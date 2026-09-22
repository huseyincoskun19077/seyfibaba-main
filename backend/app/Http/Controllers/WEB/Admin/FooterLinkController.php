<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Footer;
use App\Models\FooterLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class FooterLinkController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index()
    {
        $this->syncAllBrandsToColumnOne();

        return $this->renderColumn(1, 'Popüler Marka ve Mağazalar');
    }

    public function secondColFooterLink()
    {
        return $this->renderColumn(2, 'Popüler Sayfalar');
    }

    public function thirdColFooterLink()
    {
        return $this->renderColumn(3, 'Yardım Linkleri');
    }

    private function renderColumn(int $column, string $title)
    {
        $query = FooterLink::where('column', $column);
        if (Schema::hasColumn('footer_links', 'sort_order')) {
            $query->orderBy('sort_order')->orderBy('id');
        } else {
            $query->orderBy('id');
        }
        $links = $query->get();

        $footer = Footer::first();
        $columnTitle = match ($column) {
            1 => $footer->first_column ?: $title,
            2 => $footer->second_column ?: $title,
            3 => $footer->third_column ?: $title,
            default => $title,
        };

        return view('admin.footer_link', compact('links', 'column', 'title', 'columnTitle'));
    }

    public function store(Request $request)
    {
        $rules = [
            'link' => 'required',
            'name' => 'required',
            'column' => 'required',
        ];
        $customMessages = [
            'link.required' => trans('admin_validation.Link is required'),
            'name.required' => trans('admin_validation.Name is required'),
            'column.required' => trans('admin_validation.Column is required'),
        ];
        $this->validate($request, $rules, $customMessages);

        $maxSort = 0;
        if (Schema::hasColumn('footer_links', 'sort_order')) {
            $maxSort = (int) FooterLink::where('column', $request->column)->max('sort_order');
        }

        $link = new FooterLink();
        $link->link = $request->link;
        $link->title = $request->name;
        $link->column = $request->column;
        if (Schema::hasColumn('footer_links', 'status')) {
            $link->status = $request->boolean('status', true);
        }
        if (Schema::hasColumn('footer_links', 'sort_order')) {
            $link->sort_order = $maxSort + 1;
        }
        $link->save();

        $notification = trans('admin_validation.Create Successfully');
        $notification = ['messege' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }

    public function show($id)
    {
        $link = FooterLink::find($id);

        return response()->json(['link' => $link], 200);
    }

    public function update(Request $request, $id)
    {
        $rules = [
            'name' => 'required',
            'link' => 'required',
        ];
        $customMessages = [
            'link.required' => trans('admin_validation.Link is required'),
            'name.required' => trans('admin_validation.Name is required'),
        ];
        $this->validate($request, $rules, $customMessages);

        $link = FooterLink::find($id);
        $link->link = $request->link;
        $link->title = $request->name;
        if (Schema::hasColumn('footer_links', 'status') && $request->has('status')) {
            $link->status = $request->boolean('status');
        }
        $link->save();

        $notification = trans('admin_validation.Update Successfully');
        $notification = ['messege' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }

    public function destroy($id)
    {
        $link = FooterLink::find($id);
        $link->delete();
        $notification = trans('admin_validation.Delete Successfully');
        $notification = ['messege' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }

    public function updateColTitle(Request $request, $id)
    {
        $rules = [
            'title' => 'required',
        ];
        $customMessages = [
            'title.required' => trans('admin_validation.Title is required'),
        ];
        $this->validate($request, $rules, $customMessages);
        $footer = Footer::first();
        if ($id == 1) {
            $footer->first_column = $request->title;
            $footer->save();
        } elseif ($id == 2) {
            $footer->second_column = $request->title;
            $footer->save();
        } elseif ($id == 3) {
            $footer->third_column = $request->title;
            $footer->save();
        }
        $notification = trans('admin_validation.Update Successfully');
        $notification = ['messege' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }

    public function toggleStatus(Request $request, $id)
    {
        if (! Schema::hasColumn('footer_links', 'status')) {
            return response()->json(['success' => false, 'message' => 'status sütunu yok. Migration çalıştırın.'], 422);
        }

        $link = FooterLink::findOrFail((int) $id);
        $link->status = $request->boolean('status');
        $link->save();

        return response()->json([
            'success' => true,
            'status' => (bool) $link->status,
            'message' => $link->status ? 'Sitede görünür.' : 'Siteden gizlendi.',
        ]);
    }

    public function reorder(Request $request)
    {
        if (! Schema::hasColumn('footer_links', 'sort_order')) {
            return response()->json(['success' => false, 'message' => 'sort_order sütunu yok. Migration çalıştırın.'], 422);
        }

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:footer_links,id',
            'column' => 'nullable|integer',
        ]);

        $column = (int) $request->input('column', 0);

        foreach (array_values($request->input('ids', [])) as $index => $id) {
            $query = FooterLink::query()->where('id', (int) $id);
            if ($column > 0) {
                $query->where('column', $column);
            }
            $query->update(['sort_order' => $index + 1]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sıralama güncellendi.',
        ]);
    }

    public function syncBrands(Request $request)
    {
        $added = $this->syncAllBrandsToColumnOne();

        $notification = $added > 0
            ? "{$added} marka footer listesine eklendi. İstemediklerinizin tikini kaldırın."
            : 'Tüm markalar zaten listede. İstemediklerinizin tikini kaldırabilirsiniz.';

        return redirect()->back()->with([
            'messege' => $notification,
            'alert-type' => 'success',
        ]);
    }

    /**
     * Tüm markaları column=1 footer linklerine ekler.
     * Mevcut kayıtların status/sıra değerine dokunmaz; sadece eksikleri ekler.
     */
    private function syncAllBrandsToColumnOne(): int
    {
        if (! Schema::hasTable('brands') || ! Schema::hasTable('footer_links')) {
            return 0;
        }

        $brands = Brand::query()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        if ($brands->isEmpty()) {
            return 0;
        }

        $existing = FooterLink::where('column', 1)->get();
        $byBrandId = [];
        $byLink = [];
        $byTitle = [];

        foreach ($existing as $row) {
            if (! empty($row->brand_id)) {
                $byBrandId[(int) $row->brand_id] = $row;
            }
            $byLink[mb_strtolower(trim((string) $row->link))] = $row;
            $byTitle[mb_strtolower(trim((string) $row->title))] = $row;
        }

        $maxSort = Schema::hasColumn('footer_links', 'sort_order')
            ? (int) FooterLink::where('column', 1)->max('sort_order')
            : 0;

        $added = 0;

        foreach ($brands as $brand) {
            $linkPath = '/products?brand='.$brand->slug;
            $linkKey = mb_strtolower($linkPath);
            $titleKey = mb_strtolower(trim((string) $brand->name));

            $match = $byBrandId[(int) $brand->id]
                ?? $byLink[$linkKey]
                ?? $byTitle[$titleKey]
                ?? null;

            if ($match) {
                $dirty = false;
                if (Schema::hasColumn('footer_links', 'brand_id') && empty($match->brand_id)) {
                    $match->brand_id = $brand->id;
                    $dirty = true;
                }
                // Link formatını standartlaştır (manuel eklenenler için)
                if ($match->link !== $linkPath && str_contains((string) $match->link, 'brand='.$brand->slug)) {
                    $match->link = $linkPath;
                    $dirty = true;
                } elseif (empty($match->link) || $match->link === '#') {
                    $match->link = $linkPath;
                    $dirty = true;
                }
                if ($dirty) {
                    $match->save();
                }
                continue;
            }

            $maxSort++;
            $row = new FooterLink();
            $row->column = 1;
            $row->title = $brand->name;
            $row->link = $linkPath;
            if (Schema::hasColumn('footer_links', 'status')) {
                $row->status = true;
            }
            if (Schema::hasColumn('footer_links', 'sort_order')) {
                $row->sort_order = $maxSort;
            }
            if (Schema::hasColumn('footer_links', 'brand_id')) {
                $row->brand_id = $brand->id;
            }
            $row->save();
            $added++;

            $byBrandId[(int) $brand->id] = $row;
            $byLink[$linkKey] = $row;
            $byTitle[$titleKey] = $row;
        }

        return $added;
    }
}
