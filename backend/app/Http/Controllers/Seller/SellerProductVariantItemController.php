<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Concerns\AuthorizesSellerProduct;
use App\Http\Controllers\Controller;
use App\Models\ProductVariantItem;
use Illuminate\Http\Request;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\ShoppingCartVariant;

class SellerProductVariantItemController extends Controller
{
    use AuthorizesSellerProduct;

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(Request $request)
    {
        if (! $request->product_id || ! $request->variant_id) {
            return $this->existingDataError();
        }

        $product = $this->findSellerProductById($request->product_id);
        if (! $product) {
            return $this->existingDataError();
        }

        $variant = ProductVariant::find($request->variant_id);
        if (! $variant || (int) $variant->product_id !== (int) $product->id) {
            return $this->existingDataError();
        }

        $variantItems = ProductVariantItem::where([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
        ])->get();
        $setting = Setting::first();

        return response()->json(['variantItems' => $variantItems, 'variant' => $variant, 'product' => $product], 200);
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required',
            'product_id' => 'required',
            'variant_id' => 'required',
            'price' => 'required|numeric',
            'status' => 'required',
        ];
        $this->validate($request, $rules);

        $product = $this->findSellerProductById($request->product_id);
        if (! $product) {
            return $this->existingDataError();
        }

        $variant = ProductVariant::find($request->variant_id);
        if (! $variant || (int) $variant->product_id !== (int) $product->id) {
            return $this->existingDataError();
        }

        $variantItem = new ProductVariantItem();
        $variantItem->product_id = $request->product_id;
        $variantItem->product_variant_id = $request->variant_id;
        $variantItem->name = $request->name;
        $variantItem->price = $request->price;
        $variantItem->product_variant_name = $variant->name;
        $variantItem->status = $request->status;
        $variantItem->save();

        return response()->json(['message' => trans('Created Successfully')], 200);
    }

    public function update(Request $request, $variantItemId){
        $rules = [
            'name' => 'required',
            'product_id' => 'required',
            'variant_id' => 'required',
            'price' => 'required|numeric',
            'status' => 'required',
        ];
        $this->validate($request, $rules);

        $product = $this->findSellerProductById($request->product_id);
        if (! $product) {
            return $this->existingDataError();
        }

        $variant = ProductVariant::find($request->variant_id);
        if (! $variant || (int) $variant->product_id !== (int) $product->id) {
            return $this->existingDataError();
        }

        $variantItem = ProductVariantItem::find($variantItemId);
        if (! $variantItem || (int) $variantItem->product_id !== (int) $product->id) {
            return $this->existingDataError();
        }

        $variantItem->product_id = $request->product_id;
        $variantItem->product_variant_id = $request->variant_id;
        $variantItem->name = $request->name;
        $variantItem->price = $request->price;
        $variantItem->status = $request->status;
        $variantItem->save();

        return response()->json(['message' => trans('Update Successfully')], 200);
    }

    public function show($id){
        $variantItem = ProductVariantItem::find($id);
        if (! $variantItem || ! $this->findSellerProductById($variantItem->product_id)) {
            return response()->json(['message' => trans('Something went wrong')], 403);
        }

        return response()->json(['variantItem' => $variantItem], 200);
    }

    public function destroy($id)
    {
        $variantItem = ProductVariantItem::find($id);
        if (! $variantItem) {
            return $this->existingDataError();
        }

        $product = $this->findSellerProductById($variantItem->product_id);
        $variant = ProductVariant::find($variantItem->product_variant_id);
        if (! $product || ! $variant || (int) $variant->product_id !== (int) $product->id) {
            return $this->existingDataError();
        }

        $variantItem->delete();
        ShoppingCartVariant::where('variant_item_id', $id)->delete();

        return response()->json(['message' => trans('Delete Successfully')], 200);
    }

    public function changeStatus($id){
        $variantItem = ProductVariantItem::find($id);
        if (! $variantItem || ! $this->findSellerProductById($variantItem->product_id)) {
            return response()->json(trans('Something went wrong'), 403);
        }

        if ($variantItem->status == 1) {
            $variantItem->status = 0;
            $variantItem->save();
            $message = trans('Inactive Successfully');
        } else {
            $variantItem->status = 1;
            $variantItem->save();
            $message = trans('Active Successfully');
        }

        return response()->json($message);
    }

    public function existingDataError(){
        return response()->json(['message' => trans('Something went wrong')], 403);
    }
}
