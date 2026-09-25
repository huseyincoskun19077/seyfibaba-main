<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\HomeBlock;
use App\Models\Product;
use App\Models\Vendor;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class HomeBlockController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(Request $request)
    {
        if (! Schema::hasTable('home_blocks')) {
            return response(
                '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Anasayfa Blokları</title></head><body style="font-family:sans-serif;padding:24px">'
                .'<h1>home_blocks tablosu yok</h1>'
                .'<pre>cd /opt/seyfibaba-main/backend'."\n".'php artisan migrate --force</pre>'
                .'</body></html>',
                503
            );
        }

        $blocks = HomeBlock::query()->orderBy('serial')->orderBy('id')->get();
        $edit = null;
        $selectedProducts = collect();
        $selectedCategories = [];
        if ($request->filled('edit')) {
            $edit = HomeBlock::query()->find((int) $request->query('edit'));
            if ($edit) {
                $pids = $edit->decodeIds($edit->product_ids);
                if ($pids) {
                    $selectedProducts = Product::query()
                        ->with('seller:id,shop_name')
                        ->whereIn('id', $pids)
                        ->get(['id', 'name', 'short_name', 'vendor_id'])
                        ->sortBy(fn ($p) => array_search((int) $p->id, $pids, true))
                        ->values();
                }
                $selectedCategories = $edit->decodeIds($edit->category_ids);
            }
        }

        $categoryOptions = $this->flatCategoryOptions();

        return view('admin.home_blocks', [
            'blocks' => $blocks,
            'edit' => $edit,
            'types' => HomeBlock::TYPES,
            'feeds' => HomeBlock::FEEDS,
            'selectedProducts' => $selectedProducts,
            'selectedCategories' => $selectedCategories,
            'categoryOptions' => $categoryOptions,
        ]);
    }

    public function store(Request $request)
    {
        if (! Schema::hasTable('home_blocks')) {
            return redirect()->route('admin.dashboard')->with([
                'messege' => 'Tablo yok. Migrate çalıştırın.',
                'alert-type' => 'error',
            ]);
        }

        $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(array_keys(HomeBlock::TYPES))],
            'feed' => ['nullable', Rule::in(array_keys(HomeBlock::FEEDS))],
            'link' => ['nullable', 'string', 'max:500'],
            'mobile_link' => ['nullable', 'string', 'max:500'],
            'see_all_url' => ['nullable', 'string', 'max:500'],
            'product_ids' => ['nullable'],
            'category_ids' => ['nullable'],
            'limit_count' => ['nullable', 'integer', 'min:1', 'max:48'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $block = $request->filled('id')
            ? HomeBlock::query()->findOrFail((int) $request->input('id'))
            : new HomeBlock();

        $block->title = trim((string) $request->input('title'));
        $block->type = (string) $request->input('type');
        $block->feed = $block->type === 'product_feed'
            ? (trim((string) $request->input('feed', '')) ?: 'popular')
            : null;
        $block->link = trim((string) $request->input('link', '')) ?: null;
        $block->mobile_link = trim((string) $request->input('mobile_link', '')) ?: null;
        $block->see_all_url = trim((string) $request->input('see_all_url', '')) ?: null;
        $block->product_ids = $block->encodeIds($request->input('product_ids', []));
        $block->category_ids = $block->encodeIds($request->input('category_ids', []));
        $block->limit_count = (int) ($request->input('limit_count') ?: 12);
        $block->status = $request->boolean('status');
        $block->show_on_web = $request->boolean('show_on_web');
        $block->show_on_mobile = $request->boolean('show_on_mobile');

        if (! $request->filled('id')) {
            $max = (int) HomeBlock::query()->max('serial');
            $block->serial = $max + 1;
        }

        if ($request->hasFile('image')) {
            $dir = public_path('uploads/website-images');
            if (! File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
            $file = $request->file('image');
            $name = 'home-block-'.date('Y-m-d-His').'-'.rand(1000, 9999).'.'.$file->getClientOriginalExtension();
            $file->move($dir, $name);
            if ($block->image && File::exists(public_path($block->image))) {
                File::delete(public_path($block->image));
            }
            $block->image = 'uploads/website-images/'.$name;
        }

        $block->save();

        return redirect()->route('admin.home-blocks.index')->with([
            'messege' => $request->filled('id') ? 'Blok güncellendi' : 'Blok eklendi',
            'alert-type' => 'success',
        ]);
    }

    public function destroy($id)
    {
        $block = HomeBlock::query()->findOrFail((int) $id);
        if ($block->image && File::exists(public_path($block->image))) {
            File::delete(public_path($block->image));
        }
        $block->delete();

        return redirect()->route('admin.home-blocks.index')->with([
            'messege' => 'Blok silindi',
            'alert-type' => 'success',
        ]);
    }

    public function reorder(Request $request)
    {
        if (! Schema::hasTable('home_blocks')) {
            return response()->json(['success' => false, 'message' => 'Tablo yok'], 422);
        }

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:home_blocks,id',
        ]);

        foreach (array_values($request->input('ids', [])) as $index => $id) {
            HomeBlock::query()->where('id', (int) $id)->update(['serial' => $index + 1]);
        }

        return response()->json(['success' => true, 'message' => 'Sıralama güncellendi.']);
    }

    /** İndirimli ürünler — satıcıya göre liste + anasayfa seçimi */
    public function discountedIndex(Request $request)
    {
        if (! Schema::hasTable('home_blocks')) {
            return redirect()->route('admin.home-blocks.index');
        }

        $q = trim((string) $request->query('q', ''));
        $vendorId = (int) $request->query('vendor_id', 0);

        $query = Product::query()
            ->with('seller:id,shop_name,email')
            ->where('approve_by_admin', 1)
            ->where('status', 1)
            ->whereNotNull('offer_price')
            ->where('offer_price', '>', 0)
            ->whereColumn('offer_price', '<', 'price')
            ->orderByDesc('id');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', '%'.$q.'%')
                    ->orWhere('short_name', 'like', '%'.$q.'%');
                if (ctype_digit($q)) {
                    $w->orWhere('id', (int) $q);
                }
            });
        }

        if ($vendorId > 0) {
            $query->where('vendor_id', $vendorId);
        } elseif ($request->query('vendor_id') === '0') {
            $query->where(function ($w) {
                $w->whereNull('vendor_id')->orWhere('vendor_id', 0);
            });
        }

        $products = $query->paginate(40)->withQueryString();

        $sellersWithDiscount = Product::query()
            ->where('approve_by_admin', 1)
            ->where('status', 1)
            ->whereNotNull('offer_price')
            ->where('offer_price', '>', 0)
            ->whereColumn('offer_price', '<', 'price')
            ->selectRaw('vendor_id, COUNT(*) as cnt')
            ->groupBy('vendor_id')
            ->orderByDesc('cnt')
            ->get();

        $vendorIds = $sellersWithDiscount->pluck('vendor_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        $vendors = $vendorIds
            ? Vendor::query()->whereIn('id', $vendorIds)->get(['id', 'shop_name', 'email'])->keyBy('id')
            : collect();

        $block = HomeBlock::query()
            ->where('type', 'product_feed')
            ->where('feed', 'discounted')
            ->orderBy('serial')
            ->first();

        $homepageIds = $block ? $block->decodeIds($block->product_ids) : [];

        return view('admin.discounted_products', [
            'products' => $products,
            'sellersWithDiscount' => $sellersWithDiscount,
            'vendors' => $vendors,
            'homepageIds' => $homepageIds,
            'block' => $block,
            'q' => $q,
            'vendorId' => $request->query('vendor_id'),
        ]);
    }

    /** Seçilen indirimli ürünleri anasayfa “İndirimli” bloğuna yaz */
    public function discountedSave(Request $request)
    {
        if (! Schema::hasTable('home_blocks')) {
            return redirect()->route('admin.dashboard')->with([
                'messege' => 'Tablo yok. Migrate çalıştırın.',
                'alert-type' => 'error',
            ]);
        }

        $request->validate([
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $request->input('product_ids', []))));

        $block = HomeBlock::query()
            ->where('type', 'product_feed')
            ->where('feed', 'discounted')
            ->orderBy('serial')
            ->first();

        if (! $block) {
            $max = (int) HomeBlock::query()->max('serial');
            $block = new HomeBlock();
            $block->title = 'İndirimli Ürünler';
            $block->type = 'product_feed';
            $block->feed = 'discounted';
            $block->serial = $max + 1;
            $block->status = true;
            $block->show_on_web = true;
            $block->show_on_mobile = true;
            $block->limit_count = max(12, count($ids) ?: 12);
        }

        $block->product_ids = $block->encodeIds($ids);
        if ($ids !== []) {
            $block->limit_count = max((int) ($block->limit_count ?: 12), count($ids));
        }
        $block->save();

        return redirect()->route('admin.home-blocks.discounted')->with([
            'messege' => $ids === []
                ? 'Anasayfa seçimi temizlendi (otomatik indirimli listesi kullanılır).'
                : count($ids).' ürün anasayfa indirimli bölümüne eklendi.',
            'alert-type' => 'success',
        ]);
    }

    /** Select2 AJAX — tüm ürünler (admin + satıcı), isimle ara */
    public function searchProducts(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $query = Product::query()
            ->with('seller:id,shop_name')
            ->where('approve_by_admin', 1)
            ->where('status', 1)
            ->orderByDesc('id')
            ->limit(40);

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', '%'.$q.'%')
                    ->orWhere('short_name', 'like', '%'.$q.'%')
                    ->orWhere('slug', 'like', '%'.$q.'%');
                if (ctype_digit($q)) {
                    $w->orWhere('id', (int) $q);
                }
            });
        }

        $results = $query->get(['id', 'name', 'short_name', 'vendor_id'])->map(function ($p) {
            $seller = $p->seller?->shop_name ?: 'Platform';
            $label = trim((string) ($p->short_name ?: $p->name));

            return [
                'id' => $p->id,
                'text' => $label.' — '.$seller.' (#'.$p->id.')',
            ];
        });

        return response()->json(['results' => $results]);
    }

    /** Select2 AJAX — satıcılar, mağaza adıyla ara */
    public function searchVendors(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $query = Vendor::query()->orderBy('shop_name')->limit(40);

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('shop_name', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%');
                if (ctype_digit($q)) {
                    $w->orWhere('id', (int) $q);
                }
            });
        }

        $results = $query->get(['id', 'shop_name'])->map(fn ($v) => [
            'id' => $v->id,
            'text' => ($v->shop_name ?: 'Satıcı').' (#'.$v->id.')',
        ]);

        return response()->json(['results' => $results]);
    }

    /** @return array<int, array{id:int,label:string}> */
    private function flatCategoryOptions(): array
    {
        $out = [];
        $cats = Category::query()
            ->where('status', 1)
            ->with(['activeSubCategories.activeChildCategories'])
            ->ordered()
            ->get(['id', 'name']);

        foreach ($cats as $cat) {
            $out[] = ['id' => (int) $cat->id, 'label' => $cat->name];
            foreach ($cat->activeSubCategories as $sub) {
                $out[] = ['id' => (int) $sub->id, 'label' => '└ '.$sub->name];
                foreach ($sub->activeChildCategories as $child) {
                    $out[] = ['id' => (int) $child->id, 'label' => '└─ '.$child->name];
                }
            }
        }

        return $out;
    }
}
