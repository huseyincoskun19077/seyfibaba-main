<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SellerInventoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('checkseller');
    }

    public function index()
    {
        $seller = Auth::guard('api')->user()->seller;
        $products = Product::query()
            ->where('vendor_id', $seller->id)
            ->orderByDesc('id')
            ->get(['id', 'name', 'slug', 'sku', 'qty', 'thumb_image', 'status', 'price']);

        return response()->json(['products' => $products]);
    }

    public function stockout()
    {
        $seller = Auth::guard('api')->user()->seller;
        $products = Product::query()
            ->where('vendor_id', $seller->id)
            ->where('qty', '<=', 0)
            ->orderByDesc('id')
            ->get(['id', 'name', 'slug', 'sku', 'qty', 'thumb_image', 'status', 'price']);

        return response()->json(['products' => $products]);
    }

    public function history($productId)
    {
        $seller = Auth::guard('api')->user()->seller;
        $product = Product::query()
            ->where('id', $productId)
            ->where('vendor_id', $seller->id)
            ->first();

        if (! $product) {
            return response()->json(['message' => 'Ürün bulunamadı'], 404);
        }

        $histories = Inventory::query()
            ->where('product_id', $product->id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'product' => $product,
            'histories' => $histories,
        ]);
    }

    public function addStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'stock_in' => 'required|integer|min:1|max:999999',
        ]);

        $seller = Auth::guard('api')->user()->seller;
        $product = Product::query()
            ->where('id', $request->product_id)
            ->where('vendor_id', $seller->id)
            ->first();

        if (! $product) {
            return response()->json(['message' => 'Ürün bulunamadı'], 404);
        }

        $inventory = new Inventory();
        $inventory->product_id = $product->id;
        $inventory->stock_in = (int) $request->stock_in;
        $inventory->save();

        $product->qty = (int) $product->qty + (int) $request->stock_in;
        $product->save();

        return response()->json([
            'message' => 'Stok eklendi',
            'product' => $product->fresh(),
            'inventory' => $inventory,
        ]);
    }

    public function deleteStock($id)
    {
        $seller = Auth::guard('api')->user()->seller;
        $inventory = Inventory::find($id);
        if (! $inventory) {
            return response()->json(['message' => 'Kayıt bulunamadı'], 404);
        }

        $product = Product::query()
            ->where('id', $inventory->product_id)
            ->where('vendor_id', $seller->id)
            ->first();

        if (! $product) {
            return response()->json(['message' => 'Ürün bulunamadı'], 404);
        }

        $updateQty = (int) $product->qty - (int) $inventory->stock_in;
        $product->qty = $updateQty < 0 ? 0 : $updateQty;
        $product->save();
        $inventory->delete();

        return response()->json([
            'message' => 'Stok kaydı silindi',
            'product' => $product->fresh(),
        ]);
    }
}
