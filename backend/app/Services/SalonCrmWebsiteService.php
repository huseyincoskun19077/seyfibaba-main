<?php

namespace App\Services;

use App\Models\SalonCrmAppointment;
use App\Models\SalonCrmSalon;
use App\Models\SalonCrmService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SalonCrmWebsiteService
{
    public function __construct(private SalonCrmAccessService $accessService)
    {
    }

    public function slugify(string $text): string
    {
        $map = [
            'ş' => 's', 'Ş' => 's', 'ı' => 'i', 'İ' => 'i', 'I' => 'i',
            'ğ' => 'g', 'Ğ' => 'g', 'ü' => 'u', 'Ü' => 'u',
            'ö' => 'o', 'Ö' => 'o', 'ç' => 'c', 'Ç' => 'c',
        ];
        $t = strtr(trim($text), $map);
        $slug = Str::slug($t);

        return $slug !== '' ? $slug : 'salon';
    }

    public function previewUrl(string $province, string $district, string $name, ?int $exceptSalonId = null): array
    {
        $p = $this->slugify($province);
        $d = $this->slugify($district);
        $baseName = $this->slugify($name);
        [$nameSlug, $seq] = $this->allocateNameSlug($p, $d, $baseName, $exceptSalonId);

        return [
            'province_slug' => $p,
            'district_slug' => $d,
            'name_slug' => $nameSlug,
            'slug_seq' => $seq,
            'path' => $p.'/'.$d.'/'.$nameSlug,
            'url' => $this->publicUrl($p, $d, $nameSlug),
        ];
    }

    public function publicUrl(string $provinceSlug, string $districtSlug, string $nameSlug): string
    {
        $base = rtrim((string) env('MARKETPLACE_URL', 'https://seyfibaba.com'), '/');

        return $base.'/salon/'.$provinceSlug.'/'.$districtSlug.'/'.$nameSlug;
    }

    public function qrImageUrl(string $pageUrl): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&data='.rawurlencode($pageUrl);
    }

    /**
     * @return array{0:string,1:int}
     */
    public function allocateNameSlug(string $provinceSlug, string $districtSlug, string $baseNameSlug, ?int $exceptSalonId = null): array
    {
        if (!Schema::hasColumn('salon_crm_salons', 'website_name_slug')) {
            return [$baseNameSlug, 1];
        }

        $seq = 1;
        while ($seq < 1000) {
            $candidate = $seq === 1 ? $baseNameSlug : ($baseNameSlug.'-'.$seq);
            $exists = SalonCrmSalon::query()
                ->where('website_province_slug', $provinceSlug)
                ->where('website_district_slug', $districtSlug)
                ->where('website_name_slug', $candidate)
                ->when($exceptSalonId, fn ($q) => $q->where('id', '!=', $exceptSalonId))
                ->exists();
            if (!$exists) {
                return [$candidate, $seq];
            }
            $seq++;
        }

        return [$baseNameSlug.'-'.Str::lower(Str::random(4)), $seq];
    }

    public function applySlugFields(SalonCrmSalon $salon, string $province, string $district, string $displayName): void
    {
        if (!Schema::hasColumn('salon_crm_salons', 'website_name_slug')) {
            return;
        }

        $p = $this->slugify($province);
        $d = $this->slugify($district);
        $base = $this->slugify($displayName);
        [$nameSlug, $seq] = $this->allocateNameSlug($p, $d, $base, (int) $salon->id);

        $salon->website_province = trim($province) ?: null;
        $salon->website_district = trim($district) ?: null;
        $salon->website_province_slug = $p;
        $salon->website_district_slug = $d;
        $salon->website_name_slug = $nameSlug;
        $salon->website_slug_seq = $seq;
    }

    public function ownerPayload(SalonCrmSalon $salon): array
    {
        $snapshot = $this->accessService->snapshot($salon, $salon->user);
        $unlocked = (bool) ($snapshot['access']['is_unlocked'] ?? false);
        $pathReady = $salon->website_province_slug
            && $salon->website_district_slug
            && $salon->website_name_slug;
        $url = $pathReady
            ? $this->publicUrl(
                (string) $salon->website_province_slug,
                (string) $salon->website_district_slug,
                (string) $salon->website_name_slug
            )
            : null;

        return [
            'website_enabled' => (bool) ($salon->website_enabled ?? false),
            'website_show_calendar' => (bool) ($salon->website_show_calendar ?? false),
            'website_show_prices' => (bool) ($salon->website_show_prices ?? false),
            'website_show_staff_appointments' => (bool) ($salon->website_show_staff_appointments ?? false),
            'website_province' => $salon->website_province,
            'website_district' => $salon->website_district,
            'type' => $salon->type,
            'name' => $salon->name,
            'phone' => $salon->phone,
            'whatsapp' => $salon->whatsapp ?? null,
            'instagram' => $salon->instagram ?? null,
            'address' => $salon->address ?? null,
            'address_lat' => $salon->address_lat !== null ? (float) $salon->address_lat : null,
            'address_lng' => $salon->address_lng !== null ? (float) $salon->address_lng : null,
            'profile_text' => $salon->profile_text,
            'logo_image' => $salon->logo_image,
            'cover_image' => $salon->cover_image,
            'open_hour' => (int) ($salon->open_hour ?? 9),
            'close_hour' => (int) ($salon->close_hour ?? 21),
            'website_seo_title' => $salon->website_seo_title ?? null,
            'website_seo_description' => $salon->website_seo_description ?? null,
            'path' => $pathReady
                ? $salon->website_province_slug.'/'.$salon->website_district_slug.'/'.$salon->website_name_slug
                : null,
            'url' => $url,
            'qr_url' => $url ? $this->qrImageUrl($url) : null,
            'subscription_unlocked' => $unlocked,
            'public_active' => $unlocked && (bool) ($salon->website_enabled ?? false) && $pathReady,
            'access_message' => $snapshot['access']['message'] ?? null,
        ];
    }

    public function publicPayload(string $provinceSlug, string $districtSlug, string $nameSlug): array
    {
        $salon = SalonCrmSalon::query()
            ->where('website_province_slug', $provinceSlug)
            ->where('website_district_slug', $districtSlug)
            ->where('website_name_slug', $nameSlug)
            ->first();

        if (!$salon) {
            return [
                'status' => 'not_found',
                'message' => 'Salon sitesi bulunamadı.',
            ];
        }

        $snapshot = $this->accessService->snapshot($salon, $salon->user);
        $unlocked = (bool) ($snapshot['access']['is_unlocked'] ?? false);
        $enabled = (bool) ($salon->website_enabled ?? false);

        if (!$unlocked) {
            return [
                'status' => 'subscription_inactive',
                'message' => 'Bu salon sitesi şu an pasif (abonelik / erişim kapalı).',
                'salon_name' => $salon->name,
            ];
        }

        if (!$enabled) {
            return [
                'status' => 'closed',
                'message' => 'Salon sahibi web sitesini geçici olarak kapattı.',
                'salon_name' => $salon->name,
            ];
        }

        $showCalendar = (bool) ($salon->website_show_calendar ?? false);
        $showPrices = (bool) ($salon->website_show_prices ?? false);
        $showStaffAppts = (bool) ($salon->website_show_staff_appointments ?? false);

        $services = SalonCrmService::query()
            ->where('salon_id', $salon->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'duration_minutes', 'price']);

        $servicePayload = $services->map(function ($s) use ($showPrices) {
            $row = [
                'id' => (int) $s->id,
                'name' => (string) $s->name,
                'duration_minutes' => (int) ($s->duration_minutes ?? 30),
            ];
            if ($showPrices) {
                $row['price'] = $s->price !== null ? (float) $s->price : null;
            }

            return $row;
        })->values()->all();

        $url = $this->publicUrl($provinceSlug, $districtSlug, $nameSlug);
        $joinCode = $salon->join_code;
        if (!$joinCode && Schema::hasColumn('salon_crm_salons', 'join_code')) {
            do {
                $joinCode = Str::upper(Str::random(6));
            } while (SalonCrmSalon::query()->where('join_code', $joinCode)->exists());
            $salon->join_code = $joinCode;
            $salon->save();
        }

        return [
            'status' => 'open',
            'salon' => [
                'id' => (int) $salon->id,
                'name' => $salon->name,
                'type' => $salon->type,
                'province' => $salon->website_province,
                'district' => $salon->website_district,
                'phone' => $salon->phone,
                'whatsapp' => $salon->whatsapp ?? null,
                'instagram' => $salon->instagram ?? null,
                'address' => $salon->address ?? null,
                'address_lat' => $salon->address_lat !== null ? (float) $salon->address_lat : null,
                'address_lng' => $salon->address_lng !== null ? (float) $salon->address_lng : null,
                'profile_text' => $salon->profile_text,
                'logo_image' => $salon->logo_image,
                'cover_image' => $salon->cover_image,
                'open_hour' => (int) ($salon->open_hour ?? 9),
                'close_hour' => (int) ($salon->close_hour ?? 21),
                'seo_title' => $salon->website_seo_title ?: ($salon->name.' | Seyfibaba'),
                'seo_description' => $salon->website_seo_description
                    ?: Str::limit(trim((string) ($salon->profile_text ?: ($salon->name.' randevu ve hizmetler'))), 160),
                'join_code' => $joinCode,
            ],
            'flags' => [
                'show_calendar' => $showCalendar,
                'show_prices' => $showPrices,
                'show_staff_appointments' => $showStaffAppts,
            ],
            'services' => $servicePayload,
            'calendar' => $showCalendar
                ? $this->publicCalendar($salon, $showStaffAppts)
                : null,
            'url' => $url,
            'qr_url' => $this->qrImageUrl($url),
            'book' => [
                'join_code' => $joinCode,
                'app_hint' => 'Randevu almak için Seyfibaba uygulamasından Salon Hub → müşteri girişi ile bu salona bağlanın.',
                'deep_link_hint' => 'Uygulamada Salon Hub → Müşteri girişi → berber kodu: '.($joinCode ?: '—'),
            ],
        ];
    }

    private function publicCalendar(SalonCrmSalon $salon, bool $showStaffAppointments): array
    {
        $from = Carbon::now('Europe/Istanbul')->startOfDay();
        $to = $from->copy()->addDays(6)->endOfDay();
        $now = Carbon::now('Europe/Istanbul');

        $query = SalonCrmAppointment::query()
            ->where('salon_id', $salon->id)
            ->where(function ($q) {
                $q->where('is_block', true)->orWhere('status', 'scheduled');
            })
            ->whereBetween('starts_at', [$from, $to])
            ->orderBy('starts_at');

        if (!$showStaffAppointments) {
            // Personel randevuları gizli: yalnızca salon geneli / staff_id null bloklar
            $query->where(function ($q) {
                $q->whereNull('staff_id')->orWhere('is_block', true);
            });
        }

        $rows = $query->get(['starts_at', 'duration_minutes', 'is_block', 'block_type', 'staff_id']);

        $byDate = [];
        foreach ($rows as $row) {
            $start = Carbon::parse($row->starts_at)->timezone('Europe/Istanbul');
            $end = $start->copy()->addMinutes(max(5, (int) ($row->duration_minutes ?: 30)));
            if ($end->lte($now)) {
                continue;
            }
            $dateKey = $start->toDateString();
            $slot = [
                'start' => $start->format('H:i'),
                'end' => $end->format('H:i'),
                'kind' => $row->is_block ? 'closed' : 'busy',
            ];
            if ($showStaffAppointments && $row->staff_id) {
                $slot['staff_id'] = (int) $row->staff_id;
            }
            $byDate[$dateKey][] = $slot;
        }

        $days = [];
        $cursor = $from->copy();
        $last = $to->copy()->startOfDay();
        while ($cursor->lte($last)) {
            $key = $cursor->toDateString();
            $days[] = [
                'date' => $key,
                'label' => $key === $now->toDateString()
                    ? 'Bugün'
                    : ($key === $now->copy()->addDay()->toDateString() ? 'Yarın' : $cursor->format('d.m.Y')),
                'slots' => $byDate[$key] ?? [],
            ];
            $cursor->addDay();
        }

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'open_hour' => (int) ($salon->open_hour ?? 9),
            'close_hour' => (int) ($salon->close_hour ?? 21),
            'days' => $days,
        ];
    }
}
