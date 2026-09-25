<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\PersonalizationShowcase;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class PersonalizationShowcaseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(Request $request)
    {
        if (! Schema::hasTable('personalization_showcases')) {
            return response(
                '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Sana Özel</title></head><body style="font-family:sans-serif;padding:24px">'
                .'<h1>personalization_showcases tablosu yok</h1>'
                .'<pre>cd /opt/seyfibaba-main/backend'."\n".'php artisan migrate --force</pre>'
                .'</body></html>',
                503
            );
        }

        $rows = PersonalizationShowcase::query()->orderBy('business_type')->get();
        $edit = null;
        if ($request->filled('edit')) {
            $edit = PersonalizationShowcase::query()->find((int) $request->query('edit'));
        }

        $categoryTree = Category::query()
            ->where('status', 1)
            ->with([
                'activeSubCategories.activeChildCategories',
            ])
            ->ordered()
            ->get(['id', 'name', 'slug']);

        $vendors = Vendor::query()
            ->orderBy('shop_name')
            ->limit(500)
            ->get(['id', 'shop_name']);

        $selectedCats = $edit
            ? $edit->decodeIds($edit->category_ids)
            : [];
        $selectedOpeningCats = $edit
            ? $edit->decodeIds($edit->opening_category_ids)
            : [];

        return view('admin.personalization_showcase', [
            'rows' => $rows,
            'edit' => $edit,
            'types' => PersonalizationShowcase::BUSINESS_TYPES,
            'categoryTree' => $categoryTree,
            'vendors' => $vendors,
            'selectedCats' => $selectedCats,
            'selectedOpeningCats' => $selectedOpeningCats,
        ]);
    }

    public function store(Request $request)
    {
        if (! Schema::hasTable('personalization_showcases')) {
            return redirect()->route('admin.dashboard')->with([
                'messege' => 'Tablo yok. Migrate çalıştırın.',
                'alert-type' => 'error',
            ]);
        }

        $request->validate([
            'business_type' => ['required', Rule::in(array_keys(PersonalizationShowcase::BUSINESS_TYPES))],
            'title' => ['required', 'string', 'max:120'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer'],
            'product_ids' => ['nullable', 'string', 'max:2000'],
            'vendor_ids' => ['nullable', 'string', 'max:2000'],
            'opening_category_ids' => ['nullable', 'array'],
            'opening_category_ids.*' => ['integer'],
            'opening_product_ids' => ['nullable', 'string', 'max:2000'],
            'opening_vendor_ids' => ['nullable', 'string', 'max:2000'],
            'home_limit' => ['nullable', 'integer', 'min:4', 'max:24'],
        ]);

        $row = $request->filled('id')
            ? PersonalizationShowcase::query()->findOrFail((int) $request->input('id'))
            : PersonalizationShowcase::query()->firstOrNew([
                'business_type' => $request->input('business_type'),
            ]);

        $tmp = new PersonalizationShowcase();
        $row->business_type = $request->input('business_type');
        $row->title = trim((string) $request->input('title')) ?: 'Sana Özel';
        $row->category_ids = $tmp->encodeIds($request->input('category_ids', []));
        $row->product_ids = $tmp->encodeIds($request->input('product_ids'));
        $row->vendor_ids = $tmp->encodeIds($request->input('vendor_ids'));
        $row->opening_category_ids = $tmp->encodeIds($request->input('opening_category_ids', []));
        $row->opening_product_ids = $tmp->encodeIds($request->input('opening_product_ids'));
        $row->opening_vendor_ids = $tmp->encodeIds($request->input('opening_vendor_ids'));
        $row->status = $request->boolean('status');
        $row->include_high_views = $request->boolean('include_high_views');
        $row->home_limit = (int) ($request->input('home_limit') ?: PersonalizationShowcase::HOME_LIMIT_DEFAULT);
        $row->save();

        return redirect()->route('admin.personalization-showcase.index')->with([
            'messege' => 'Sana Özel vitrin kaydedildi',
            'alert-type' => 'success',
        ]);
    }

    public function destroy($id)
    {
        PersonalizationShowcase::query()->where('id', (int) $id)->delete();

        return redirect()->route('admin.personalization-showcase.index')->with([
            'messege' => 'Silindi',
            'alert-type' => 'success',
        ]);
    }
}
