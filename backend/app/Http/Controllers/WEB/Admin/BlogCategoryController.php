<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use Illuminate\Http\Request;

class BlogCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index()
    {
        $categories = BlogCategory::orderBy('id', 'desc')->get();
        return view('admin.blog_category', compact('categories'));
    }

    public function create()
    {
        return view('admin.create_blog_category');
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required',
            'slug' => 'required|unique:blog_categories',
        ];
        $this->validate($request, $rules);

        $category = new BlogCategory();
        $category->name = $request->name;
        $category->slug = $request->slug;
        $category->status = $request->status;
        $category->save();

        $notification = array('messege' => 'Blog kategorisi oluşturuldu.', 'alert-type' => 'success');
        return redirect()->route('admin.blog-category.index')->with($notification);
    }

    public function edit($id)
    {
        $category = BlogCategory::find($id);
        return view('admin.edit_blog_category', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $rules = [
            'name' => 'required',
            'slug' => 'required|unique:blog_categories,slug,' . $id,
        ];
        $this->validate($request, $rules);

        $category = BlogCategory::find($id);
        $category->name = $request->name;
        $category->slug = $request->slug;
        $category->status = $request->status;
        $category->save();

        $notification = array('messege' => 'Blog kategorisi güncellendi.', 'alert-type' => 'success');
        return redirect()->route('admin.blog-category.index')->with($notification);
    }

    public function destroy($id)
    {
        BlogCategory::find($id)->delete();
        $notification = array('messege' => 'Blog kategorisi silindi.', 'alert-type' => 'success');
        return redirect()->route('admin.blog-category.index')->with($notification);
    }

    public function changeStatus($id)
    {
        $category = BlogCategory::find($id);
        $category->status = $category->status == 1 ? 0 : 1;
        $category->save();
        return response()->json($category->status == 1 ? 'Aktif edildi' : 'Pasif edildi');
    }
}
