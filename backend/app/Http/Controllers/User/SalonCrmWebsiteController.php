<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SalonCrmSalon;
use App\Services\ProductImageStorage;
use App\Services\SalonCrmAccessService;
use App\Services\SalonCrmWebsiteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SalonCrmWebsiteController extends Controller
{
    public function __construct(
        private SalonCrmWebsiteService $websites,
        private SalonCrmAccessService $accessService
    ) {
    }

    public function show(Request $request)
    {
        [$salon, $error] = $this->ownerSalon($request);
        if ($error) {
            return $error;
        }

        $this->ensureJoinCode($salon);

        return response()->json($this->websites->ownerPayload($salon->fresh()));
    }

    public function preview(Request $request)
    {
        [$salon, $error] = $this->ownerSalon($request);
        if ($error) {
            return $error;
        }

        $data = $request->validate([
            'province' => ['required', 'string', 'max:80'],
            'district' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:160'],
        ]);

        return response()->json(
            $this->websites->previewUrl(
                $data['province'],
                $data['district'],
                $data['name'],
                (int) $salon->id
            )
        );
    }

    public function update(Request $request, ProductImageStorage $imageStorage)
    {
        [$salon, $error] = $this->ownerSalon($request, requireWrite: true);
        if ($error) {
            return $error;
        }

        if (!Schema::hasColumn('salon_crm_salons', 'website_enabled')) {
            return response()->json([
                'message' => 'Web sitesi alanları henüz kurulmadı. Migration gerekli.',
            ], 503);
        }

        $data = $request->validate([
            'website_enabled' => ['nullable', 'boolean'],
            'website_show_calendar' => ['nullable', 'boolean'],
            'website_show_prices' => ['nullable', 'boolean'],
            'website_show_staff_appointments' => ['nullable', 'boolean'],
            'website_province' => ['nullable', 'string', 'max:80'],
            'website_district' => ['nullable', 'string', 'max:80'],
            'name' => ['nullable', 'string', 'max:160'],
            'type' => ['nullable', 'in:kuafor,berber,guzellik'],
            'phone' => ['nullable', 'string', 'max:32'],
            'whatsapp' => ['nullable', 'string', 'max:32'],
            'instagram' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'address_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'address_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'profile_text' => ['nullable', 'string', 'max:2000'],
            'open_hour' => ['nullable', 'integer', 'min:0', 'max:23'],
            'close_hour' => ['nullable', 'integer', 'min:0', 'max:23'],
            'website_seo_title' => ['nullable', 'string', 'max:180'],
            'website_seo_description' => ['nullable', 'string', 'max:320'],
            'logo_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        if (array_key_exists('name', $data) && trim((string) $data['name']) !== '') {
            $salon->name = trim($data['name']);
        }
        if (array_key_exists('type', $data) && $data['type']) {
            $salon->type = $data['type'];
        }
        if (array_key_exists('phone', $data)) {
            $salon->phone = trim((string) ($data['phone'] ?? '')) ?: null;
        }
        if (array_key_exists('whatsapp', $data)) {
            $salon->whatsapp = trim((string) ($data['whatsapp'] ?? '')) ?: null;
        }
        if (array_key_exists('instagram', $data)) {
            $ig = trim((string) ($data['instagram'] ?? ''));
            $ig = ltrim($ig, '@');
            $ig = preg_replace('#^https?://(www\.)?instagram\.com/#i', '', $ig) ?: $ig;
            $ig = rtrim((string) $ig, '/');
            $salon->instagram = $ig !== '' ? $ig : null;
        }
        if (array_key_exists('address', $data)) {
            $salon->address = trim((string) ($data['address'] ?? '')) ?: null;
        }
        if (array_key_exists('address_lat', $data)) {
            $salon->address_lat = $data['address_lat'] !== null ? (float) $data['address_lat'] : null;
        }
        if (array_key_exists('address_lng', $data)) {
            $salon->address_lng = $data['address_lng'] !== null ? (float) $data['address_lng'] : null;
        }
        if (array_key_exists('profile_text', $data)) {
            $salon->profile_text = trim((string) ($data['profile_text'] ?? '')) ?: null;
        }
        if (array_key_exists('open_hour', $data)) {
            $salon->open_hour = (int) $data['open_hour'];
        }
        if (array_key_exists('close_hour', $data)) {
            $salon->close_hour = (int) $data['close_hour'];
        }
        if (isset($salon->open_hour, $salon->close_hour) && (int) $salon->close_hour <= (int) $salon->open_hour) {
            return response()->json(['message' => 'Kapanış saati açılıştan sonra olmalı.'], 422);
        }

        if (array_key_exists('website_seo_title', $data)) {
            $salon->website_seo_title = trim((string) ($data['website_seo_title'] ?? '')) ?: null;
        }
        if (array_key_exists('website_seo_description', $data)) {
            $salon->website_seo_description = trim((string) ($data['website_seo_description'] ?? '')) ?: null;
        }

        foreach ([
            'website_enabled',
            'website_show_calendar',
            'website_show_prices',
            'website_show_staff_appointments',
        ] as $flag) {
            if (array_key_exists($flag, $data)) {
                $salon->{$flag} = (bool) $data[$flag];
            }
        }

        $province = array_key_exists('website_province', $data)
            ? trim((string) ($data['website_province'] ?? ''))
            : (string) ($salon->website_province ?? '');
        $district = array_key_exists('website_district', $data)
            ? trim((string) ($data['website_district'] ?? ''))
            : (string) ($salon->website_district ?? '');

        if ($salon->website_enabled && ($province === '' || $district === '' || trim((string) $salon->name) === '')) {
            return response()->json([
                'message' => 'Siteyi açmak için il, ilçe ve dükkan adı gerekli.',
            ], 422);
        }

        if ($province !== '' && $district !== '' && trim((string) $salon->name) !== '') {
            $this->websites->applySlugFields($salon, $province, $district, (string) $salon->name);
        }

        if ($request->boolean('remove_logo')) {
            $salon->logo_image = null;
        } elseif ($request->hasFile('logo_image')) {
            $salon->logo_image = $imageStorage->store($request->file('logo_image'), 'salon-crm-logo');
        }

        $salon->save();
        $this->ensureJoinCode($salon);

        return response()->json([
            'message' => 'Web sitesi ayarları kaydedildi.',
            ...$this->websites->ownerPayload($salon->fresh()),
        ]);
    }

    public function publicShow(string $province, string $district, string $slug)
    {
        if (!Schema::hasColumn('salon_crm_salons', 'website_name_slug')) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Salon sitesi bulunamadı.',
            ], 404);
        }

        $payload = $this->websites->publicPayload($province, $district, $slug);
        $status = $payload['status'] ?? 'not_found';
        $code = match ($status) {
            'open' => 200,
            'closed', 'subscription_inactive' => 200,
            default => 404,
        };

        return response()->json($payload, $code);
    }

    /**
     * @return array{0:?SalonCrmSalon,1:?\Illuminate\Http\JsonResponse}
     */
    private function ownerSalon(Request $request, bool $requireWrite = false): array
    {
        $actor = $request->attributes->get('salon_crm_actor');
        if (!$actor || empty($actor['salon'])) {
            return [null, response()->json(['message' => 'CRM girişi gerekli.'], 401)];
        }

        if (($actor['role'] ?? '') !== 'owner') {
            return [null, response()->json(['message' => 'Bu işlem yalnızca salon patronu tarafından yapılabilir.'], 403)];
        }

        /** @var SalonCrmSalon $salon */
        $salon = $actor['salon'];
        if ($requireWrite) {
            $snapshot = $this->accessService->snapshot($salon, $salon->user);
            if (!($snapshot['access']['can_write'] ?? false)) {
                return [null, response()->json([
                    'message' => 'CRM kilitli. İşlem yapılamaz.',
                    ...$snapshot,
                ], 403)];
            }
        }

        return [$salon, null];
    }

    private function ensureJoinCode(SalonCrmSalon $salon): void
    {
        if (!Schema::hasColumn('salon_crm_salons', 'join_code') || !empty($salon->join_code)) {
            return;
        }

        do {
            $code = Str::upper(Str::random(6));
        } while (SalonCrmSalon::query()->where('join_code', $code)->exists());

        $salon->join_code = $code;
        $salon->save();
    }
}
