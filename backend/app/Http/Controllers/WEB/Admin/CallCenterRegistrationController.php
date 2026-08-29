<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Vendor;
use App\Services\CallCenter\CallCenterCommissionService;
use App\Services\CallCenter\QuickSellerOnboardingStatus;
use App\Services\CallCenter\QuickSellerRegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CallCenterRegistrationController extends Controller
{
    public function __construct(
        protected QuickSellerRegistrationService $registrationService,
        protected CallCenterCommissionService $commissionService,
    ) {
        $this->middleware('auth:admin');
    }

    public function index(Request $request)
    {
        $query = Vendor::query()
            ->with(['user', 'registeredByAdmin'])
            ->withCount('products')
            ->where('registration_source', 'call_center')
            ->orderByRaw("CASE WHEN vendors.kyc_status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('vendors.id');

        if ($request->filled('agent_id')) {
            $query->where('registered_by_admin_id', (int) $request->agent_id);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($builder) use ($search) {
                $builder->where('shop_name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%')
                            ->orWhere('phone', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('registeredByAdmin', function ($adminQuery) use ($search) {
                        $adminQuery->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    });
            });
        }

        $registrations = $query->with('callCenterCommission')->paginate(25)->withQueryString();

        $this->syncAllCommissions();
        $registrations->load('callCenterCommission');

        $registrations->getCollection()->transform(function (Vendor $vendor) {
            $vendor->setAttribute('onboarding', QuickSellerOnboardingStatus::for($vendor));

            return $vendor;
        });

        $agents = Admin::query()
            ->where('admin_type', Admin::TYPE_CALL_CENTER)
            ->orderByRaw("CASE WHEN LOWER(name) LIKE '%emre%üstün%' OR LOWER(name) LIKE '%emre%ustun%' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $agentStats = $this->buildAgentStats();

        return view('admin.call_center_registrations', compact('registrations', 'agents', 'agentStats'));
    }

    public function resendSms(int $id)
    {
        $vendor = Vendor::query()
            ->with('user')
            ->where('registration_source', 'call_center')
            ->findOrFail($id);

        try {
            $this->registrationService->resendFirstLoginSms($vendor);
        } catch (RuntimeException $exception) {
            return back()->with(['messege' => $exception->getMessage(), 'alert-type' => 'error']);
        }

        return back()->with([
            'messege' => 'Tek kullanımlık giriş kodu SMS ile yeniden gönderildi.',
            'alert-type' => 'success',
        ]);
    }

    public function payCommission(Request $request, int $id)
    {
        $vendor = Vendor::query()
            ->where('registration_source', 'call_center')
            ->findOrFail($id);

        try {
            $this->commissionService->markPaid(
                $vendor,
                Auth::guard('admin')->user(),
                $request->input('note')
            );
        } catch (RuntimeException $exception) {
            return back()->with(['messege' => $exception->getMessage(), 'alert-type' => 'error']);
        }

        return back()->with([
            'messege' => 'Hakediş ödemesi kaydedildi.',
            'alert-type' => 'success',
        ]);
    }

    protected function syncAllCommissions(): void
    {
        $vendors = Vendor::query()
            ->where('registration_source', 'call_center')
            ->whereNotNull('registered_by_admin_id')
            ->withCount('products')
            ->get();

        $this->commissionService->syncMany($vendors);
    }

    /**
     * Çağrı merkezi temsilcisi istatistikleri.
     * Ürün join'i seller/KYC sayılarını şişirmemesi için ayrı sorgularla hesaplanır.
     *
     * @return \Illuminate\Support\Collection<int, object{seller_count: int, approved_kyc_count: int, product_count: int}>
     */
    protected function buildAgentStats(): \Illuminate\Support\Collection
    {
        $sellerStats = Vendor::query()
            ->select('registered_by_admin_id')
            ->selectRaw('COUNT(*) as seller_count')
            ->selectRaw("SUM(CASE WHEN kyc_status = 'approved' THEN 1 ELSE 0 END) as approved_kyc_count")
            ->where('registration_source', 'call_center')
            ->whereNotNull('registered_by_admin_id')
            ->groupBy('registered_by_admin_id')
            ->get()
            ->keyBy('registered_by_admin_id');

        $productStats = DB::table('products')
            ->join('vendors', 'vendors.id', '=', 'products.vendor_id')
            ->where('vendors.registration_source', 'call_center')
            ->whereNotNull('vendors.registered_by_admin_id')
            ->groupBy('vendors.registered_by_admin_id')
            ->select('vendors.registered_by_admin_id')
            ->selectRaw('COUNT(products.id) as product_count')
            ->get()
            ->keyBy('registered_by_admin_id');

        $commissionStats = DB::table('call_center_commissions')
            ->select('admin_id')
            ->selectRaw('COALESCE(SUM(calculated_total), 0) as calculated_total')
            ->selectRaw('COALESCE(SUM(paid_total), 0) as paid_total')
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'awaiting_payment' THEN approved_amount ELSE 0 END), 0) as awaiting_payment")
            ->groupBy('admin_id')
            ->get()
            ->keyBy('admin_id');

        $adminIds = $sellerStats->keys()->merge($productStats->keys())->merge($commissionStats->keys())->unique();

        return $adminIds->mapWithKeys(function ($adminId) use ($sellerStats, $productStats, $commissionStats) {
            $seller = $sellerStats->get($adminId);
            $products = $productStats->get($adminId);
            $commission = $commissionStats->get($adminId);

            $calculated = (float) ($commission->calculated_total ?? 0);
            $paid = (float) ($commission->paid_total ?? 0);
            $awaiting = (float) ($commission->awaiting_payment ?? 0);
            $pendingApproval = max(0, $calculated - $paid - $awaiting);

            return [
                $adminId => (object) [
                    'seller_count' => (int) ($seller->seller_count ?? 0),
                    'approved_kyc_count' => (int) ($seller->approved_kyc_count ?? 0),
                    'product_count' => (int) ($products->product_count ?? 0),
                    'commission_calculated' => $calculated,
                    'commission_paid' => $paid,
                    'commission_awaiting' => $awaiting,
                    'commission_pending' => $pendingApproval,
                ],
            ];
        });
    }
}
