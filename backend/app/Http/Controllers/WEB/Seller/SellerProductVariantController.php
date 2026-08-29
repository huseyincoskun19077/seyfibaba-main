<?php

namespace App\Http\Controllers\WEB\Seller;

use App\Http\Controllers\Concerns\AuthorizesSellerProduct;
use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductVariantItem;
use App\Models\ShoppingCartVariant;

class SellerProductVariantController extends Controller
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

        $variants = ProductVariant::with('variantItems')->where('product_id', $productId)->get();

        return view('seller.variant', compact('variants', 'product'));
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required',
            'product_id' => 'required',
            'status' => 'required',
        ];
        $customMessages = [
            'name.required' => trans('admin_validation.Name is required'),
            'product_id.required' => trans('admin_validation.Product is required'),
        ];
        $this->validate($request, $rules, $customMessages);

        $product = $this->findSellerProductById($request->product_id);
        if (! $product) {
            return $this->denySellerProductAccess();
        }

        $variant = new ProductVariant();
        $variant->name = $request->name;
        $variant->product_id = $request->product_id;
        $variant->status = $request->status;
        $variant->save();

        $notification = trans('admin_validation.Created Successfully');
        $notification = ['messege' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }

    public function update(Request $request, $id){
        $rules = [
            'name' => 'required',
            'product_id' => 'required',
            'status' => 'required',
        ];
        $customMessages = [
            'name.required' => trans('admin_validation.Name is required'),
            'product_id.required' => trans('admin_validation.Product is required'),
        ];
        $this->validate($request, $rules, $customMessages);

        $variant = ProductVariant::find($id);
        if (! $variant || ! $this->findSellerProductById($variant->product_id)) {
            return $this->denySellerProductAccess();
        }

        $variant->name = $request->name;
        $variant->status = $request->status;
        $variant->save();

        ProductVariantItem::where('product_variant_id', $variant->id)->update(['product_variant_name' => $variant->name]);

        $notification = trans('admin_validation.Update Successfully');
        $notification = ['messege' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }

    public function destroy($id)
    {
        $variant = ProductVariant::find($id);
        if (! $variant || ! $this->findSellerProductById($variant->product_id)) {
            return $this->denySellerProductAccess();
        }

        $variant->delete();
        ShoppingCartVariant::where('variant_id', $id)->delete();

        $notification = trans('admin_validation.Delete Successfully');
        $notification = ['messege' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }

    public function changeStatus($id){
        $variant = ProductVariant::find($id);
        if (! $variant || ! $this->findSellerProductById($variant->product_id)) {
            return response()->json(trans('admin_validation.Something went wrong'), 403);
        }

        if ($variant->status == 1) {
            $variant->status = 0;
            $variant->save();
            $message = trans('admin_validation.Inactive Successfully');
        } else {
            $variant->status = 1;
            $variant->save();
            $message = trans('admin_validation.Active Successfully');
        }

        return response()->json($message);
    }

    public function show($id){
        $variant = ProductVariant::find($id);
        if (! $variant || ! $this->findSellerProductById($variant->product_id)) {
            return response()->json(['message' => trans('admin_validation.Something went wrong')], 403);
        }

        return response()->json(['variant' => $variant], 200);
    }
}
