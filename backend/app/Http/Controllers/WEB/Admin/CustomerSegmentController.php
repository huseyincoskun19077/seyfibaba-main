<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CustomerSegment;
use App\Models\CustomerSegmentProduct;
use App\Models\CustomerSegmentTaxonomy;
use App\Models\Product;
use App\Models\SegmentAssignmentQueue;
use App\Models\SubCategory;
use App\Services\CustomerSegmentService;
use Illuminate\Http\Request;

class CustomerSegmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(CustomerSegmentService $service)
    {
        $service->ensureDefaults();
        $segments = CustomerSegment::query()->orderBy('serial')->orderBy('id')->get();
        $pendingCount = SegmentAssignmentQueue::query()
            ->where('status', SegmentAssignmentQueue::STATUS_PENDING)
            ->count();

        $segmentStats = [];
        foreach ($segments as $s) {
            try {
                $q = $service->productQueryForSegment($s)->where('status', 1);
                $segmentStats[$s->id] = [
                    'products' => (clone $q)->count(),
                    'vendors' => (clone $q)->distinct('vendor_id')->count('vendor_id'),
                ];
            } catch (\Throwable $e) {
                $segmentStats[$s->id] = ['products' => 0, 'vendors' => 0];
            }
        }

        return view('admin.customer_segments.index', compact('segments', 'pendingCount', 'segmentStats'));
    }

    public function edit(int $id)
    {
        $segment = CustomerSegment::query()->with(['taxonomies.category', 'taxonomies.subCategory'])->findOrFail($id);
        $categories = Category::query()->orderBy('serial')->orderBy('id')->get(['id', 'name']);
        $subCategories = SubCategory::query()->with('category:id,name')->orderBy('category_id')->orderBy('id')->get();
        $featured = CustomerSegmentProduct::query()
            ->where('customer_segment_id', $segment->id)
            ->where('is_featured', true)
            ->with('product:id,name')
            ->orderBy('serial')
            ->get();

        return view('admin.customer_segments.edit', compact('segment', 'categories', 'subCategories', 'featured'));
    }

    public function update(Request $request, int $id)
    {
        $segment = CustomerSegment::query()->findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'short_name' => 'nullable|string|max:60',
            'description' => 'nullable|string|max:2000',
            'serial' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'show_on_guest_home' => 'nullable|boolean',
            'is_primary_home' => 'nullable|boolean',
            'vendor_diversity' => 'nullable|integer|min:1|max:20',
            'home_product_limit' => 'nullable|integer|min:4|max:48',
            'business_type_key' => 'nullable|string|max:40',
        ]);

        $segment->fill([
            'name' => $data['name'],
            'short_name' => $data['short_name'] ?? $segment->short_name,
            'description' => $data['description'] ?? null,
            'serial' => (int) ($data['serial'] ?? $segment->serial),
            'is_active' => $request->boolean('is_active'),
            'show_on_guest_home' => $request->boolean('show_on_guest_home'),
            'is_primary_home' => $request->boolean('is_primary_home'),
            'vendor_diversity' => (int) ($data['vendor_diversity'] ?? 3),
            'home_product_limit' => (int) ($data['home_product_limit'] ?? 12),
            'business_type_key' => $data['business_type_key'] ?: null,
        ]);
        $segment->save();

        return redirect()
            ->route('admin.customer-segments.edit', $segment->id)
            ->with(['messege' => 'Alan güncellendi.', 'alert-type' => 'success']);
    }

    public function storeTaxonomy(Request $request, int $id)
    {
        $segment = CustomerSegment::query()->findOrFail($id);
        $data = $request->validate([
            'category_id' => 'nullable|integer',
            'sub_category_id' => 'nullable|integer',
            'child_category_id' => 'nullable|integer',
        ]);

        if (empty($data['category_id']) && empty($data['sub_category_id']) && empty($data['child_category_id'])) {
            return back()->with(['messege' => 'En az bir kategori seviyesi seçin.', 'alert-type' => 'error']);
        }

        if (! empty($data['sub_category_id']) && empty($data['category_id'])) {
            $sub = SubCategory::query()->find($data['sub_category_id']);
            $data['category_id'] = $sub?->category_id;
        }

        CustomerSegmentTaxonomy::query()->updateOrCreate(
            [
                'customer_segment_id' => $segment->id,
                'category_id' => $data['category_id'] ?: null,
                'sub_category_id' => $data['sub_category_id'] ?: null,
                'child_category_id' => $data['child_category_id'] ?: null,
            ],
            ['is_active' => true]
        );

        return back()->with(['messege' => 'Kategori eşlemesi eklendi.', 'alert-type' => 'success']);
    }

    public function destroyTaxonomy(int $id, int $taxonomyId)
    {
        CustomerSegmentTaxonomy::query()
            ->where('customer_segment_id', $id)
            ->where('id', $taxonomyId)
            ->delete();

        return back()->with(['messege' => 'Eşleme kaldırıldı.', 'alert-type' => 'success']);
    }

    public function storeFeaturedProduct(Request $request, int $id)
    {
        $segment = CustomerSegment::query()->findOrFail($id);
        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'is_featured' => 'nullable|boolean',
        ]);

        CustomerSegmentProduct::query()->updateOrCreate(
            [
                'customer_segment_id' => $segment->id,
                'product_id' => (int) $data['product_id'],
            ],
            [
                'is_featured' => $request->boolean('is_featured', true),
                'is_forced' => true,
            ]
        );

        return back()->with(['messege' => 'Ürün alana eklendi.', 'alert-type' => 'success']);
    }

    public function destroyFeaturedProduct(int $id, int $rowId)
    {
        CustomerSegmentProduct::query()
            ->where('customer_segment_id', $id)
            ->where('id', $rowId)
            ->delete();

        return back()->with(['messege' => 'Ürün kaldırıldı.', 'alert-type' => 'success']);
    }

    public function queue(CustomerSegmentService $service)
    {
        $service->ensureDefaults();
        $items = SegmentAssignmentQueue::query()
            ->with(['product:id,name,category_id,sub_category_id', 'product.category:id,name', 'product.subCategory:id,name'])
            ->where('status', SegmentAssignmentQueue::STATUS_PENDING)
            ->orderByDesc('id')
            ->paginate(40);
        $segments = CustomerSegment::query()->orderBy('serial')->get(['id', 'code', 'name']);

        return view('admin.customer_segments.queue', compact('items', 'segments'));
    }

    public function queueScan(CustomerSegmentService $service)
    {
        $service->ensureDefaults();
        $rows = $service->queueAmbiguousProducts(false, 800);

        return redirect()
            ->route('admin.customer-segments.queue')
            ->with(['messege' => count($rows).' belirsiz ürün kuyruğa alındı / güncellendi.', 'alert-type' => 'success']);
    }

    public function queueResolve(Request $request, int $queueId)
    {
        $item = SegmentAssignmentQueue::query()->findOrFail($queueId);
        $data = $request->validate([
            'action' => 'required|in:approve,reject',
            'segment_ids' => 'nullable|array',
            'segment_ids.*' => 'integer',
        ]);

        if ($data['action'] === 'reject') {
            $item->status = SegmentAssignmentQueue::STATUS_REJECTED;
            $item->reviewed_at = now();
            $item->save();

            return back()->with(['messege' => 'Reddedildi.', 'alert-type' => 'info']);
        }

        $segmentIds = array_map('intval', $data['segment_ids'] ?? []);
        foreach ($segmentIds as $sid) {
            CustomerSegmentProduct::query()->updateOrCreate(
                ['customer_segment_id' => $sid, 'product_id' => $item->product_id],
                ['is_forced' => true, 'is_featured' => false]
            );
        }
        $item->status = SegmentAssignmentQueue::STATUS_APPROVED;
        $item->reviewed_at = now();
        $item->save();

        return back()->with(['messege' => 'Ürün alanlara eşlendi.', 'alert-type' => 'success']);
    }

    public function preview(CustomerSegmentService $service)
    {
        $service->ensureDefaults();
        $preview = $service->previewSubCategoryMapping();

        return view('admin.customer_segments.preview', compact('preview'));
    }

    public function applyUnambiguous(CustomerSegmentService $service)
    {
        $n = $service->applySuggestedSubMappings(true);

        return redirect()
            ->route('admin.customer-segments.index')
            ->with(['messege' => "{$n} net alt kategori eşlemesi uygulandı (belirsizler atlandı).", 'alert-type' => 'success']);
    }
}
