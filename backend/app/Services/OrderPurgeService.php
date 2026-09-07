<?php

namespace App\Services;

use App\Models\CargoShipment;
use App\Models\CommissionLedger;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderProduct;
use App\Models\OrderProductVariant;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sipariş ve yalnızca siparişe bağlı kayıtları siler.
 * users / vendors / products / categories ASLA silinmez.
 */
class OrderPurgeService
{
    /**
     * @return array<string, int>
     */
    public function purgeOrder(Order $order): array
    {
        $counts = [
            'return_images' => 0,
            'returns' => 0,
            'commission_ledger' => 0,
            'variants' => 0,
            'order_products' => 0,
            'addresses' => 0,
            'cargo' => 0,
            'delivery_messages' => 0,
            'delivery_reviews' => 0,
            'user_activities' => 0,
            'orders' => 0,
            'product_reviews_unlinked' => 0,
            'legal_consents_unlinked' => 0,
        ];

        DB::transaction(function () use ($order, &$counts) {
            $orderId = (int) $order->id;

            $orderProductIds = OrderProduct::query()
                ->where('order_id', $orderId)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $returnIds = ReturnRequest::query()
                ->where('order_id', $orderId)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($returnIds !== [] && Schema::hasTable('return_request_images')) {
                $counts['return_images'] = ReturnRequestImage::query()
                    ->whereIn('return_request_id', $returnIds)
                    ->delete();
            }

            if ($returnIds !== []) {
                $counts['returns'] = ReturnRequest::query()
                    ->whereIn('id', $returnIds)
                    ->delete();
            } else {
                $counts['returns'] = ReturnRequest::query()
                    ->where('order_id', $orderId)
                    ->delete();
            }

            if ($orderProductIds !== []) {
                $counts['commission_ledger'] += CommissionLedger::query()
                    ->whereIn('order_product_id', $orderProductIds)
                    ->delete();

                $counts['variants'] = OrderProductVariant::query()
                    ->whereIn('order_product_id', $orderProductIds)
                    ->delete();
            }

            $counts['commission_ledger'] += CommissionLedger::query()
                ->where('order_id', $orderId)
                ->delete();

            if (Schema::hasTable('cargo_shipments') && Schema::hasColumn('cargo_shipments', 'order_id')) {
                $counts['cargo'] = CargoShipment::query()
                    ->where('order_id', $orderId)
                    ->delete();
            }

            if (Schema::hasTable('delivery_messages') && Schema::hasColumn('delivery_messages', 'order_id')) {
                $counts['delivery_messages'] = DB::table('delivery_messages')
                    ->where('order_id', $orderId)
                    ->delete();
            }

            if (Schema::hasTable('delivery_man_reviews') && Schema::hasColumn('delivery_man_reviews', 'order_id')) {
                $counts['delivery_reviews'] = DB::table('delivery_man_reviews')
                    ->where('order_id', $orderId)
                    ->delete();
            }

            if (Schema::hasTable('user_activities') && Schema::hasColumn('user_activities', 'order_id')) {
                $counts['user_activities'] = DB::table('user_activities')
                    ->where('order_id', $orderId)
                    ->delete();
            }

            if (Schema::hasTable('product_reviews') && Schema::hasColumn('product_reviews', 'order_id')) {
                $counts['product_reviews_unlinked'] = DB::table('product_reviews')
                    ->where('order_id', $orderId)
                    ->update(['order_id' => null]);
            }

            if (Schema::hasTable('legal_document_consents') && Schema::hasColumn('legal_document_consents', 'order_id')) {
                $counts['legal_consents_unlinked'] = DB::table('legal_document_consents')
                    ->where('order_id', $orderId)
                    ->update(['order_id' => null]);
            }

            $counts['addresses'] = OrderAddress::query()
                ->where('order_id', $orderId)
                ->delete();

            $counts['order_products'] = OrderProduct::query()
                ->where('order_id', $orderId)
                ->delete();

            $order->delete();
            $counts['orders'] = 1;
        });

        return $counts;
    }

    /**
     * @param  iterable<int>|null  $orderIds  null = tüm siparişler
     * @return array{orders: int, details: array<string, int>}
     */
    public function purgeMany(?iterable $orderIds = null): array
    {
        $query = Order::query()->orderBy('id');
        if ($orderIds !== null) {
            $ids = collect($orderIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
            if ($ids->isEmpty()) {
                return ['orders' => 0, 'details' => []];
            }
            $query->whereIn('id', $ids->all());
        }

        $totals = [];
        $deleted = 0;

        $query->chunkById(100, function ($orders) use (&$totals, &$deleted) {
            foreach ($orders as $order) {
                try {
                    $counts = $this->purgeOrder($order);
                    $deleted += $counts['orders'];
                    foreach ($counts as $key => $value) {
                        $totals[$key] = ($totals[$key] ?? 0) + (int) $value;
                    }
                } catch (\Throwable $e) {
                    \Log::error('Order purge failed', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            }
        });

        return ['orders' => $deleted, 'details' => $totals];
    }
}
