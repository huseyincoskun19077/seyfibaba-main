<?php

namespace App\Http\Controllers\WEB\CallCenter;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Support\Facades\Auth;

class CallCenterDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:admin', 'call-center']);
    }

    public function index()
    {
        $agent = Auth::guard('admin')->user();

        $todayCount = Vendor::query()
            ->where('registration_source', 'call_center')
            ->where('registered_by_admin_id', $agent->id)
            ->whereDate('created_at', today())
            ->count();

        $totalCount = Vendor::query()
            ->where('registration_source', 'call_center')
            ->where('registered_by_admin_id', $agent->id)
            ->count();

        $recentRegistrations = Vendor::query()
            ->with('user')
            ->where('registration_source', 'call_center')
            ->where('registered_by_admin_id', $agent->id)
            ->latest('id')
            ->limit(5)
            ->get();

        return view('call-center.dashboard', compact('todayCount', 'totalCount', 'recentRegistrations'));
    }
}
