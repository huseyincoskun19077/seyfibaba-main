<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use App\Models\Vendor;
use App\Services\CommissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReturnRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('checkseller');
    }

    public function index(Request $request)
    {
        $vendor = $this->resolveVendor();
        $returns = ReturnRequest::with(['order', 'orderProduct.product', 'user', 'images'])
            ->where('seller_id', $vendor->id)
            ->when($request->filled('status'), function ($query) use ($request) {
                $status = trim((string) $request->status);
                if ($status === 'approved') {
                    $query->whereIn('status', [
                        ReturnRequest::STATUS_SELLER_APPROVED,
                        ReturnRequest::STATUS_ADMIN_APPROVED,
                        ReturnRequest::STATUS_ITEM_RECEIVED,
                    ]);
                } elseif ($status === 'rejected') {
                    $query->whereIn('status', [
                        ReturnRequest::STATUS_SELLER_REJECTED,
                        ReturnRequest::STATUS_ADMIN_REJECTED,
                    ]);
                } else {
                    $query->where('status', (int) $status);
                }
            })
            ->orderByDesc('id')
            ->paginate((int) $request->get('per_page', 20));

        $returns->getCollection()->transform(
            fn (ReturnRequest $return) => $this->presentForSeller($return, $vendor)
        );

        return response()->json(['returns' => $returns]);
    }

    public function show($id)
    {
        $vendor = $this->resolveVendor();
        $return = ReturnRequest::with(['order', 'orderProduct.product', 'user', 'images'])
            ->where('id', $id)
            ->where('seller_id', $vendor->id)
            ->first();

        if (! $return) {
            return response()->json(['message' => 'İade talebi bulunamadı'], 404);
        }

        return response()->json(['return' => $this->presentForSeller($return, $vendor)]);
    }

    public function approve(Request $request, $id)
    {
        $return = $this->findOwnedReturnRequest($id);
        if ((int) $return->status !== ReturnRequest::STATUS_PENDING) {
            return response()->json(['message' => 'Yalnızca bekleyen talepler onaylanabilir'], 422);
        }

        $vendor = $this->resolveVendor();
        $request->merge([
            'return_address' => $request->input(
                'return_address',
                $return->return_address ?: ReturnRequest::buildSellerReturnAddress($vendor)
            ),
            'return_shipping_payer' => $request->input(
                'return_shipping_payer',
                $return->return_shipping_payer ?: ReturnRequest::defaultShippingPayerForReason($return->reason)
            ),
        ]);

        $request->validate([
            'return_address' => 'required|string|min:10',
            'return_shipping_payer' => 'required|in:seller,buyer',
            'return_carrier_name' => 'nullable|string|max:100',
            'return_cargo_code' => 'nullable|string|max:120',
            'return_shipping_instructions' => 'nullable|string|max:2000',
            'seller_note' => 'nullable|string',
        ], [
            'return_address.required' => 'İade adresi zorunludur',
            'return_shipping_payer.required' => 'İade kargo ücretini kimin karşılayacağını seçin',
            'return_shipping_payer.in' => 'Kargo ücretini yalnızca satıcı veya alıcı karşılayabilir',
        ]);

        $note = $request->seller_note;
        $payer = $request->return_shipping_payer === 'buyer'
            ? ReturnRequest::PAYER_BUYER
            : ReturnRequest::PAYER_SELLER;
        $return->update([
            'status' => ReturnRequest::STATUS_SELLER_APPROVED,
            'vendor_response' => $note,
            'seller_note' => $note,
            'return_address' => trim((string) $request->return_address),
            'return_shipping_payer' => $payer,
            'return_carrier_name' => $request->filled('return_carrier_name') ? trim((string) $request->return_carrier_name) : null,
            'return_cargo_code' => $request->filled('return_cargo_code') ? trim((string) $request->return_cargo_code) : null,
            'return_shipping_instructions' => $request->filled('return_shipping_instructions') ? trim((string) $request->return_shipping_instructions) : null,
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'İade talebini onayladınız. Müşteri iade adresi ve kargo talimatını görecek.',
            'return' => $this->presentForSeller($return->fresh(['order', 'orderProduct.product', 'user', 'images']), $vendor),
        ]);
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejected_reason' => 'required|string|min:5',
        ], [
            'rejected_reason.required' => 'Red gerekçesi zorunludur',
            'rejected_reason.min' => 'Red gerekçesi en az 5 karakter olmalıdır',
        ]);

        $return = $this->findOwnedReturnRequest($id);
        if ((int) $return->status !== ReturnRequest::STATUS_PENDING) {
            return response()->json(['message' => 'Yalnızca bekleyen talepler reddedilebilir'], 422);
        }

        $reason = trim((string) $request->rejected_reason);
        $return->update([
            'status' => ReturnRequest::STATUS_SELLER_REJECTED,
            'vendor_response' => $reason,
            'seller_note' => $reason,
            'rejected_reason' => $reason,
            'rejected_at' => now(),
        ]);

        app(\App\Services\SellerPayoutService::class)->syncPayoutBlockFromReturns($return->order);

        return response()->json([
            'message' => 'İade talebini reddettiniz. Talebiniz yöneticiye ve alıcıya iletildi.',
            'return' => $this->presentForSeller($return->fresh(['order', 'orderProduct.product', 'user', 'images']), $this->resolveVendor()),
        ]);
    }

    public function markReceived(Request $request, $id)
    {
        $return = $this->findOwnedReturnRequest($id);
        if (! $return->canSellerMarkReceived()) {
            return response()->json([
                'message' => 'Bu talep için ürün teslim alındı işaretlenemez. Önce iadeyi onaylayın.',
            ], 422);
        }

        $request->validate([
            'seller_note' => 'nullable|string|max:2000',
        ]);

        $note = $request->filled('seller_note')
            ? trim((string) $request->seller_note)
            : $return->seller_note;

        $return->update([
            'status' => ReturnRequest::STATUS_ITEM_RECEIVED,
            'seller_note' => $note,
            'vendor_response' => $note,
        ]);

        return response()->json([
            'message' => 'Ürünü teslim aldığınız kaydedildi. Yönetici para iadesini tamamlayabilir.',
            'return' => $this->presentForSeller($return->fresh(['order', 'orderProduct.product', 'user', 'images']), $this->resolveVendor()),
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        if ((int) $request->status === ReturnRequest::STATUS_SELLER_APPROVED) {
            return $this->approve($request, $id);
        }

        if ((int) $request->status === ReturnRequest::STATUS_SELLER_REJECTED) {
            $request->merge([
                'rejected_reason' => $request->vendor_response ?? $request->rejected_reason,
            ]);

            return $this->reject($request, $id);
        }

        if ((int) $request->status === ReturnRequest::STATUS_ITEM_RECEIVED) {
            return $this->markReceived($request, $id);
        }

        return response()->json(['message' => 'Geçersiz satıcı işlem durumu'], 422);
    }

    /**
     * Satıcıya müşteri iade tutarı / birim fiyat gösterme; komisyon sonrası net etki.
     */
    private function presentForSeller(ReturnRequest $return, Vendor $vendor): array
    {
        $impact = app(CommissionService::class)->calculateReturnImpact($return);
        $payload = $return->toArray();

        $payload['status_label'] = $return->statusLabel();
        $payload['reason_label'] = ReturnRequest::reasonLabel($return->reason);
        $payload['refund_method_label'] = ReturnRequest::refundMethodLabel($return->refund_method);
        $payload['return_shipping_payer_label'] = ReturnRequest::shippingPayerLabel($return->return_shipping_payer);
        $payload['default_return_address'] = $return->return_address
            ?: ReturnRequest::buildSellerReturnAddress($vendor);
        $payload['default_shipping_payer'] = in_array($return->return_shipping_payer, ['seller', 'buyer'], true)
            ? $return->return_shipping_payer
            : ReturnRequest::defaultShippingPayerForReason($return->reason);
        $payload['can_mark_received'] = $return->canSellerMarkReceived();

        $payload['seller_impact_gross'] = $impact['gross'];
        $payload['seller_impact_commission'] = $impact['commission'];
        $payload['seller_impact_net'] = $impact['seller_net'];
        $payload['seller_commission_rate'] = $impact['commission_rate'];
        // Geriye uyumluluk: satıcı ekranlarında refund_amount = net kazanç etkisi
        $payload['refund_amount'] = $impact['seller_net'];

        if (isset($payload['order_product']) && is_array($payload['order_product'])) {
            unset(
                $payload['order_product']['unit_price'],
                $payload['order_product']['commission_amount'],
                $payload['order_product']['seller_net_amount'],
                $payload['order_product']['commission_rate']
            );
        }

        if (isset($payload['order']) && is_array($payload['order'])) {
            unset(
                $payload['order']['total_amount'],
                $payload['order']['amount_real'],
                $payload['order']['discount_amount'],
                $payload['order']['coupon_coast'],
                $payload['order']['shipping_cost']
            );
        }

        $payload['images'] = $return->images->map(function ($img) {
            $path = (string) ($img->image ?? '');
            if ($path === '') {
                return null;
            }
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return ['id' => $img->id, 'url' => $path];
            }

            return [
                'id' => $img->id,
                'url' => url(ltrim($path, '/')),
            ];
        })->filter()->values();

        return $payload;
    }

    private function resolveVendor(): Vendor
    {
        $user = Auth::guard('api')->user();

        return Vendor::where('user_id', $user->id)->firstOrFail();
    }

    private function findOwnedReturnRequest($id): ReturnRequest
    {
        $vendor = $this->resolveVendor();

        return ReturnRequest::where('id', $id)
            ->where('seller_id', $vendor->id)
            ->firstOrFail();
    }
}
