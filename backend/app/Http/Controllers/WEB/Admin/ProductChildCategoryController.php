<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChildCategory;
use App\Models\Category;
use App\Models\SubCategory;
use App\Services\CategorySerialService;
use Illuminate\Http\Request;
use App\Models\PopularCategory;
use App\Models\ThreeColumnCategory;
use Illuminate\Support\Facades\Schema;
class ProductChildCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(Request $request)
    {
        $categories = Category::ordered()->get(['id', 'name']);
        $categoryId = (int) $request->query('category_id', 0);
        $subCategoryId = (int) $request->query('sub_category_id', 0);

        $subCategories = collect();
        if ($categoryId > 0) {
            $subCategories = SubCategory::where('category_id', $categoryId)->ordered()->get(['id', 'name', 'category_id']);
        }

        $childCategories = collect();
        if ($subCategoryId > 0) {
            $childCategories = ChildCategory::with('subCategory', 'category', 'products')
                ->where('sub_category_id', $subCategoryId)
                ->ordered()
                ->get();
        }

        return view('admin.product_child_category', compact(
            'childCategories',
            'categories',
            'subCategories',
            'categoryId',
            'subCategoryId'
        ));
    }


    public function create()
    {
        $categories=Category::ordered()->get();
        $SubCategories=SubCategory::ordered()->get();
        return view('admin.create_product_child_category',compact('categories','SubCategories'));
    }

    public function getSubcategoryByCategory($id){
        $subCategories=SubCategory::where('category_id',$id)->ordered()->get();
        $response="<option value=''>".trans('admin_validation.Select sub category')."</option>";
        foreach($subCategories as $subCategory){
            $response .= "<option value=".$subCategory->id.">".$subCategory->name."</option>";
        }
        return response()->json(['subCategories'=>$response]);
    }

    public function getChildcategoryBySubCategory($id){
        $childCategories=ChildCategory::where('sub_category_id',$id)->ordered()->get();
        $response='<option value="">'.trans('admin_validation.Select Child Category').'</option>';
        foreach($childCategories as $childCategory){
            $response .= "<option value=".$childCategory->id.">".$childCategory->name."</option>";
        }
        return response()->json(['childCategories'=>$response]);
    }



    public function store(Request $request)
    {
        $rules = [
            'name'=>'required',
            'category'=>'required',
            'sub_category'=>'required',
            'slug'=>'required|unique:child_categories',
            'status'=>'required'
        ];
        $customMessages = [
            'name.required' => trans('admin_validation.Name is required'),
            'slug.required' => trans('admin_validation.Slug is required'),
            'slug.unique' => trans('admin_validation.Slug already exist'),
            'category.required' => trans('admin_validation.Category is required'),
            'sub_category.required' => trans('admin_validation.Sub category is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $childCategory = new ChildCategory();
        $childCategory->category_id = $request->category;
        $childCategory->sub_category_id = $request->sub_category;
        $childCategory->name = $request->name;
        $childCategory->slug = $request->slug;
        $childCategory->status = $request->status;
        if (Schema::hasColumn('child_categories', 'max_installment')) {
            $val = $request->input('max_installment');
            $childCategory->max_installment = ($val === '' || $val === null) ? null : (int) $val;
        }
        if (Schema::hasColumn('child_categories', 'serial')) {
            $childCategory->serial = app(CategorySerialService::class)->nextSerial(ChildCategory::class, [
                'sub_category_id' => (int) $request->sub_category,
            ]);
        }
        $childCategory->save();

        $notification = trans('admin_validation.Created Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.product-child-category.index', [
            'category_id' => $childCategory->category_id,
            'sub_category_id' => $childCategory->sub_category_id,
        ])->with($notification);
    }


    public function show($id){
        $childCategory = ChildCategory::find($id);
        return response()->json(['childCategory' => $childCategory],200);
    }

    public function edit($id)
    {
        $childCategory = ChildCategory::find($id);
        $categories = Category::ordered()->get();
        $subCategories = SubCategory::where('category_id',$childCategory->category_id)->ordered()->get();
        return view('admin.edit_product_child_category',compact('childCategory','categories','subCategories'));
    }


    public function update(Request $request, $id)
    {
        $childCategory = ChildCategory::find($id);
        $rules = [
            'name' => 'required',
            'category' => 'required',
            'sub_category' => 'required',
            'slug' => 'required|unique:child_categories,slug,'.$childCategory->id,
            'status' => 'required'
        ];
        $customMessages = [
            'name.required' => trans('admin_validation.Name is required'),
            'slug.required' => trans('admin_validation.Slug is required'),
            'slug.unique' => trans('admin_validation.Slug already exist'),
            'category.required' => trans('admin_validation.Category is required'),
            'sub_category.required' => trans('admin_validation.Sub category is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $childCategory->category_id = $request->category;
        $childCategory->sub_category_id = $request->sub_category;
        $childCategory->name = $request->name;
        $childCategory->slug = $request->slug;
        $childCategory->status = $request->status;
        if (Schema::hasColumn('child_categories', 'max_installment')) {
            $val = $request->input('max_installment');
            $childCategory->max_installment = ($val === '' || $val === null) ? null : (int) $val;
        }
        $childCategory->save();

        $notification = trans('admin_validation.Update Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.product-child-category.index', [
            'category_id' => $childCategory->category_id,
            'sub_category_id' => $childCategory->sub_category_id,
        ])->with($notification);
    }


    public function destroy($id)
    {
        $childCategory = ChildCategory::find($id);
        $categoryId = $childCategory ? (int) $childCategory->category_id : 0;
        $subCategoryId = $childCategory ? (int) $childCategory->sub_category_id : 0;
        $childCategory->delete();
        $notification = trans('admin_validation.Delete Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.product-child-category.index', array_filter([
            'category_id' => $categoryId ?: null,
            'sub_category_id' => $subCategoryId ?: null,
        ]))->with($notification);
    }

    public function changeStatus($id){
        $childCategory = ChildCategory::find($id);
        if($childCategory->status==1){
            $childCategory->status=0;
            $childCategory->save();
            $message = trans('admin_validation.InActive Successfully');
        }else{
            $childCategory->status=1;
            $childCategory->save();
            $message = trans('admin_validation.Active Successfully');
        }
        return response()->json($message);
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:child_categories,id',
            'sub_category_id' => 'required|integer|exists:sub_categories,id',
        ]);

        if (! Schema::hasColumn('child_categories', 'serial')) {
            return response()->json([
                'success' => false,
                'message' => 'serial kolonu yok. Sunucuda php artisan migrate çalıştırın.',
            ], 422);
        }

        try {
            app(CategorySerialService::class)->reorder(
                ChildCategory::class,
                $request->input('ids', []),
                ['sub_category_id' => (int) $request->input('sub_category_id')]
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
