<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use App\Models\Vendor;
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
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->orderByDesc('id')
            ->paginate((int) $request->get('per_page', 20));

        return response()->json(['returns' => $returns]);
    }

    public function show($id)
    {
        $vendor = $this->resolveVendor();
        $return = ReturnRequest::with(['order', 'orderProduct.product', 'user', 'images'])
            ->where('id', $id)
            ->where('seller_id', $vendor->id)
            ->first();

        if (!$return) {
            return response()->json(['message' => 'Return request not found'], 404);
        }

        return response()->json(['return' => $return]);
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'seller_note' => 'nullable|string',
        ]);

        $return = $this->findOwnedReturnRequest($id);
        if ((int) $return->status !== ReturnRequest::STATUS_PENDING) {
            return response()->json(['message' => 'Only pending requests can be approved'], 422);
        }

        $note = $request->seller_note;
        $return->update([
            'status' => ReturnRequest::STATUS_SELLER_APPROVED,
            'vendor_response' => $note,
            'seller_note' => $note,
            'approved_at' => now(),
        ]);

        return response()->json(['message' => 'Return request approved successfully']);
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejected_reason' => 'required|string',
        ]);

        $return = $this->findOwnedReturnRequest($id);
        if ((int) $return->status !== ReturnRequest::STATUS_PENDING) {
            return response()->json(['message' => 'Only pending requests can be rejected'], 422);
        }

        $return->update([
            'status' => ReturnRequest::STATUS_SELLER_REJECTED,
            'vendor_response' => $request->rejected_reason,
            'seller_note' => $request->rejected_reason,
            'rejected_reason' => $request->rejected_reason,
            'rejected_at' => now(),
        ]);

        return response()->json(['message' => 'Return request rejected successfully']);
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

        return response()->json(['message' => 'Unsupported seller status transition'], 422);
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
