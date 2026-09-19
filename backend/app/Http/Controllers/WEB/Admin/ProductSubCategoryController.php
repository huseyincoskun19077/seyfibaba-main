<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubCategory;
use App\Models\Category;
use App\Services\CategorySerialService;
use Illuminate\Http\Request;
use App\Models\PopularCategory;
use App\Models\ThreeColumnCategory;
use App\Models\MegaMenuSubCategory;
use Illuminate\Support\Facades\Schema;
class ProductSubCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(Request $request)
    {
        $categories = Category::ordered()->get(['id', 'name']);
        $categoryId = (int) $request->query('category_id', 0);

        $subCategories = collect();
        if ($categoryId > 0) {
            $subCategories = SubCategory::with('category', 'childCategories', 'products')
                ->where('category_id', $categoryId)
                ->ordered()
                ->get();
        }

        return view('admin.product_sub_category', compact('subCategories', 'categories', 'categoryId'));
    }


    public function create()
    {
        $categories=Category::ordered()->get();
        return view('admin.create_product_sub_category',compact('categories'));
    }


    public function store(Request $request)
    {
        $hasMaxInstallment = Schema::hasColumn('sub_categories', 'max_installment');
        $rules = [
            'name'=>'required',
            'slug'=>'required|unique:sub_categories',
            'category'=>'required',
            'status'=>'required',
        ];
        if ($hasMaxInstallment) {
            $rules['max_installment'] = 'nullable|integer|min:0|max:12';
        }

        $customMessages = [
            'name.required' => trans('admin_validation.Name is required'),
            'slug.required' => trans('admin_validation.Slug is required'),
            'slug.unique' => trans('admin_validation.Slug already exist'),
            'category.required' => trans('admin_validation.Category is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $subCategory = new SubCategory();
        $subCategory->category_id = $request->category;
        $subCategory->name = $request->name;
        $subCategory->slug = $request->slug;
        $subCategory->status = $request->status;
        if (Schema::hasColumn('sub_categories', 'serial')) {
            $subCategory->serial = app(CategorySerialService::class)->nextSerial(SubCategory::class, [
                'category_id' => (int) $request->category,
            ]);
        }
        if ($hasMaxInstallment) {
            if ($request->filled('max_installment')) {
                $subCategory->max_installment = (int) $request->max_installment;
            }
        }
        $subCategory->save();

        $notification = trans('admin_validation.Created Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.product-sub-category.index', [
            'category_id' => $subCategory->category_id,
        ])->with($notification);
    }

    public function show($id){
        $subCategory = SubCategory::find($id);
        return response()->json(['subCategory' => $subCategory],200);
    }

    public function edit($id)
    {
        $subCategory = SubCategory::find($id);
        $categories=Category::ordered()->get();
        return view('admin.edit_product_sub_category',compact('subCategory','categories'));
    }


    public function update(Request $request, $id)
    {
        $subCategory = SubCategory::find($id);
        $hasMaxInstallment = Schema::hasColumn('sub_categories', 'max_installment');
        $rules = [
            'name'=>'required',
            'slug'=>'required|unique:sub_categories,slug,'.$subCategory->id,
            'category'=>'required',
            'status'=>'required',
        ];
        if ($hasMaxInstallment) {
            $rules['max_installment'] = 'nullable|integer|min:0|max:12';
        }

        $customMessages = [
            'name.required' => trans('admin_validation.Name is required'),
            'slug.required' => trans('admin_validation.Slug is required'),
            'slug.unique' => trans('admin_validation.Slug already exist'),
            'category.required' => trans('admin_validation.Category is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $subCategory->category_id = $request->category;
        $subCategory->name = $request->name;
        $subCategory->slug = $request->slug;
        $subCategory->status = $request->status;
        if ($hasMaxInstallment) {
            if ($request->filled('max_installment')) {
                $subCategory->max_installment = (int) $request->max_installment;
            } else {
                $subCategory->max_installment = null;
            }
        }
        $subCategory->save();

        $notification = trans('admin_validation.Update Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.product-sub-category.index', [
            'category_id' => $subCategory->category_id,
        ])->with($notification);
    }


    public function destroy($id)
    {
        $subCategory = SubCategory::find($id);
        $categoryId = $subCategory ? (int) $subCategory->category_id : 0;
        $subCategory->delete();
        MegaMenuSubCategory::where('sub_category_id',$id)->delete();

        $notification = trans('admin_validation.Delete Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.product-sub-category.index', array_filter([
            'category_id' => $categoryId ?: null,
        ]))->with($notification);
    }

    public function changeStatus($id){
        $subCategory = SubCategory::find($id);
        if($subCategory->status==1){
            $subCategory->status=0;
            $subCategory->save();
            $message = trans('admin_validation.InActive Successfully');
        }else{
            $subCategory->status=1;
            $subCategory->save();
            $message = trans('admin_validation.Active Successfully');
        }
        return response()->json($message);
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:sub_categories,id',
            'category_id' => 'required|integer|exists:categories,id',
        ]);

        if (! Schema::hasColumn('sub_categories', 'serial')) {
            return response()->json([
                'success' => false,
                'message' => 'serial kolonu yok. Sunucuda php artisan migrate çalıştırın.',
            ], 422);
        }

        try {
            app(CategorySerialService::class)->reorder(
                SubCategory::class,
                $request->input('ids', []),
                ['category_id' => (int) $request->input('category_id')]
            );
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sıralama kaydedilemedi: '.$e->getMessage(),
            ], 500);
        }

        return response()->json(['success' => true, 'message' => 'Sıralama güncellendi.']);
    }

}
