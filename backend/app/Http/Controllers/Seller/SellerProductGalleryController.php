<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Concerns\AuthorizesSellerProduct;
use App\Http\Controllers\Controller;
use App\Models\ProductGallery;
use Illuminate\Http\Request;
use Image;
use File;

class SellerProductGalleryController extends Controller
{
    use AuthorizesSellerProduct;

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index($productId)
    {
        $product = $this->findSellerProduct($productId);
        if (! $product) {
            return response()->json(['message' => trans('Something went wrong')], 403);
        }

        $gallery = ProductGallery::where('product_id', $productId)->get();

        return response()->json(['product' => $product, 'gallery' => $gallery]);
    }

    public function store(Request $request)
    {
        $rules = [
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,jpg,png,webp|max:5120',
            'product_id' => 'required',
        ];
        $customMessages = [
            'images.required' => trans('Images is required'),
            'product_id.required' => trans('Product is required'),
        ];
        $this->validate($request, $rules, $customMessages);

        $product = $this->findSellerProductById($request->product_id);
        if (! $product) {
            return response()->json(['message' => trans('Something went wrong')], 403);
        }

        foreach ($request->images as $image) {
            $extention = $image->getClientOriginalExtension();
            $image_name = 'Gallery'.date('-Y-m-d-h-i-s-').rand(999, 9999).'.'.$extention;
            $image_name = 'uploads/custom-images/'.$image_name;
            Image::make($image)->save(public_path().'/'.$image_name);
            $gallery = new ProductGallery();
            $gallery->product_id = $request->product_id;
            $gallery->image = $image_name;
            $gallery->save();
        }

        return response()->json(['message' => trans('Uploaded Successfully')], 200);
    }

    public function destroy($id)
    {
        $gallery = ProductGallery::find($id);
        if (! $gallery) {
            return response()->json(['message' => trans('Something went wrong')], 404);
        }

        if (! $this->findSellerProductById($gallery->product_id)) {
            return response()->json(['message' => trans('Something went wrong')], 403);
        }

        $old_image = $gallery->image;
        $gallery->delete();
        if ($old_image && File::exists(public_path().'/'.$old_image)) {
            unlink(public_path().'/'.$old_image);
        }

        return response()->json(['message' => trans('Delete Successfully')], 200);
    }

    public function changeStatus($id){
        $gallery = ProductGallery::find($id);
        if (! $gallery || ! $this->findSellerProductById($gallery->product_id)) {
            return response()->json(trans('Something went wrong'), 403);
        }

        if ($gallery->status == 1) {
            $gallery->status = 0;
            $gallery->save();
            $message = trans('Inactive Successfully');
        } else {
            $gallery->status = 1;
            $gallery->save();
            $message = trans('Active Successfully');
        }

        return response()->json($message);
    }
}
