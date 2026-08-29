<?php

namespace App\Services;

use App\Models\LegalDocument;
use App\Models\LegalDocumentConsent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LegalConsentService
{
    public const SELLER_REGISTER_REQUIRED_SLUGS = [
        'seller-terms',
        'privacy-policy',
        'kvkk-aydinlatma',
    ];

    /**
     * @param  array<int, array{slug: string, status?: bool}>  $consents
     */
    public function assertRequiredSlugs(array $consents, array $requiredSlugs, string $field = 'legal_consents'): void
    {
        $acceptedSlugs = collect($consents)
            ->filter(static fn (array $entry): bool => (bool) ($entry['status'] ?? true))
            ->pluck('slug')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $missing = array_diff($requiredSlugs, $acceptedSlugs);

        if ($missing !== []) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                $field => ['Zorunlu yasal metinleri okuyup kabul etmelisiniz.'],
            ]);
        }
    }

    public function hasAcceptedSlug(array $consents, string $slug): bool
    {
        return collect($consents)->contains(static function (array $entry) use ($slug): bool {
            return ($entry['slug'] ?? null) === $slug && (bool) ($entry['status'] ?? true);
        });
    }

    /**
     * @param  array<int, array{slug: string, status?: bool}>  $consents
     */
    public function recordMany(Request $request, array $consents, array $options = []): void
    {
        $userId = $options['user_id'] ?? null;
        $guestIdentifier = $options['guest_identifier'] ?? null;
        $context = $options['context'] ?? null;
        $orderId = $options['order_id'] ?? null;
        $platform = $options['platform'] ?? $this->detectPlatform($request);

        foreach ($consents as $entry) {
            $slug = $entry['slug'] ?? null;
            if (!$slug) {
                continue;
            }

            $status = array_key_exists('status', $entry) ? (bool) $entry['status'] : true;
            $document = LegalDocument::where('slug', $slug)->first();

            LegalDocumentConsent::create([
                'user_id' => $userId,
                'guest_identifier' => $guestIdentifier,
                'legal_document_id' => $document?->id,
                'document_slug' => $slug,
                'document_title' => $document?->title ?? ($entry['title'] ?? $slug),
                'document_version' => $document?->version ?? ($entry['version'] ?? '1.0'),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
                'platform' => $platform,
                'consent_status' => $status,
                'context' => $context,
                'order_id' => $orderId,
                'consented_at' => Carbon::now(),
            ]);
        }
    }

    public function detectPlatform(Request $request): string
    {
        $platform = strtolower((string) $request->header('X-Platform', ''));

        if (in_array($platform, ['web', 'android', 'ios'], true)) {
            return $platform;
        }

        return 'web';
    }
}
