<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LegalDocument;
use App\Models\LegalDocumentConsent;
use App\Services\LegalConsentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LegalDocumentController extends Controller
{
    public function index(): JsonResponse
    {
        $documents = LegalDocument::published()
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get([
                'id',
                'slug',
                'title',
                'version',
                'meta_title',
                'meta_description',
                'requires_consent',
                'category',
                'updated_at',
            ]);

        return response()->json(['documents' => $documents]);
    }

    public function show(string $slug): JsonResponse
    {
        $document = LegalDocument::published()->where('slug', $slug)->first();

        if (!$document) {
            return response()->json(['message' => 'Belge bulunamadı.'], 404);
        }

        return response()->json([
            'document' => [
                'id' => $document->id,
                'slug' => $document->slug,
                'title' => $document->title,
                'content' => $document->content ?? '',
                'version' => $document->version,
                'meta_title' => $document->meta_title ?: $document->title,
                'meta_description' => $document->meta_description,
                'requires_consent' => $document->requires_consent,
                'category' => $document->category,
                'updated_at' => $document->updated_at?->toIso8601String(),
            ],
        ]);
    }

    public function storeConsents(Request $request, LegalConsentService $consentService): JsonResponse
    {
        $validated = $request->validate([
            'consents' => 'required|array|min:1',
            'consents.*.slug' => 'required|string|max:64',
            'consents.*.status' => 'nullable|boolean',
            'context' => 'nullable|string|max:64',
            'order_id' => 'nullable|integer',
            'platform' => 'nullable|string|in:web,android,ios',
            'guest_identifier' => 'nullable|string|max:64',
        ]);

        $userId = Auth::guard('api')->id();

        $consentService->recordMany(
            $request,
            $validated['consents'],
            [
                'user_id' => $userId,
                'guest_identifier' => $validated['guest_identifier'] ?? null,
                'context' => $validated['context'] ?? null,
                'order_id' => $validated['order_id'] ?? null,
                'platform' => $validated['platform'] ?? null,
            ]
        );

        return response()->json(['message' => 'Onay kayıtları oluşturuldu.']);
    }

    public function userConsents(Request $request): JsonResponse
    {
        $userId = Auth::guard('api')->id();

        if (!$userId) {
            return response()->json(['message' => 'Yetkisiz.'], 401);
        }

        $consents = LegalDocumentConsent::where('user_id', $userId)
            ->orderByDesc('consented_at')
            ->limit(200)
            ->get([
                'document_slug',
                'document_title',
                'document_version',
                'consent_status',
                'platform',
                'context',
                'consented_at',
            ]);

        return response()->json(['consents' => $consents]);
    }
}
