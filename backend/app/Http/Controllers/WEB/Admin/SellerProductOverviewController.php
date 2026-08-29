<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;

class SellerProductOverviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(Request $request)
    {
        $query = Vendor::query()
            ->with(['user:id,name,email,phone'])
            ->withCount([
                'products as products_total',
                'products as products_approved' => fn ($q) => $q->where('approve_by_admin', 1),
                'products as products_pending' => fn ($q) => $q->where('approve_by_admin', 0),
                'products as products_active' => fn ($q) => $q->where('status', 1),
            ])
            ->where('status', 1);

        if ($request->filled('kyc_status') && $request->kyc_status !== 'all') {
            $query->where('kyc_status', $request->kyc_status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('shop_name', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $sort = $request->get('sort', 'products_desc');
        match ($sort) {
            'products_asc' => $query->orderBy('products_total'),
            'name' => $query->orderBy('shop_name'),
            'kyc' => $query->orderByRaw("FIELD(kyc_status, 'pending', 'not_submitted', 'approved', 'rejected')"),
            default => $query->orderByDesc('products_total'),
        };

        $sellers = $query->get();

        $summary = [
            'seller_count' => Vendor::where('status', 1)->count(),
            'with_products' => Vendor::where('status', 1)->whereHas('products')->count(),
            'total_products' => Vendor::where('status', 1)
                ->withCount('products')
                ->get()
                ->sum('products_count'),
        ];

        return view('admin.seller_product_overview', compact('sellers', 'summary', 'sort'));
    }
}
