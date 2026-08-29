<?php

namespace App\Http\Controllers\WEB\Seller;

use App\Http\Controllers\Concerns\AuthorizesSellerProduct;
use App\Http\Controllers\Controller;
use App\Models\ProductGallery;
use App\Models\Product;
use App\Services\ProductImageStorage;
use Illuminate\Http\Request;
use File;
use Throwable;

class SellerProductGalleryController extends Controller
{
    use AuthorizesSellerProduct;

    public function __construct()
    {
        $this->middleware('auth:web');
    }

    public function index($productId)
    {
        $product = $this->findSellerProduct($productId);
        if (! $product) {
            return $this->denySellerProductAccess();
        }

        $gallery = ProductGallery::where('product_id', $productId)->get();

        return view('seller.product_image_gallery', compact('gallery', 'product'));
    }

    public function store(Request $request)
    {
        $rules = [
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,jpg,png,webp|max:5120',
            'product_id' => 'required',
        ];
        $customMessages = [
            'images.required' => trans('admin_validation.Images is required'),
            'product_id.required' => trans('admin_validation.Product is required'),
        ];
        $this->validate($request, $rules, $customMessages);

        $product = $this->findSellerProductById($request->product_id);
        if (! $product) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['message' => trans('admin_validation.Something went wrong')], 403);
            }

            return $this->denySellerProductAccess();
        }

        if ($request->images) {
            $storage = app(ProductImageStorage::class);
            foreach ($request->images as $image) {
                try {
                    $image_name = $storage->store($image, 'Gallery');
                    $gallery = new ProductGallery();
                    $gallery->product_id = $request->product_id;
                    $gallery->image = $image_name;
                    $gallery->save();
                } catch (Throwable $e) {
                    if (request()->ajax() || request()->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => $e->getMessage() ?: 'Görsel yüklenemedi.',
                        ], 500);
                    }

                    return redirect()->back()->with([
                        'messege' => $e->getMessage() ?: 'Görsel yüklenemedi.',
                        'alert-type' => 'error',
                    ]);
                }
            }

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Görseller yüklendi.']);
            }
            $notification = trans('admin_validation.Uploaded Successfully');
            $notification = ['messege' => $notification, 'alert-type' => 'success'];

            return redirect()->back()->with($notification);
        }

        return redirect()->back();
    }

    public function destroy($id)
    {
        $gallery = ProductGallery::find($id);
        if (! $gallery) {
            return response()->json(['message' => trans('admin_validation.Something went wrong')], 404);
        }

        $product = $this->findSellerProductById($gallery->product_id);
        if (! $product) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['message' => trans('admin_validation.Something went wrong')], 403);
            }

            return $this->denySellerProductAccess();
        }

        $old_image = $gallery->image;
        $gallery->delete();
        if ($old_image && File::exists(public_path().'/'.$old_image)) {
            unlink(public_path().'/'.$old_image);
        }

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Görsel silindi.']);
        }
        $notification = trans('admin_validation.Delete Successfully');
        $notification = ['messege' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }

    public function changeStatus($id){
        $gallery = ProductGallery::find($id);
        if (! $gallery || ! $this->findSellerProductById($gallery->product_id)) {
            return response()->json(trans('admin_validation.Something went wrong'), 403);
        }

        if ($gallery->status == 1) {
            $gallery->status = 0;
            $gallery->save();
            $message = trans('admin_validation.Inactive Successfully');
        } else {
            $gallery->status = 1;
            $gallery->save();
            $message = trans('admin_validation.Active Successfully');
        }

        return response()->json($message);
    }
}
