<?php

namespace App\Http\Controllers\WEB\CallCenter;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Services\CallCenter\CallCenterCommissionCalculator;
use App\Services\CallCenter\CallCenterCommissionService;
use App\Services\CallCenter\QuickSellerOnboardingStatus;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class CallCenterCommissionController extends Controller
{
    public function __construct(
        protected CallCenterCommissionService $commissionService,
        protected CallCenterCommissionCalculator $calculator,
    ) {
        $this->middleware(['auth:admin', 'call-center']);
    }

    public function index()
    {
        $agent = Auth::guard('admin')->user();

        $registrations = Vendor::query()
            ->with(['user', 'callCenterCommission'])
            ->withCount('products')
            ->where('registration_source', 'call_center')
            ->where('registered_by_admin_id', $agent->id)
            ->orderByDesc('id')
            ->paginate(20);

        $this->commissionService->syncMany($registrations->getCollection());
        $registrations->load('callCenterCommission');

        $registrations->getCollection()->transform(function (Vendor $vendor) {
            $vendor->setAttribute('onboarding', QuickSellerOnboardingStatus::for($vendor));
            $vendor->setAttribute('commission_preview', $this->calculator->calculate($vendor));

            return $vendor;
        });

        $totals = $this->commissionService->agentTotals((int) $agent->id);
        $recentPayments = $this->commissionService->recentPayments((int) $agent->id, 15);

        return view('call-center.commissions.index', compact('registrations', 'totals', 'recentPayments'));
    }

    public function approve(int $id)
    {
        $agent = Auth::guard('admin')->user();

        $vendor = Vendor::query()
            ->where('registration_source', 'call_center')
            ->where('registered_by_admin_id', $agent->id)
            ->findOrFail($id);

        try {
            $this->commissionService->approveByAgent($vendor, $agent);
        } catch (RuntimeException $exception) {
            return back()->with(['messege' => $exception->getMessage(), 'alert-type' => 'error']);
        }

        return back()->with([
            'messege' => 'Hakediş onaylandı. Admin ödemesi bekleniyor.',
            'alert-type' => 'success',
        ]);
    }
}
