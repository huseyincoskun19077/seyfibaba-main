<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Image;

class SellerBrandController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('checkseller');
    }

    public function index()
    {
        $seller = Auth::guard('api')->user()->seller;

        $brands = Brand::query()
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'logo', 'status']);

        $myBrands = Brand::query()
            ->where('created_by', $seller->id)
            ->where('created_by_type', 'App\\Models\\Vendor')
            ->orderBy('name')
            ->get();

        return response()->json([
            'brands' => $brands,
            'my_brands' => $myBrands,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:brands',
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        $seller = Auth::guard('api')->user()->seller;
        $brand = new Brand();

        $extention = $request->logo->getClientOriginalExtension();
        $logo_name = 'uploads/custom-images/' . Str::slug($request->name) . date('-Y-m-d-h-i-s-') . rand(999, 9999) . '.' . $extention;
        Image::make($request->logo)->save(public_path() . '/' . $logo_name);
        $brand->logo = $logo_name;
        $brand->name = $request->name;
        $brand->slug = Str::slug($request->name);
        $brand->status = 1;
        $brand->created_by = $seller->id;
        $brand->created_by_type = 'App\\Models\\Vendor';
        $brand->is_admin_created = false;
        $brand->save();

        return response()->json([
            'message' => 'Marka başarıyla eklendi.',
            'brand' => $brand,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $seller = Auth::guard('api')->user()->seller;
        $brand = Brand::findOrFail($id);

        if ((int) $brand->created_by !== (int) $seller->id || $brand->created_by_type !== 'App\\Models\\Vendor') {
            return response()->json(['message' => 'Bu markayı güncelleme yetkiniz yok.'], 403);
        }

        $request->validate([
            'name' => 'required|unique:brands,name,' . $brand->id,
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            if ($brand->logo && File::exists(public_path() . '/' . $brand->logo)) {
                File::delete(public_path() . '/' . $brand->logo);
            }
            $extention = $request->logo->getClientOriginalExtension();
            $logo_name = 'uploads/custom-images/' . Str::slug($request->name) . date('-Y-m-d-h-i-s-') . rand(999, 9999) . '.' . $extention;
            Image::make($request->logo)->save(public_path() . '/' . $logo_name);
            $brand->logo = $logo_name;
        }

        $brand->name = $request->name;
        $brand->slug = Str::slug($request->name);
        $brand->save();

        return response()->json([
            'message' => 'Marka başarıyla güncellendi.',
            'brand' => $brand,
        ]);
    }

    public function destroy($id)
    {
        return response()->json([
            'message' => 'Marka silinemez. Gerekirse düzenleyebilirsiniz.',
        ], 403);
    }
}
