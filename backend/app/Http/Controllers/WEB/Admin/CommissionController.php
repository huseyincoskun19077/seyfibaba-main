<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\Vendor;
use App\Models\CommissionLedger;
use App\Services\CommissionService;
use App\Support\AdminReportPeriod;
use Illuminate\Support\Facades\DB;

class CommissionController extends Controller
{
    protected $commissionService;

    public function __construct(CommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
        $this->middleware('auth:admin');
    }

    public function settings()
    {
        $setting = Setting::first();
        $vendors = Vendor::with('user')
            ->whereHas('user')
            ->orderBy('shop_name', 'asc')
            ->get();

        return view('admin.commission_settings', compact('setting', 'vendors'));
    }

    public function updateGlobalRate(Request $request)
    {
        $request->validate([
            'default_commission_rate' => 'required|numeric|min:0|max:100',
        ]);

        $setting = Setting::first();
        $setting->default_commission_rate = $request->default_commission_rate;
        $setting->save();

        $notification = trans('admin_validation.Global commission rate updated successfully');
        $notification = array('messege' => $notification, 'alert-type' => 'success');
        return redirect()->back()->with($notification);
    }

    public function updatePayoutSettings(Request $request)
    {
        $request->validate([
            'auto_complete_days' => 'required|integer|min:1|max:60',
            'payout_hold_days' => 'required|integer|min:0|max:30',
            'return_window_days' => 'required|integer|min:1|max:60',
            'iyzico_payout_dry_run' => 'nullable|boolean',
        ]);

        $setting = Setting::first();
        $setting->auto_complete_days = $request->auto_complete_days;
        $setting->payout_hold_days = $request->payout_hold_days;
        $setting->return_window_days = $request->return_window_days;
        $setting->iyzico_payout_dry_run = $request->boolean('iyzico_payout_dry_run');
        $setting->save();

        $notification = array('messege' => 'Hakediş ayarları güncellendi', 'alert-type' => 'success');

        return redirect()->back()->with($notification);
    }

    public function updateVendorRate(Request $request, $id)
    {
        $request->validate([
            'commission_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $vendor = Vendor::findOrFail($id);
        $vendor->commission_rate = $request->commission_rate;
        $vendor->save();

        return response()->json(['message' => trans('admin_validation.Vendor commission rate updated successfully')]);
    }

    public function resetVendorRate($id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->commission_rate = null;
        $vendor->save();

        return response()->json(['message' => trans('admin_validation.Vendor commission rate reset successfully')]);
    }

    public function report(Request $request)
    {
        extract(AdminReportPeriod::resolve($request));

        $sellerId = $request->integer('seller_id');
        $status = $request->get('status');
        $vendors = Vendor::orderBy('shop_name', 'asc')->get(['id', 'shop_name']);

        $ledgerQuery = CommissionLedger::with(['order', 'vendor'])
            ->paidOrders()
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($sellerId, fn ($query) => $query->where('seller_id', $sellerId))
            ->when($status, fn ($query) => $query->where('status', $status));

        $ledgers = (clone $ledgerQuery)->orderBy('id', 'desc')->paginate(20)->withQueryString();

        $summary = (clone $ledgerQuery)
            ->selectRaw('COALESCE(SUM(gross_amount), 0) as total_gross')
            ->selectRaw('COALESCE(SUM(commission_amount), 0) as total_commission')
            ->selectRaw('COALESCE(SUM(seller_net_amount), 0) as total_seller_net')
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'pending' THEN commission_amount ELSE 0 END), 0) as pending_commission")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'settled' THEN commission_amount ELSE 0 END), 0) as settled_commission")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'refunded' THEN commission_amount ELSE 0 END), 0) as refunded_commission")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'pending' THEN seller_net_amount ELSE 0 END), 0) as pending_seller_net")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'settled' THEN seller_net_amount ELSE 0 END), 0) as settled_seller_net")
            ->first();

        $sellerBreakdown = CommissionLedger::query()
            ->paidOrders()
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->select('seller_id')
            ->selectRaw('COALESCE(SUM(gross_amount), 0) as total_gross')
            ->selectRaw('COALESCE(SUM(commission_amount), 0) as total_commission')
            ->selectRaw('COALESCE(SUM(seller_net_amount), 0) as total_seller_net')
            ->with('vendor:id,shop_name')
            ->when($sellerId, fn ($query) => $query->where('seller_id', $sellerId))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->groupBy('seller_id')
            ->orderByDesc(DB::raw('COALESCE(SUM(commission_amount), 0)'))
            ->get();

        return view('admin.commission_report', compact(
            'ledgers',
            'vendors',
            'sellerBreakdown',
            'summary',
            'sellerId',
            'status',
            'period',
            'periodLabel',
            'dateFrom',
            'dateTo'
        ));
    }
}
