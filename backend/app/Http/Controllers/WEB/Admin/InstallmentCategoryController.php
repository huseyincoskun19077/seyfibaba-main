<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\SubCategory;
use App\Services\CategoryInstallmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class InstallmentCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index()
    {
        $hasColumns =
            Schema::hasColumn('categories', 'max_installment')
            && Schema::hasColumn('sub_categories', 'max_installment');

        $categories = Category::orderBy('name')->get();
        $subCategories = SubCategory::orderBy('name')->get();
        $iyzicoRules = CategoryInstallmentService::IYZICO_RULES;

        return view('admin.installment_categories', compact('hasColumns', 'categories', 'subCategories', 'iyzicoRules'));
    }

    public function update(Request $request)
    {
        if (
            !Schema::hasColumn('categories', 'max_installment')
            || !Schema::hasColumn('sub_categories', 'max_installment')
        ) {
            return redirect()->back()->with([
                'messege' => 'DB kolonları yok. Önce migrate çalıştırın.',
                'alert-type' => 'error',
            ]);
        }

        $request->validate([
            'categories' => 'array',
            'sub_categories' => 'array',
        ]);

        foreach (($request->input('categories') ?? []) as $id => $val) {
            $cat = Category::find($id);
            if (! $cat) continue;
            $cat->max_installment = ($val === '' || $val === null) ? null : (int) $val;
            $cat->save();
        }

        foreach (($request->input('sub_categories') ?? []) as $id => $val) {
            $sub = SubCategory::find($id);
            if (! $sub) continue;
            $sub->max_installment = ($val === '' || $val === null) ? null : (int) $val;
            $sub->save();
        }

        return redirect()->back()->with([
            'messege' => 'Taksit kategori ayarları güncellendi',
            'alert-type' => 'success',
        ]);
    }
}

