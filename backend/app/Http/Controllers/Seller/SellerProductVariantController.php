<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Concerns\AuthorizesSellerProduct;
use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use App\Models\ProductVariantItem;
use App\Models\ShoppingCartVariant;

class SellerProductVariantController extends Controller
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
            return $this->errorGenerate();
        }

        $variants = ProductVariant::with('variantItems')->where('product_id', $productId)->get();

        return response()->json(['variants' => $variants, 'product' => $product], 200);
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required',
            'product_id' => 'required',
            'status' => 'required',
        ];
        $this->validate($request, $rules);

        $product = $this->findSellerProductById($request->product_id);
        if (! $product) {
            return $this->errorGenerate();
        }

        $variant = new ProductVariant();
        $variant->name = $request->name;
        $variant->product_id = $request->product_id;
        $variant->status = $request->status;
        $variant->save();

        return response()->json(['message' => trans('Created Successfully')], 200);
    }

    public function update(Request $request, $id){
        $rules = [
            'name' => 'required',
            'product_id' => 'required',
            'status' => 'required',
        ];
        $this->validate($request, $rules);

        $variant = ProductVariant::find($id);
        if (! $variant || ! $this->findSellerProductById($variant->product_id)) {
            return $this->errorGenerate();
        }

        $variant->name = $request->name;
        $variant->status = $request->status;
        $variant->save();

        ProductVariantItem::where('product_variant_id', $variant->id)->update(['product_variant_name' => $variant->name]);

        return response()->json(['message' => trans('Update Successfully')], 200);
    }

    public function destroy($id)
    {
        $variant = ProductVariant::find($id);
        if (! $variant || ! $this->findSellerProductById($variant->product_id)) {
            return $this->errorGenerate();
        }

        $variant->delete();
        ShoppingCartVariant::where('variant_id', $id)->delete();

        return response()->json(['message' => trans('Delete Successfully')], 200);
    }

    public function changeStatus($id){
        $variant = ProductVariant::find($id);
        if (! $variant || ! $this->findSellerProductById($variant->product_id)) {
            return response()->json(trans('Something went wrong'), 403);
        }

        if ($variant->status == 1) {
            $variant->status = 0;
            $variant->save();
            $message = trans('Inactive Successfully');
        } else {
            $variant->status = 1;
            $variant->save();
            $message = trans('Active Successfully');
        }

        return response()->json($message);
    }

    public function errorGenerate(){
        return response()->json(['message' => trans('Something went wrong')], 403);
    }

    public function show($id){
        $variant = ProductVariant::find($id);
        if (! $variant || ! $this->findSellerProductById($variant->product_id)) {
            return response()->json(['message' => trans('Something went wrong')], 403);
        }

        return response()->json(['variant' => $variant], 200);
    }
}
