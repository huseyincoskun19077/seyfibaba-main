<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Concerns\HandlesOrderCargo;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\GdeliveryService;

class OrderCargoController extends Controller
{
    use HandlesOrderCargo;

    public function __construct(private GdeliveryService $gdeliveryService)
    {
        $this->middleware('auth:admin');
    }

    protected function getGdeliveryService(): GdeliveryService
    {
        return $this->gdeliveryService;
    }

    protected function resolveOrderForCargo(int $orderId): Order
    {
        return Order::findOrFail($orderId);
    }

    protected function cargoCreatedBy(): array
    {
        return [
            'type' => 'admin',
            'id' => auth('admin')->id() ?? 0,
        ];
    }
}
