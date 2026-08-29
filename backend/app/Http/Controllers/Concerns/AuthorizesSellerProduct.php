<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

trait AuthorizesSellerProduct
{
    protected function currentSeller(): ?Vendor
    {
        $user = auth('web')->user() ?? auth('api')->user() ?? auth()->user();

        return $user?->seller;
    }

    protected function sellerHasApprovedKyc(?Vendor $seller = null): bool
    {
        $seller ??= $this->currentSeller();

        return $seller && $seller->kyc_status === 'approved';
    }

    protected function findSellerProduct(int|string $id, array $with = []): ?Product
    {
        $seller = $this->currentSeller();
        if (! $seller) {
            return null;
        }

        $query = Product::query()->where('id', $id)->where('vendor_id', $seller->id);
        if ($with !== []) {
            $query->with($with);
        }

        return $query->first();
    }

    protected function denySellerProductAccess(): RedirectResponse|JsonResponse
    {
        if (request()->expectsJson() || auth('api')->check()) {
            return response()->json(['message' => trans('admin_validation.Something went wrong')], 403);
        }

        return redirect()
            ->route('seller.product.index')
            ->with(['messege' => trans('admin_validation.Something went wrong'), 'alert-type' => 'error']);
    }

    protected function findSellerProductById(int|string $productId): ?Product
    {
        $seller = $this->currentSeller();
        if (! $seller) {
            return null;
        }

        return Product::query()
            ->where('id', $productId)
            ->where('vendor_id', $seller->id)
            ->first();
    }
}
