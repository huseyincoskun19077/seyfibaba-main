<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Shipping;
use App\Models\Vendor;
use App\Models\VendorShippingTier;
use Illuminate\Support\Collection;

/**
 * Pazaryeri: satıcı bazlı kademeli kargo.
 * Her satıcının kendi sepet alt toplamına göre kademe uygulanır; toplam kargo = satıcı kargoları toplamı.
 */
class VendorShippingService
{
    /**
     * @param  Collection|array  $cartLines  Her satır: product_id, qty, isteğe bağlı product / unit_price
     * @return array{
     *   groups: list<array>,
     *   total_shipping_fee: float,
     *   product_subtotal: float
     * }
     */
    public function calculateForCart($cartLines, ?CartPriceService $priceService = null): array
    {
        $priceService = $priceService ?? app(CartPriceService::class);
        $lines = collect($cartLines);
        $groupsMap = [];
        $productSubtotal = 0.0;

        foreach ($lines as $line) {
            $line = is_array($line) ? $line : (array) $line;
            $productId = (int) ($line['product_id'] ?? $line['product']['id'] ?? 0);
            $qty = max(1, (int) ($line['qty'] ?? 1));
            if ($productId <= 0) {
                continue;
            }

            $product = $line['product'] ?? null;
            if (! $product instanceof Product) {
                $product = Product::query()
                    ->select('id', 'vendor_id', 'price', 'offer_price', 'name', 'slug', 'thumb_image')
                    ->find($productId);
            }
            if (! $product) {
                continue;
            }

            $vendorId = (int) ($product->vendor_id ?? $line['product']['vendor_id'] ?? 0);
            $unit = $priceService->resolveUnitPriceFromCartLine($product, $line);
            $lineTotal = round($unit * $qty, 2);
            $productSubtotal += $lineTotal;

            if (! isset($groupsMap[$vendorId])) {
                $groupsMap[$vendorId] = [
                    'vendor_id' => $vendorId,
                    'shop_name' => null,
                    'subtotal' => 0.0,
                    'items' => [],
                ];
            }
            $groupsMap[$vendorId]['subtotal'] += $lineTotal;
            $groupsMap[$vendorId]['items'][] = [
                'product_id' => $productId,
                'qty' => $qty,
                'line_total' => $lineTotal,
            ];
        }

        $vendorIds = array_keys($groupsMap);
        $vendors = Vendor::query()
            ->whereIn('id', array_filter($vendorIds))
            ->get(['id', 'shop_name'])
            ->keyBy('id');

        $tiersByVendor = VendorShippingTier::query()
            ->whereIn('vendor_id', array_filter($vendorIds))
            ->orderBy('sort_order')
            ->orderBy('min_amount')
            ->get()
            ->groupBy('vendor_id');

        $groups = [];
        $totalShipping = 0.0;

        foreach ($groupsMap as $vendorId => $group) {
            $vendor = $vendors->get($vendorId);
            $shopName = $vendor?->shop_name
                ?: ($vendorId === 0 ? 'Platform' : 'Satıcı');
            $subtotal = round((float) $group['subtotal'], 2);
            $tiers = $tiersByVendor->get($vendorId, collect());
            $fee = $this->resolveFee($subtotal, $tiers);
            $totalShipping += $fee;

            $nextFree = $this->amountUntilFree($subtotal, $tiers);

            $groups[] = [
                'vendor_id' => (int) $vendorId,
                'shop_name' => $shopName,
                'subtotal' => $subtotal,
                'shipping_fee' => round($fee, 2),
                'is_free_shipping' => $fee <= 0.00001,
                'amount_until_free' => $nextFree,
                'items' => $group['items'],
            ];
        }

        usort($groups, static fn ($a, $b) => strcmp($a['shop_name'], $b['shop_name']));

        return [
            'groups' => $groups,
            'total_shipping_fee' => round($totalShipping, 2),
            'product_subtotal' => round($productSubtotal, 2),
        ];
    }

    /**
     * @param  Collection<int, VendorShippingTier>  $tiers
     */
    public function resolveFee(float $subtotal, $tiers): float
    {
        $tiers = collect($tiers)->sortBy([
            ['sort_order', 'asc'],
            ['min_amount', 'asc'],
        ])->values();

        if ($tiers->isEmpty()) {
            return $this->fallbackPlatformFee($subtotal);
        }

        foreach ($tiers as $tier) {
            $min = (float) $tier->min_amount;
            $max = $tier->max_amount;
            if ($subtotal + 0.00001 < $min) {
                continue;
            }
            if ($max !== null && $subtotal > (float) $max + 0.00001) {
                continue;
            }

            return (float) $tier->shipping_fee;
        }

        // Hiçbir kademeye uymadıysa en yüksek min'li kademenin ücretini kullan
        $last = $tiers->last();

        return $last ? (float) $last->shipping_fee : $this->fallbackPlatformFee($subtotal);
    }

    /**
     * @param  Collection<int, VendorShippingTier>  $tiers
     */
    public function amountUntilFree(float $subtotal, $tiers): ?float
    {
        $tiers = collect($tiers);
        $freeTier = $tiers
            ->filter(fn ($t) => (float) $t->shipping_fee <= 0.00001)
            ->sortBy('min_amount')
            ->first();

        if (! $freeTier) {
            return null;
        }

        $need = (float) $freeTier->min_amount - $subtotal;

        return $need > 0.01 ? round($need, 2) : null;
    }

    protected function fallbackPlatformFee(float $subtotal): float
    {
        // Admin global fiyat kademeleri (type=price varsayımı: condition_from/to)
        $rules = Shipping::query()
            ->where(function ($q) {
                $q->where('type', 1)->orWhere('shipping_rule', 'like', '%price%');
            })
            ->orderByRaw('CAST(condition_from AS DECIMAL(12,2)) ASC')
            ->get();

        if ($rules->isEmpty()) {
            $rules = Shipping::query()
                ->orderByRaw('CAST(condition_from AS DECIMAL(12,2)) ASC')
                ->get();
        }

        foreach ($rules as $rule) {
            $from = (float) $rule->condition_from;
            $to = (float) $rule->condition_to;
            if ($subtotal < $from) {
                continue;
            }
            if ($to > 0 && $subtotal > $to) {
                continue;
            }
            // condition_to = -1 → sınırsız
            return (float) $rule->shipping_fee;
        }

        return 0.0;
    }

    /**
     * Satıcı için örnek varsayılan kademeler (henüz yoksa).
     *
     * @return list<array{min_amount: float, max_amount: float|null, shipping_fee: float, sort_order: int}>
     */
    public static function defaultTierTemplate(): array
    {
        return [
            ['min_amount' => 0, 'max_amount' => 499.99, 'shipping_fee' => 100, 'sort_order' => 1],
            ['min_amount' => 500, 'max_amount' => 999.99, 'shipping_fee' => 100, 'sort_order' => 2],
            ['min_amount' => 1000, 'max_amount' => null, 'shipping_fee' => 0, 'sort_order' => 3],
        ];
    }
}
