<?php

namespace App\Http\Controllers\WEB\Seller;

use App\Http\Controllers\Concerns\HandlesOrderCargo;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\CargoShipment;
use App\Services\GdeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SellerOrderCargoController extends Controller
{
    use HandlesOrderCargo;

    public function __construct(private GdeliveryService $gdeliveryService)
    {
        $this->middleware('auth:web');
    }

    protected function getGdeliveryService(): GdeliveryService
    {
        return $this->gdeliveryService;
    }

    protected function resolveOrderForCargo(int $orderId): Order
    {
        $seller = Auth::guard('web')->user()->seller;

        $order = Order::query()
            ->where('id', $orderId)
            ->whereHas('orderProducts', function ($query) use ($seller) {
                $query->where('seller_id', $seller->id);
            })
            ->first();

        if (! $order) {
            abort(403, 'Bu siparişe erişim yetkiniz yok.');
        }

        return $order;
    }

    protected function cargoCreatedBy(): array
    {
        $seller = Auth::guard('web')->user()->seller;

        return [
            'type' => 'seller',
            'id' => (int) $seller->id,
        ];
    }

    public function bulkImport(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $seller = Auth::guard('web')->user()->seller;
        $file = $request->file('csv_file');
        $handle = fopen($file->getPathname(), 'r');
        
        $results = [
            'success' => [],
            'failed' => [],
        ];
        
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return response()->json(['message' => 'CSV dosyası boş'], 400);
        }
        
        $expectedHeaders = ['order_id', 'tracking_number'];
        $headerMap = array_map('trim', array_map('strtolower', $headers));
        
        $orderIdCol = array_search('order_id', $headerMap);
        $trackingCol = array_search('tracking_number', $headerMap);
        
        if ($orderIdCol === false || $trackingCol === false) {
            fclose($handle);
            return response()->json([
                'message' => 'CSV başlukları order_id ve tracking_number olmalı'
            ], 400);
        }
        
        $rowNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            
            $orderId = isset($row[$orderIdCol]) ? trim($row[$orderIdCol]) : null;
            $trackingNumber = isset($row[$trackingCol]) ? trim($row[$trackingCol]) : null;
            
            if (!$orderId || !$trackingNumber) {
                $results['failed'][] = [
                    'row' => $rowNumber,
                    'reason' => 'Eksik veri',
                ];
                continue;
            }
            
            try {
                $order = Order::where('id', $orderId)
                    ->whereHas('orderProducts', function ($q) use ($seller) {
                        $q->where('seller_id', $seller->id);
                    })->first();
                
                if (!$order) {
                    $results['failed'][] = [
                        'row' => $rowNumber,
                        'order_id' => $orderId,
                        'reason' => 'Sipariş bulunamadı veya erişim yetkiniz yok',
                    ];
                    continue;
                }
                
                if ($order->order_status != 0) {
                    $results['failed'][] = [
                        'row' => $rowNumber,
                        'order_id' => $orderId,
                        'reason' => 'Sipariş zaten işlenmiş',
                    ];
                    continue;
                }
                
                CargoShipment::updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'tracking_number' => $trackingNumber,
                        'status' => 'created',
                        'created_by_type' => 'seller',
                        'created_by_id' => $seller->id,
                    ]
                );
                
                $order->update([
                    'order_status' => 1,
                    'order_approval_date' => now(),
                ]);
                
                $results['success'][] = [
                    'row' => $rowNumber,
                    'order_id' => $orderId,
                    'tracking_number' => $trackingNumber,
                ];
                
            } catch (\Exception $e) {
                Log::error("Bulk cargo import error: " . $e->getMessage());
                $results['failed'][] = [
                    'row' => $rowNumber,
                    'order_id' => $orderId,
                    'reason' => 'Sistem hatası',
                ];
            }
        }
        
        fclose($handle);
        
        return response()->json([
            'message' => 'İşlem tamamlandı',
            'results' => $results,
        ]);
    }
}
