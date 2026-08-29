<?php

namespace App\Http\Controllers\WEB\Seller;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReturnRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web');
    }

    public function index(Request $request)
    {
        $seller = Auth::guard('web')->user()->seller;
        $setting = Setting::first();

        $returns = ReturnRequest::with(['order', 'orderProduct.product', 'user', 'images'])
            ->where('seller_id', $seller->id)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->orderByDesc('id')
            ->get();

        return view('seller.return_request', compact('returns', 'setting'));
    }

    public function show($id)
    {
        $seller = Auth::guard('web')->user()->seller;
        $setting = Setting::first();

        $return = ReturnRequest::with(['order', 'orderProduct.product', 'user', 'images'])
            ->where('id', $id)
            ->where('seller_id', $seller->id)
            ->first();

        if (!$return) {
            $notification = ['messege' => 'Return request not found', 'alert-type' => 'error'];
            return redirect()->route('seller.return-requests.index')->with($notification);
        }

        return view('seller.show_return_request', compact('return', 'setting'));
    }

    public function updateStatus(Request $request, $id)
    {
        $seller = Auth::guard('web')->user()->seller;

        $return = ReturnRequest::where('id', $id)
            ->where('seller_id', $seller->id)
            ->first();

        if (!$return) {
            $notification = ['messege' => 'Return request not found', 'alert-type' => 'error'];
            return redirect()->route('seller.return-requests.index')->with($notification);
        }

        if ((int) $request->status === ReturnRequest::STATUS_SELLER_APPROVED) {
            if ((int) $return->status !== ReturnRequest::STATUS_PENDING) {
                $notification = ['messege' => 'Only pending requests can be approved', 'alert-type' => 'error'];
                return redirect()->back()->with($notification);
            }

            $return->update([
                'status' => ReturnRequest::STATUS_SELLER_APPROVED,
                'vendor_response' => $request->seller_note,
                'seller_note' => $request->seller_note,
                'approved_at' => now(),
            ]);

            $notification = ['messege' => 'Return request approved successfully', 'alert-type' => 'success'];
            return redirect()->back()->with($notification);
        }

        if ((int) $request->status === ReturnRequest::STATUS_SELLER_REJECTED) {
            $request->validate([
                'rejected_reason' => 'required|string',
            ]);

            if ((int) $return->status !== ReturnRequest::STATUS_PENDING) {
                $notification = ['messege' => 'Only pending requests can be rejected', 'alert-type' => 'error'];
                return redirect()->back()->with($notification);
            }

            $return->update([
                'status' => ReturnRequest::STATUS_SELLER_REJECTED,
                'vendor_response' => $request->rejected_reason,
                'seller_note' => $request->rejected_reason,
                'rejected_reason' => $request->rejected_reason,
                'rejected_at' => now(),
            ]);

            app(\App\Services\SellerPayoutService::class)->syncPayoutBlockFromReturns($return->order);

            $notification = ['messege' => 'Return request rejected successfully', 'alert-type' => 'success'];
            return redirect()->back()->with($notification);
        }

        $notification = ['messege' => 'Unsupported seller status transition', 'alert-type' => 'error'];
        return redirect()->back()->with($notification);
    }
}
