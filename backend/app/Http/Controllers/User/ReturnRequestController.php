<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestImage;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;

class ReturnRequestController extends Controller
{
    private const IMAGE_REQUIRED_REASONS = [
        'defective',
        'damaged_in_shipping',
        'wrong_item',
    ];

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(Request $request)
    {
        $user = Auth::guard('api')->user();
        $query = ReturnRequest::with(['order', 'orderProduct.product', 'images'])
            ->where('user_id', $user->id)
            ->when($request->filled('status'), fn ($builder) => $builder->where('status', $request->status))
            ->when($request->filled('reason'), fn ($builder) => $builder->where('reason', $request->reason))
            ->when($request->filled('date_from'), fn ($builder) => $builder->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($builder) => $builder->whereDate('created_at', '<=', $request->date_to))
            ->when($request->filled('search'), function ($builder) use ($request) {
                $search = trim((string) $request->search);

                $builder->where(function ($nested) use ($search) {
                    $nested->where('reason', 'like', '%' . $search . '%')
                        ->orWhere('details', 'like', '%' . $search . '%')
                        ->orWhereHas('order', fn ($orderQuery) => $orderQuery->where('order_id', 'like', '%' . $search . '%'))
                        ->orWhereHas('orderProduct', function ($orderProductQuery) use ($search) {
                            $orderProductQuery->where('product_name', 'like', '%' . $search . '%')
                                ->orWhereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', '%' . $search . '%'));
                        });
                });
            });

        $returns = (clone $query)
            ->orderByDesc('id')
            ->paginate((int) $request->get('per_page', 20));

        $statsBaseQuery = ReturnRequest::where('user_id', $user->id)
            ->when($request->filled('date_from'), fn ($builder) => $builder->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($builder) => $builder->whereDate('created_at', '<=', $request->date_to));

        $stats = [
            'total' => (clone $statsBaseQuery)->count(),
            'pending' => (clone $statsBaseQuery)->where('status', ReturnRequest::STATUS_PENDING)->count(),
            'approved' => (clone $statsBaseQuery)->whereIn('status', [
                ReturnRequest::STATUS_SELLER_APPROVED,
                ReturnRequest::STATUS_ADMIN_APPROVED,
                ReturnRequest::STATUS_ITEM_RECEIVED,
            ])->count(),
            'refunded' => (clone $statsBaseQuery)->where('status', ReturnRequest::STATUS_REFUNDED)->count(),
            'rejected' => (clone $statsBaseQuery)->whereIn('status', [
                ReturnRequest::STATUS_SELLER_REJECTED,
                ReturnRequest::STATUS_ADMIN_REJECTED,
            ])->count(),
            'cancelled' => (clone $statsBaseQuery)->where('status', ReturnRequest::STATUS_USER_CANCELLED)->count(),
        ];

        return response()->json([
            'returns' => $returns,
            'stats' => $stats,
            'filters' => [
                'status' => $request->get('status'),
                'reason' => $request->get('reason'),
                'search' => $request->get('search'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
            ],
        ]);
    }

    public function returnableItems($id)
    {
        $user = Auth::guard('api')->user();
        $order = Order::with('orderProducts')
            ->where('user_id', $user->id)
            ->where(function ($query) use ($id) {
                $query->where('id', $id)
                    ->orWhere('order_id', $id);
            })
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $items = $order->orderProducts->map(function ($orderProduct) use ($order) {
            return $this->buildReturnableItemPayload($order, $orderProduct);
        });

        return response()->json([
            'order_id' => $order->order_id,
            'items' => $items,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_product_id' => 'required|exists:order_products,id',
            'reason' => 'required|string|max:255',
            'details' => 'nullable|string',
            'qty' => 'required|integer|min:1',
            'images' => 'nullable|array|max:3',
            'images.*' => 'image|max:2048',
        ]);

        $user = Auth::guard('api')->user();
        $orderProduct = OrderProduct::with(['order', 'seller'])->findOrFail($request->order_product_id);

        if ((int) $orderProduct->order->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $payload = $this->buildReturnableItemPayload($orderProduct->order, $orderProduct);
        if (!$payload['is_returnable']) {
            return response()->json(['message' => $payload['message']], 422);
        }

        if ((int) $request->qty > (int) $payload['max_returnable_qty']) {
            return response()->json(['message' => 'Quantity exceeds returnable quantity'], 422);
        }

        if (in_array($request->reason, self::IMAGE_REQUIRED_REASONS, true) && !$request->hasFile('images')) {
            return response()->json(['message' => 'At least one image is required for this reason'], 422);
        }

        $orderProduct->order->loadMissing('orderProducts');
        $refundHint = $orderProduct->order->suggestedReturnRefund(
            $orderProduct,
            (int) $request->qty
        );

        $return = ReturnRequest::create([
            'order_id' => $orderProduct->order_id,
            'user_id' => $user->id,
            'seller_id' => $orderProduct->seller_id,
            'order_product_id' => $orderProduct->id,
            'reason' => $request->reason,
            'details' => $request->details,
            'description' => $request->details,
            'qty' => $request->qty,
            'status' => ReturnRequest::STATUS_PENDING,
            'refund_amount' => $refundHint['refund_amount'],
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $imageName = 'return-' . time() . '-' . rand(100, 999) . '.' . $file->getClientOriginalExtension();
                $imagePath = 'uploads/return_images/' . $imageName;
                Image::make($file)->save(public_path($imagePath));

                ReturnRequestImage::create([
                    'return_request_id' => $return->id,
                    'image' => $imagePath,
                ]);
            }
        }

        try {
            \App\Helpers\MailHelper::setMailConfig();
            $vendor = \App\Models\Vendor::find($return->seller_id);
            $sellerUser = $vendor ? \App\Models\User::find($vendor->user_id) : null;
            if ($sellerUser && $sellerUser->email) {
                $productName = $orderProduct->product->name ?? 'Ürün';
                $content = "Yeni iade talebi oluşturuldu.\n\nSipariş No: {$orderProduct->order->order_id}\nÜrün: {$productName}\nAdet: {$return->qty}\nSebep: {$return->reason}\n\nSatıcı panelinizden talebi inceleyebilirsiniz.";
                \Mail::to($sellerUser->email)->send(new \App\Mail\ReturnRequestCreatedMail($content));
            }
        } catch (\Throwable $e) {
            \Log::warning('Return request seller mail failed', ['return_id' => $return->id, 'error' => $e->getMessage()]);
        }

        app(\App\Services\SellerPayoutService::class)->blockPayoutForReturn(
            $orderProduct->order,
            'İade talebi #'.$return->id
        );

        return response()->json([
            'message' => 'Return request submitted successfully',
            'return' => $return->load(['order', 'orderProduct', 'images']),
        ], 201);
    }

    public function cancel($id)
    {
        $user = Auth::guard('api')->user();
        $return = ReturnRequest::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$return) {
            return response()->json(['message' => 'Return request not found'], 404);
        }

        if ((int) $return->status !== ReturnRequest::STATUS_PENDING) {
            return response()->json(['message' => 'Only pending requests can be cancelled'], 422);
        }

        $return->update([
            'status' => ReturnRequest::STATUS_USER_CANCELLED,
            'rejected_reason' => 'Cancelled by customer',
            'rejected_at' => now(),
        ]);

        app(\App\Services\SellerPayoutService::class)->syncPayoutBlockFromReturns($return->order);

        return response()->json(['message' => 'Return request cancelled successfully']);
    }

    public function show($id)
    {
        $user = Auth::guard('api')->user();
        $return = ReturnRequest::with(['order', 'orderProduct.product', 'images'])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$return) {
            return response()->json(['message' => 'Return request not found'], 404);
        }

        return response()->json(['return' => $return]);
    }

    private function buildReturnableItemPayload(Order $order, OrderProduct $orderProduct): array
    {
        $setting = Setting::first();
        $windowDays = (int) ($setting->return_window_days ?? 14);
        $allowedStatuses = [2, 3];

        if (!in_array((int) $order->order_status, $allowedStatuses, true)) {
            return [
                'order_product_id' => $orderProduct->id,
                'is_returnable' => false,
                'max_returnable_qty' => 0,
                'message' => 'Order must be delivered or completed before requesting a return',
            ];
        }

        $deliveredAt = $order->order_delivered_date ? now()->parse($order->order_delivered_date) : null;
        if ($deliveredAt && $deliveredAt->diffInDays(now()) > $windowDays) {
            return [
                'order_product_id' => $orderProduct->id,
                'is_returnable' => false,
                'max_returnable_qty' => 0,
                'message' => 'Return period has expired',
            ];
        }

        $activeRequest = ReturnRequest::where('order_product_id', $orderProduct->id)
            ->whereIn('status', [
                ReturnRequest::STATUS_PENDING,
                ReturnRequest::STATUS_SELLER_APPROVED,
                ReturnRequest::STATUS_ADMIN_APPROVED,
                ReturnRequest::STATUS_ITEM_RECEIVED,
            ])
            ->first();

        $processedQty = (int) ReturnRequest::where('order_product_id', $orderProduct->id)
            ->whereIn('status', [
                ReturnRequest::STATUS_SELLER_APPROVED,
                ReturnRequest::STATUS_ADMIN_APPROVED,
                ReturnRequest::STATUS_ITEM_RECEIVED,
                ReturnRequest::STATUS_REFUNDED,
            ])
            ->sum('qty');

        $maxReturnableQty = max(0, (int) $orderProduct->qty - $processedQty);
        $isReturnable = !$activeRequest && $maxReturnableQty > 0;
        $order->loadMissing('orderProducts');
        $refundHint = $order->suggestedReturnRefund(
            $orderProduct,
            $maxReturnableQty > 0 ? $maxReturnableQty : 1
        );

        return [
            'order_product_id' => $orderProduct->id,
            'product_name' => $orderProduct->product_name,
            'qty' => (int) $orderProduct->qty,
            'max_returnable_qty' => $maxReturnableQty,
            'unit_price' => round((float) $orderProduct->unit_price, 2),
            'paid_unit_price' => $refundHint['paid_unit_price'],
            'suggested_refund' => $refundHint['refund_amount'],
            'coupon_share' => $refundHint['coupon_share'],
            'is_returnable' => $isReturnable,
            'existing_return_request_id' => $activeRequest?->id,
            'message' => $isReturnable
                ? null
                : ($activeRequest
                    ? 'An active return request already exists for this item'
                    : 'This item is no longer returnable'),
        ];
    }
}
