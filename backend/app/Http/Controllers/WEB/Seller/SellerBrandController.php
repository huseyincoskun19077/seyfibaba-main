<?php

namespace App\Http\Controllers\WEB\Seller;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Image;
use File;
use Auth;

class SellerBrandController extends Controller
{
    public function index()
    {
        $seller = Auth::guard('web')->user()->seller;
        
        $brands = Brand::where('status', 1)
            ->orderBy('name', 'asc')
            ->get();
        
        $myBrands = Brand::where('created_by', $seller->id)
            ->where('created_by_type', 'App\Models\Vendor')
            ->orderBy('name', 'asc')
            ->get();
        
        return view('seller.brand.index', compact('brands', 'myBrands'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:brands',
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ], [
            'name.required' => 'Marka adı zorunludur.',
            'name.unique' => 'Bu marka adı zaten mevcut.',
            'logo.required' => 'Logo zorunludur.',
            'logo.image' => 'Geçerli bir resim dosyası olmalı.',
        ]);

        $seller = Auth::guard('web')->user()->seller;

        $brand = new Brand();
        
        if ($request->hasFile('logo')) {
            $extention = $request->logo->getClientOriginalExtension();
            $logo_name = Str::slug($request->name) . date('-Y-m-d-h-i-s-') . rand(999, 9999) . '.' . $extention;
            $logo_name = 'uploads/custom-images/' . $logo_name;
            
            Image::make($request->logo)->save(public_path() . '/' . $logo_name);
            $brand->logo = $logo_name;
        }
        
        $brand->name = $request->name;
        $brand->slug = Str::slug($request->name);
        $brand->status = 1;
        $brand->created_by = $seller->id;
        $brand->created_by_type = 'App\Models\Vendor';
        $brand->is_admin_created = false;
        $brand->save();

        $notification = array('messege' => 'Marka başarıyla eklendi.', 'alert-type' => 'success');
        return redirect()->back()->with($notification);
    }

    public function update(Request $request, $id)
    {
        $brand = Brand::findOrFail($id);
        $seller = Auth::guard('web')->user()->seller;
        
        if ($brand->created_by != $seller->id || $brand->created_by_type != 'App\Models\Vendor') {
            $notification = array('messege' => 'Bu markayı güncelleme yetkiniz yok.', 'alert-type' => 'error');
            return redirect()->back()->with($notification);
        }

        $request->validate([
            'name' => 'required|unique:brands,name,' . $brand->id,
        ], [
            'name.required' => 'Marka adı zorunludur.',
            'name.unique' => 'Bu marka adı zaten mevcut.',
        ]);

        if ($request->hasFile('logo')) {
            if ($brand->logo && File::exists(public_path() . '/' . $brand->logo)) {
                File::delete(public_path() . '/' . $brand->logo);
            }
            
            $extention = $request->logo->getClientOriginalExtension();
            $logo_name = Str::slug($request->name) . date('-Y-m-d-h-i-s-') . rand(999, 9999) . '.' . $extention;
            $logo_name = 'uploads/custom-images/' . $logo_name;
            
            Image::make($request->logo)->save(public_path() . '/' . $logo_name);
            $brand->logo = $logo_name;
        }
        
        $brand->name = $request->name;
        $brand->slug = Str::slug($request->name);
        $brand->save();

        $notification = array('messege' => 'Marka başarıyla güncellendi.', 'alert-type' => 'success');
        return redirect()->back()->with($notification);
    }

    public function destroy($id)
    {
        $notification = array(
            'messege' => 'Marka silinemez. Gerekirse düzenleyebilirsiniz.',
            'alert-type' => 'error',
        );
        return redirect()->back()->with($notification);
    }
}