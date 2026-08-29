<?php

namespace App\Services;

use App\Models\Product;

class InstallmentService
{
    /**
     * Sepetteki ürünlerin taksit durumunu kontrol eder.
     *
     * @param  array<int, array{price: float|int, qty: int}>  $cartItems
     * @return array{taksit_olabilir: bool, engellenen_urunler: array<int, array<string, mixed>>, mesaj: string, max_taksit: int}
     */
    public static function checkCartInstallmentStatus(array $cartItems): array
    {
        $service = app(CategoryInstallmentService::class);
        $productIds = array_keys($cartItems);

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->with(['category'])
            ->get()
            ->keyBy('id');

        $maxInstallment = 12;
        $engellenenUrunler = [];

        foreach ($cartItems as $productId => $item) {
            $product = $products->get($productId);
            if (! $product) {
                continue;
            }

            $perItemMax = $service->maxInstallmentForProduct($product);
            $maxInstallment = min($maxInstallment, $perItemMax);

            if ($perItemMax <= 1) {
                $engellenenUrunler[] = [
                    'urun_adi' => $product->name,
                    'fiyat' => ($item['price'] ?? 0) * ($item['qty'] ?? 1),
                    'kategori' => $product->category->name ?? 'Bilinmiyor',
                    'max_taksit' => 1,
                ];
            }
        }

        $maxInstallment = max(1, $maxInstallment);
        $taksitOlabilir = $maxInstallment > 1;

        return [
            'taksit_olabilir' => $taksitOlabilir,
            'engellenen_urunler' => $engellenenUrunler,
            'max_taksit' => $maxInstallment,
            'mesaj' => $taksitOlabilir
                ? ''
                : 'Sepette tek çekim zorunlu ürün bulunmaktadır. Tüm sipariş tek çekim ödenmelidir.',
        ];
    }

    public static function isProductInstallmentEnabled(int $productId): bool
    {
        $product = Product::query()->with(['category'])->find($productId);
        if (! $product) {
            return false;
        }

        return app(CategoryInstallmentService::class)->maxInstallmentForProduct($product) > 1;
    }

    /**
     * @param  array<int, array{price: float|int, qty: int}>  $cartItems
     * @return array{taksit_olan_urunler: array<int, array<string, mixed>>, taksit_sayisi: int}
     */
    public static function getInstallmentProducts(array $cartItems): array
    {
        $service = app(CategoryInstallmentService::class);
        $cartPayload = [];
        foreach ($cartItems as $productId => $item) {
            $cartPayload[] = [
                'product_id' => $productId,
                'qty' => $item['qty'] ?? 1,
            ];
        }

        $enabled = $service->enabledInstallmentsForCart($cartPayload);
        $maxTaksit = max($enabled);

        $products = Product::query()
            ->whereIn('id', array_keys($cartItems))
            ->with(['category'])
            ->get()
            ->keyBy('id');

        $taksitOlanUrunler = [];
        foreach ($cartItems as $productId => $item) {
            $product = $products->get($productId);
            if (! $product) {
                continue;
            }

            if ($service->maxInstallmentForProduct($product) <= 1) {
                continue;
            }

            $taksitOlanUrunler[] = [
                'urun_adi' => $product->name,
                'fiyat' => ($item['price'] ?? 0) * ($item['qty'] ?? 1),
                'kategori' => $product->category->name ?? 'Bilinmiyor',
                'max_taksit' => $service->maxInstallmentForProduct($product),
            ];
        }

        return [
            'taksit_olan_urunler' => $taksitOlanUrunler,
            'taksit_sayisi' => $maxTaksit,
        ];
    }
}
