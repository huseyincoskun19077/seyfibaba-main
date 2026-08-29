<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\SellerKycDocument;
use App\Models\Vendor;
use App\Notifications\KycStatusNotification;
use App\Services\SellerIyzicoOnboardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SellerKycController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(Request $request)
    {
        $query = Vendor::query()
            ->with(['user:id,name,email,phone', 'kycDocuments'])
            ->withCount([
                'products as products_total',
                'products as products_approved' => fn ($q) => $q->where('approve_by_admin', 1),
                'products as products_pending' => fn ($q) => $q->where('approve_by_admin', 0),
            ])
            ->where('status', 1);

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('kyc_status', $request->status);
        }

        $sellers = $query->orderByRaw("FIELD(kyc_status, 'pending', 'not_submitted', 'approved', 'rejected')")
            ->orderByDesc('id')
            ->get();

        $stats = [
            'total' => Vendor::where('status', 1)->count(),
            'not_submitted' => Vendor::where('status', 1)->where('kyc_status', 'not_submitted')->count(),
            'pending' => Vendor::where('status', 1)->where('kyc_status', 'pending')->count(),
            'approved' => Vendor::where('status', 1)->where('kyc_status', 'approved')->count(),
            'rejected' => Vendor::where('status', 1)->where('kyc_status', 'rejected')->count(),
        ];

        return view('admin.seller_kyc', compact('sellers', 'stats'));
    }

    public function show($id)
    {
        $seller = Vendor::query()
            ->with(['user:id,name,email,phone,address', 'kycDocuments.reviewer:id,name,email'])
            ->withCount([
                'products as products_total',
                'products as products_approved' => fn ($q) => $q->where('approve_by_admin', 1),
                'products as products_pending' => fn ($q) => $q->where('approve_by_admin', 0),
            ])
            ->findOrFail($id);

        $status = $this->buildStatusPayload($seller);

        return view('admin.show_seller_kyc', compact('seller', 'status'));
    }

    public function approveVendor(Request $request, $id)
    {
        $request->validate([
            'admin_note' => ['nullable', 'string'],
        ]);

        $seller = Vendor::with('kycDocuments')->findOrFail($id);
        $pendingDocuments = $seller->kycDocuments
            ->where('status', SellerKycDocument::STATUS_PENDING);

        if ($pendingDocuments->isEmpty()) {
            return redirect()->back()->with([
                'messege' => 'Onay bekleyen belge bulunamadı.',
                'alert-type' => 'warning',
            ]);
        }

        foreach ($pendingDocuments as $document) {
            $document->update([
                'status' => SellerKycDocument::STATUS_APPROVED,
                'admin_note' => $request->input('admin_note'),
                'reviewed_by' => Auth::guard('admin')->id(),
                'reviewed_at' => now(),
            ]);
        }

        $this->syncVendorStatus($seller->fresh('kycDocuments'));

        return redirect()->back()->with([
            'messege' => 'Satıcının tüm belgeleri onaylandı.',
            'alert-type' => 'success',
        ]);
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'admin_note' => ['nullable', 'string'],
        ]);

        $document = SellerKycDocument::with('seller.kycDocuments')->findOrFail($id);
        $document->update([
            'status' => SellerKycDocument::STATUS_APPROVED,
            'admin_note' => $request->input('admin_note'),
            'reviewed_by' => Auth::guard('admin')->id(),
            'reviewed_at' => now(),
        ]);

        $this->syncVendorStatus($document->seller->fresh('kycDocuments'));

        return redirect()->back()->with([
            'messege' => trans('admin_validation.Update Successfully'),
            'alert-type' => 'success',
        ]);
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'admin_note' => ['required', 'string'],
        ]);

        $document = SellerKycDocument::with('seller.kycDocuments')->findOrFail($id);
        $document->update([
            'status' => SellerKycDocument::STATUS_REJECTED,
            'admin_note' => $request->input('admin_note'),
            'reviewed_by' => Auth::guard('admin')->id(),
            'reviewed_at' => now(),
        ]);

        $this->syncVendorStatus($document->seller->fresh('kycDocuments'));

        return redirect()->back()->with([
            'messege' => trans('admin_validation.Update Successfully'),
            'alert-type' => 'success',
        ]);
    }

    public function download($id)
    {
        $document = SellerKycDocument::query()->findOrFail($id);

        abort_unless($document->file_path && Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->original_name);
    }

    public function createSubMerchant($sellerId)
    {
        $vendor = Vendor::with('user')->findOrFail($sellerId);

        if ($vendor->kyc_status !== 'approved') {
            return redirect()->back()->with([
                'messege' => 'Alt üye işyeri sadece KYC onaylı satıcılar için oluşturulabilir.',
                'alert-type' => 'error',
            ]);
        }

        if ($vendor->iyzico_sub_merchant_key) {
            return redirect()->back()->with([
                'messege' => 'Bu satıcının zaten bir alt üye işyeri anahtarı var.',
                'alert-type' => 'info',
            ]);
        }

        $onboarding = app(SellerIyzicoOnboardingService::class);
        $missing = $onboarding->missingFields($vendor);
        if ($missing !== []) {
            return redirect()->back()->with([
                'messege' => 'Eksik bilgiler: ' . implode(', ', $missing) . '. Satıcının bu bilgileri KYC formundan doldurması gerekiyor.',
                'alert-type' => 'error',
            ]);
        }

        $result = $onboarding->createForVendor($vendor);
        $vendor->refresh();

        if ($result['ok'] && $vendor->iyzico_sub_merchant_key) {
            return redirect()->back()->with([
                'messege' => 'Iyzico alt üye işyeri başarıyla oluşturuldu! Anahtar: ' . Str::limit($vendor->iyzico_sub_merchant_key, 20),
                'alert-type' => 'success',
            ]);
        }

        return redirect()->back()->with([
            'messege' => 'Alt üye işyeri oluşturulamadı. ' . ($result['message'] ?: 'Loglara bakın.'),
            'alert-type' => 'error',
        ]);
    }

    private function syncVendorStatus(Vendor $vendor): void
    {
        $documents = $vendor->kycDocuments;
        $oldStatus = $vendor->kyc_status;

        if ($documents->isEmpty()) {
            $vendor->kyc_status = 'not_submitted';
            $vendor->kyc_submitted_at = null;
            $vendor->kyc_approved_at = null;
            $vendor->is_verified = 0;
            $vendor->save();

            return;
        }

        if ($documents->contains('status', SellerKycDocument::STATUS_PENDING)) {
            $vendor->kyc_status = 'pending';
            $vendor->kyc_approved_at = null;
        } elseif ($documents->contains('status', SellerKycDocument::STATUS_REJECTED)) {
            $vendor->kyc_status = 'rejected';
            $vendor->kyc_approved_at = null;
        } else {
            $vendor->kyc_status = 'approved';
            $vendor->kyc_approved_at = now();
            $vendor->kyc_submitted_at = $vendor->kyc_submitted_at ?? now();
        }

        $vendor->is_verified = $vendor->kyc_status === 'approved' ? 1 : 0;
        $vendor->save();

        if ($oldStatus !== $vendor->kyc_status && in_array($vendor->kyc_status, ['approved', 'rejected'], true) && $vendor->user) {
            $reason = null;
            if ($vendor->kyc_status === 'rejected') {
                $reason = $documents
                    ->where('status', SellerKycDocument::STATUS_REJECTED)
                    ->sortByDesc('reviewed_at')
                    ->first()
                    ?->admin_note;
            }
            $vendor->user->notify(new KycStatusNotification($vendor, $vendor->kyc_status, $reason));
        }

        if ($vendor->kyc_status === 'approved' && !$vendor->iyzico_sub_merchant_key) {
            app(SellerIyzicoOnboardingService::class)->createForVendor($vendor);
        }
    }

    private function buildStatusPayload(Vendor $vendor): array
    {
        $documents = $vendor->kycDocuments ?? collect();

        return [
            'kyc_status' => $vendor->kyc_status ?? 'not_submitted',
            'submitted_at' => $vendor->kyc_submitted_at,
            'approved_at' => $vendor->kyc_approved_at,
            'document_count' => $documents->count(),
            'pending_count' => $documents->where('status', SellerKycDocument::STATUS_PENDING)->count(),
            'approved_count' => $documents->where('status', SellerKycDocument::STATUS_APPROVED)->count(),
            'rejected_count' => $documents->where('status', SellerKycDocument::STATUS_REJECTED)->count(),
        ];
    }
}
