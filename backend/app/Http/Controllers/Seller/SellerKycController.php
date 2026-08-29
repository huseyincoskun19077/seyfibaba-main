<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\UploadSellerKycDocumentRequest;
use App\Models\SellerKycDocument;
use App\Models\Vendor;
use App\Services\SellerIyzicoOnboardingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SellerKycController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('checkseller');
    }

    public function documents()
    {
        $vendor = $this->resolveVendor();

        return response()->json([
            'documents' => $vendor->kycDocuments()->orderBy('document_type')->get(),
            'status' => $this->buildStatusPayload($vendor->fresh('kycDocuments')),
        ]);
    }

    public function status()
    {
        $vendor = $this->resolveVendor()->load('kycDocuments');

        return response()->json([
            'status' => $this->buildStatusPayload($vendor),
        ]);
    }

    public function upload(UploadSellerKycDocumentRequest $request)
    {
        $vendor = $this->resolveVendor()->loadMissing('user');
        $onboarding = app(SellerIyzicoOnboardingService::class);

        if (! $onboarding->hasValidContactEmail($vendor->user?->email)) {
            return response()->json([
                'message' => 'Iyzico alt üye işyeri için geçerli bir e-posta adresi zorunludur. Lütfen önce KYC bilgilerinize e-posta ekleyin.',
            ], 422);
        }

        $file = $request->file('document');
        $documentType = $request->string('document_type')->toString();

        $existingDocument = $vendor->kycDocuments()
            ->where('document_type', $documentType)
            ->first();

        $storedFilePath = $file->storeAs(
            'private/kyc/' . $vendor->id,
            $documentType . '-' . Str::uuid() . '.' . $file->getClientOriginalExtension(),
            'local'
        );

        if ($existingDocument && $existingDocument->file_path) {
            Storage::disk('local')->delete($existingDocument->file_path);
        }

        $document = SellerKycDocument::updateOrCreate(
            [
                'seller_id' => $vendor->id,
                'document_type' => $documentType,
            ],
            [
                'file_path' => $storedFilePath,
                'original_name' => $file->getClientOriginalName(),
                'file_size' => (int) $file->getSize(),
                'status' => SellerKycDocument::STATUS_PENDING,
                'admin_note' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]
        );

        if ($request->filled('iban')) {
            $vendor->iban = $request->string('iban')->trim()->toString();
        }

        if ($request->filled('tax_number')) {
            $vendor->tax_number = $request->string('tax_number')->trim()->toString();
        }

        $vendor->kyc_status = 'pending';
        $vendor->kyc_submitted_at = now();
        $vendor->kyc_approved_at = null;
        $vendor->save();

        return response()->json([
            'message' => 'KYC document uploaded successfully',
            'document' => $document->fresh(),
            'status' => $this->buildStatusPayload($vendor->fresh('kycDocuments')),
        ], 201);
    }

    public function destroy($id)
    {
        $vendor = $this->resolveVendor();
        $document = $vendor->kycDocuments()->where('id', $id)->firstOrFail();

        if ($document->status !== SellerKycDocument::STATUS_PENDING) {
            return response()->json([
                'message' => 'Only pending KYC documents can be deleted',
            ], 422);
        }

        if ($document->file_path) {
            Storage::disk('local')->delete($document->file_path);
        }

        $document->delete();

        $vendor = $vendor->fresh('kycDocuments');
        $this->syncVendorStatus($vendor);

        return response()->json([
            'message' => 'KYC document deleted successfully',
            'status' => $this->buildStatusPayload($vendor->fresh('kycDocuments')),
        ]);
    }

    public function updateInfo(Request $request)
    {
        $user = Auth::guard('api')->user();
        $seller = $this->resolveVendor();
        $onboarding = app(SellerIyzicoOnboardingService::class);
        $hasRealEmail = $onboarding->hasValidContactEmail($user->email);

        $rules = [
            'seller_type' => 'required|in:sole_proprietorship,limited_company,corporate',
            'iban' => 'required|string',
            'address' => 'required|string|min:10',
            'tc_identity' => 'required|string',
            'tax_number' => 'nullable|string',
            'tax_office' => 'nullable|string',
            'legal_company_title' => 'nullable|string',
        ];
        $messages = [];
        if (! $hasRealEmail) {
            $rules['email'] = 'required|email|max:255|unique:users,email,' . $user->id;
            $messages = [
                'email.required' => 'Iyzico alt üye işyeri için e-posta adresi zorunludur.',
                'email.email' => 'Geçerli bir e-posta adresi giriniz.',
                'email.unique' => 'Bu e-posta adresi başka bir hesapta kayıtlı.',
            ];
        }
        $request->validate($rules, $messages);

        $sellerType = $onboarding->normalizeSellerType($request->seller_type);
        $tc = preg_replace('/\D/', '', (string) $request->tc_identity);
        if (strlen($tc) !== 11) {
            return response()->json(['message' => 'TC Kimlik No 11 haneli olmalıdır.', 'errors' => ['tc_identity' => ['TC Kimlik No 11 haneli olmalıdır.']]], 422);
        }

        $iban = strtoupper(preg_replace('/\s+/', '', (string) $request->iban));
        if (! preg_match('/^TR\d{24}$/', $iban)) {
            return response()->json(['message' => 'IBAN formatı hatalı. TR ile başlayan 26 karakterli numara olmalıdır.', 'errors' => ['iban' => ['IBAN formatı hatalı.']]], 422);
        }

        $address = trim((string) $request->address);
        if ($address === '' || $address === 'Adres bilgisi sonra tamamlanacak') {
            return response()->json(['message' => 'Iyzico için geçerli bir adres giriniz.', 'errors' => ['address' => ['Geçerli bir adres giriniz.']]], 422);
        }

        $errors = [];
        if (in_array($sellerType, ['sole_proprietorship', 'limited_company'], true)) {
            if (! $request->filled('tax_office')) {
                $errors['tax_office'] = ['Vergi dairesi zorunludur.'];
            }
            if (! $request->filled('legal_company_title')) {
                $errors['legal_company_title'] = ['Ticari unvan zorunludur.'];
            }
        }
        if ($sellerType === 'limited_company' && ! $request->filled('tax_number')) {
            $errors['tax_number'] = ['Ltd / A.Ş. için vergi numarası zorunludur.'];
        }
        if ($errors !== []) {
            return response()->json(['message' => 'Doğrulama hatası', 'errors' => $errors], 422);
        }

        $seller->seller_type = $sellerType;
        $seller->tc_identity = $tc;
        $seller->iban = $iban;
        $seller->address = $address;
        $seller->tax_office = $request->filled('tax_office') ? trim((string) $request->tax_office) : null;
        $seller->legal_company_title = $request->filled('legal_company_title') ? trim((string) $request->legal_company_title) : null;

        if (! $hasRealEmail) {
            $user->email = strtolower(trim((string) $request->email));
            $user->save();
            $seller->email = $user->email;
        }

        if ($sellerType === 'limited_company') {
            $seller->tax_number = trim((string) $request->tax_number);
        } else {
            $seller->tax_number = $request->filled('tax_number')
                ? trim((string) $request->tax_number)
                : $tc;
        }

        $seller->save();

        return response()->json([
            'message' => 'Bilgiler kaydedildi.',
            'status' => $this->buildStatusPayload($seller->fresh('kycDocuments')),
            'seller' => $seller->fresh(),
        ]);
    }

    private function resolveVendor(): Vendor
    {
        return Vendor::where('user_id', Auth::guard('api')->id())->firstOrFail();
    }

    private function buildStatusPayload(Vendor $vendor): array
    {
        $documents = $vendor->kycDocuments ?? collect();

        return [
            'kyc_status' => $vendor->kyc_status ?? 'not_submitted',
            'submitted_at' => optional($vendor->kyc_submitted_at)->toISOString(),
            'approved_at' => optional($vendor->kyc_approved_at)->toISOString(),
            'iban' => $vendor->iban,
            'tax_number' => $vendor->tax_number,
            'seller_type' => $vendor->seller_type,
            'tc_identity' => $vendor->tc_identity,
            'address' => $vendor->address,
            'tax_office' => $vendor->tax_office,
            'legal_company_title' => $vendor->legal_company_title,
            'document_count' => $documents->count(),
            'uploaded_document_types' => $documents->pluck('document_type')->values(),
            'pending_document_types' => $documents->where('status', SellerKycDocument::STATUS_PENDING)->pluck('document_type')->values(),
            'rejected_document_types' => $documents->where('status', SellerKycDocument::STATUS_REJECTED)->pluck('document_type')->values(),
        ];
    }

    private function syncVendorStatus(Vendor $vendor): void
    {
        $documents = $vendor->kycDocuments;

        if ($documents->isEmpty()) {
            $vendor->kyc_status = 'not_submitted';
            $vendor->kyc_submitted_at = null;
            $vendor->kyc_approved_at = null;
            $vendor->is_verified = 0;
            $vendor->save();

            return;
        }

        $vendor->kyc_status = $documents->contains('status', SellerKycDocument::STATUS_PENDING)
            ? 'pending'
            : ($documents->contains('status', SellerKycDocument::STATUS_REJECTED) ? 'rejected' : 'approved');

        if ($vendor->kyc_status !== 'approved') {
            $vendor->kyc_approved_at = null;
        }

        $vendor->is_verified = $vendor->kyc_status === 'approved' ? 1 : 0;
        $vendor->save();
    }
}
