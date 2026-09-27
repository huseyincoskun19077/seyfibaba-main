<?php

namespace App\Http\Controllers;

use App\Models\CustomerSegment;
use App\Services\CustomerSegmentService;
use Illuminate\Http\Request;

class CustomerSegmentApiController extends Controller
{
    public function index(Request $request, CustomerSegmentService $service)
    {
        $service->ensureDefaults();
        $guest = $request->query('guest_home') ? 'home' : null;

        return response()->json([
            'success' => true,
            'segments' => $service->listForApi($guest),
        ]);
    }

    public function show(string $slug, CustomerSegmentService $service)
    {
        $service->ensureDefaults();
        $segment = CustomerSegment::query()->active()->where('slug', $slug)->first();
        if (! $segment) {
            return response()->json(['success' => false, 'message' => 'Alan bulunamadı'], 404);
        }

        return response()->json([
            'success' => true,
            'segment' => $service->segmentPayload($segment, true),
        ]);
    }

    public function products(string $slug, Request $request, CustomerSegmentService $service)
    {
        $service->ensureDefaults();
        $segment = CustomerSegment::query()->active()->where('slug', $slug)->first();
        if (! $segment) {
            return response()->json(['success' => false, 'message' => 'Alan bulunamadı'], 404);
        }

        $limit = min(48, max(4, (int) $request->query('limit', $segment->home_product_limit ?: 12)));
        $diversity = max(1, (int) ($segment->vendor_diversity ?: 3));

        $query = $service->productQueryForSegment($segment)
            ->with(['category:id,name,slug', 'brand:id,name'])
            ->orderByDesc('id');

        // Satıcı çeşitliliği: vendor başına sınırlı ürün
        $products = $query->limit($limit * max(3, $diversity))->get();
        $byVendor = [];
        $picked = [];
        foreach ($products as $p) {
            $vid = (int) ($p->vendor_id ?? 0);
            $byVendor[$vid] = ($byVendor[$vid] ?? 0) + 1;
            if ($byVendor[$vid] > $diversity) {
                continue;
            }
            $picked[] = $p;
            if (count($picked) >= $limit) {
                break;
            }
        }

        return response()->json([
            'success' => true,
            'segment' => $service->segmentPayload($segment),
            'products' => $picked,
        ]);
    }
}
