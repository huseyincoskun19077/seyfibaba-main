<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SecondHandVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SecondHandVerificationController extends Controller
{
    public function show()
    {
        $user = Auth::guard('api')->user();

        $verification = SecondHandVerification::query()
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        return response()->json([
            'verification' => $verification,
        ]);
    }

    public function submit(Request $request)
    {
        $user = Auth::guard('api')->user();

        $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'tax_number' => ['required', 'string', 'max:64'],
            // Vergi belgesi zorunlu
            'tax_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],

            // Berberler Odası: sicil no veya evrak (en az biri)
            'barber_registry_number' => ['nullable', 'string', 'max:80'],
            'barber_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],

            // Zorunlu sözleşme onayları
            'accept_terms' => ['required', 'accepted'],
            'accept_privacy' => ['required', 'accepted'],
        ]);

        $hasRegistry = trim((string) $request->input('barber_registry_number')) !== '';
        $hasDoc = $request->hasFile('barber_document');
        if (! $hasRegistry && ! $hasDoc) {
            return response()->json([
                'message' => 'Berberler Odası sicil numarası veya evrak yüklemeniz gerekiyor.',
            ], 422);
        }

        $existing = SecondHandVerification::query()
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        if ($existing && $existing->status === SecondHandVerification::STATUS_APPROVED) {
            return response()->json([
                'message' => 'Hesabınız zaten ikinci el için doğrulanmış.',
            ], 422);
        }

        $verification = $existing ?: new SecondHandVerification();

        // Eğer önce reddedildiyse tekrar başvuruda statüyü sıfırla
        if ($verification->exists && $verification->status === SecondHandVerification::STATUS_REJECTED) {
            $verification->reviewed_by = null;
            $verification->reviewed_at = null;
            $verification->admin_note = null;
        }

        $verification->user_id = $user->id;
        $verification->business_name = $request->input('business_name');
        $verification->tax_number = $request->input('tax_number');
        $verification->barber_registry_number = $request->input('barber_registry_number');
        $verification->status = SecondHandVerification::STATUS_PENDING;
        $verification->submitted_at = now();
        $verification->terms_accepted_at = now();
        $verification->privacy_accepted_at = now();

        // Vergi belgesi (zorunlu)
        $file = $request->file('tax_document');
        if ($verification->tax_document_path) {
            Storage::disk('local')->delete($verification->tax_document_path);
        }
        $path = $file->store('second_hand/verification_documents', 'local');
        $verification->tax_document_path = $path;
        $verification->tax_document_original_name = $file->getClientOriginalName();
        $verification->tax_document_size = (int) $file->getSize();

        // Sicil evrağı (opsiyonel)
        if ($request->hasFile('barber_document')) {
            $b = $request->file('barber_document');
            if ($verification->barber_document_path) {
                Storage::disk('local')->delete($verification->barber_document_path);
            }
            $bpath = $b->store('second_hand/verification_documents', 'local');
            $verification->barber_document_path = $bpath;
            $verification->barber_document_original_name = $b->getClientOriginalName();
            $verification->barber_document_size = (int) $b->getSize();
        }

        $verification->save();

        return response()->json([
            'message' => 'Doğrulama başvurunuz alındı.',
            'verification' => $verification,
        ]);
    }
}

