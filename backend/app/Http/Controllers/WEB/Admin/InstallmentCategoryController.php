<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ChildCategory;
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

        $hasChildColumn = Schema::hasColumn('child_categories', 'max_installment');

        $categories = Category::ordered()->get();
        $subCategories = SubCategory::ordered()->get();
        $childCategories = ChildCategory::ordered()->get();
        $iyzicoRules = CategoryInstallmentService::IYZICO_RULES;

        return view('admin.installment_categories', compact(
            'hasColumns',
            'hasChildColumn',
            'categories',
            'subCategories',
            'childCategories',
            'iyzicoRules'
        ));
    }

    public function update(Request $request)
    {
        if (
            ! Schema::hasColumn('categories', 'max_installment')
            || ! Schema::hasColumn('sub_categories', 'max_installment')
        ) {
            return redirect()->back()->with([
                'messege' => 'DB kolonları yok. Önce migrate çalıştırın.',
                'alert-type' => 'error',
            ]);
        }

        $request->validate([
            'categories' => 'array',
            'sub_categories' => 'array',
            'child_categories' => 'array',
        ]);

        foreach (($request->input('categories') ?? []) as $id => $val) {
            $cat = Category::find($id);
            if (! $cat) {
                continue;
            }
            $cat->max_installment = ($val === '' || $val === null) ? null : (int) $val;
            $cat->save();
        }

        foreach (($request->input('sub_categories') ?? []) as $id => $val) {
            $sub = SubCategory::find($id);
            if (! $sub) {
                continue;
            }
            $sub->max_installment = ($val === '' || $val === null) ? null : (int) $val;
            $sub->save();
        }

        if (Schema::hasColumn('child_categories', 'max_installment')) {
            foreach (($request->input('child_categories') ?? []) as $id => $val) {
                $child = ChildCategory::find($id);
                if (! $child) {
                    continue;
                }
                $child->max_installment = ($val === '' || $val === null) ? null : (int) $val;
                $child->save();
            }
        }

        return redirect()->back()->with([
            'messege' => 'Taksit kategori ayarları güncellendi',
            'alert-type' => 'success',
        ]);
    }
}
