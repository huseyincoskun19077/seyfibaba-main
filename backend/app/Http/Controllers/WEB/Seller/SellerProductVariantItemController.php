<?php

namespace App\Http\Controllers\WEB\Seller;

use App\Http\Controllers\Concerns\AuthorizesSellerProduct;
use App\Http\Controllers\Controller;
use App\Models\ProductVariantItem;
use Illuminate\Http\Request;
use App\Models\ProductVariant;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShoppingCartVariant;

class SellerProductVariantItemController extends Controller
{
    use AuthorizesSellerProduct;

    public function __construct()
    {
        $this->middleware('auth:web');
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

        return view('seller.variant_item', compact('variantItems', 'product', 'variant', 'setting'));
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
        $customMessages = [
            'name.required' => trans('admin_validation.Name is required'),
            'product_id.required' => trans('admin_validation.Product is required'),
            'variant_id.required' => trans('admin_validation.Variant is required'),
            'price.required' => trans('admin_validation.Price is required'),
        ];
        $this->validate($request, $rules, $customMessages);

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

        $notification = trans('admin_validation.Created Successfully');
        $notification = ['messege' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }

    public function update(Request $request, $variantItemId){
        $rules = [
            'name' => 'required',
            'product_id' => 'required',
            'variant_id' => 'required',
            'price' => 'required|numeric',
            'status' => 'required',
        ];
        $customMessages = [
            'name.required' => trans('admin_validation.Name is required'),
            'product_id.required' => trans('admin_validation.Product is required'),
            'variant_id.required' => trans('admin_validation.Variant is required'),
            'price.required' => trans('admin_validation.Price is required'),
        ];
        $this->validate($request, $rules, $customMessages);

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

        $notification = trans('admin_validation.Update Successfully');
        $notification = ['messege' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }

    public function show($id){
        $variantItem = ProductVariantItem::find($id);
        if (! $variantItem || ! $this->findSellerProductById($variantItem->product_id)) {
            return response()->json(['message' => trans('admin_validation.Something went wrong')], 403);
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

        $notification = trans('admin_validation.Delete Successfully');
        $notification = ['messege' => $notification, 'alert-type' => 'success'];

        return redirect()->back()->with($notification);
    }

    public function changeStatus($id){
        $variantItem = ProductVariantItem::find($id);
        if (! $variantItem || ! $this->findSellerProductById($variantItem->product_id)) {
            return response()->json(trans('admin_validation.Something went wrong'), 403);
        }

        if ($variantItem->status == 1) {
            $variantItem->status = 0;
            $variantItem->save();
            $message = trans('admin_validation.Inactive Successfully');
        } else {
            $variantItem->status = 1;
            $variantItem->save();
            $message = trans('admin_validation.Active Successfully');
        }

        return response()->json($message);
    }

    public function existingDataError(){
        $notification = trans('admin_validation.Something went wrong');
        $notification = ['messege' => $notification, 'alert-type' => 'error'];

        return redirect()->route('seller.product.index')->with($notification);
    }
}
