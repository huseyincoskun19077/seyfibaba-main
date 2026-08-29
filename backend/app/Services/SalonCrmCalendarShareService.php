<?php

namespace App\Services;

use App\Models\SalonCrmAppointment;
use App\Models\SalonCrmCalendarShare;
use App\Models\SalonCrmSalon;
use App\Models\SalonCrmStaff;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SalonCrmCalendarShareService
{
    public function publicUrl(string $token): string
    {
        $base = rtrim((string) env('MARKETPLACE_URL', 'https://seyfibaba.com'), '/');

        return $base.'/salon-takvim/'.$token;
    }

    public function ensureShare(SalonCrmSalon $salon, ?int $staffId): SalonCrmCalendarShare
    {
        $query = SalonCrmCalendarShare::query()
            ->where('salon_id', $salon->id);
        if ($staffId) {
            $query->where('staff_id', $staffId);
        } else {
            $query->whereNull('staff_id');
        }

        $share = $query->first();
        if ($share) {
            if (!$share->is_active) {
                $share->is_active = true;
                $share->save();
            }

            return $share;
        }

        return SalonCrmCalendarShare::query()->create([
            'salon_id' => $salon->id,
            'staff_id' => $staffId,
            'token' => $this->newToken(),
            'horizon' => SalonCrmCalendarShare::HORIZON_TODAY_TOMORROW,
            'is_active' => true,
        ]);
    }

    public function updateHorizon(SalonCrmCalendarShare $share, string $horizon): SalonCrmCalendarShare
    {
        $share->horizon = $this->normalizeHorizon($horizon);
        $share->save();

        return $share;
    }

    public function ownerPayload(SalonCrmCalendarShare $share, SalonCrmSalon $salon, ?SalonCrmStaff $staff): array
    {
        return [
            'token' => $share->token,
            'url' => $this->publicUrl($share->token),
            'horizon' => $this->normalizeHorizon($share->horizon),
            'is_active' => (bool) $share->is_active,
            'salon_name' => $salon->name,
            'person_name' => $staff?->name ?: ($salon->owner_name ?: $salon->name),
            'person_role' => $staff ? 'staff' : 'owner',
        ];
    }

    public function publicPayload(string $token): ?array
    {
        $share = SalonCrmCalendarShare::query()
            ->where('token', $token)
            ->where('is_active', true)
            ->first();
        if (!$share) {
            return null;
        }

        $salon = SalonCrmSalon::query()->find($share->salon_id);
        if (!$salon) {
            return null;
        }

        $staff = null;
        if ($share->staff_id) {
            $staff = SalonCrmStaff::query()
                ->where('salon_id', $salon->id)
                ->where('id', $share->staff_id)
                ->where('is_active', true)
                ->first();
            if (!$staff) {
                return null;
            }
        }

        [$from, $to] = $this->range($share->horizon);
        $now = Carbon::now('Europe/Istanbul');

        $rows = SalonCrmAppointment::query()
            ->where('salon_id', $salon->id)
            ->when(
                $staff,
                fn ($q) => $q->where('staff_id', $staff->id),
                fn ($q) => $q->whereNull('staff_id')
            )
            ->where(function ($q) {
                $q->where('is_block', true)
                    ->orWhere('status', 'scheduled');
            })
            ->whereBetween('starts_at', [$from, $to])
            ->orderBy('starts_at')
            ->get(['starts_at', 'duration_minutes', 'is_block', 'block_type']);

        $byDate = [];
        foreach ($rows as $row) {
            $start = Carbon::parse($row->starts_at)->timezone('Europe/Istanbul');
            $end = $start->copy()->addMinutes(max(5, (int) ($row->duration_minutes ?: 30)));
            if ($end->lte($now)) {
                continue;
            }
            $dateKey = $start->toDateString();
            $byDate[$dateKey][] = [
                'start' => $start->format('H:i'),
                'end' => $end->format('H:i'),
                'kind' => $row->is_block ? 'closed' : 'busy',
            ];
        }

        $days = [];
        $cursor = $from->copy()->startOfDay();
        $last = $to->copy()->startOfDay();
        while ($cursor->lte($last)) {
            $key = $cursor->toDateString();
            $days[] = [
                'date' => $key,
                'label' => $this->dayLabel($cursor, $now),
                'slots' => $byDate[$key] ?? [],
            ];
            $cursor->addDay();
        }

        return [
            'salon_name' => $salon->name,
            'person_name' => $staff?->name ?: ($salon->owner_name ?: $salon->name),
            'person_role' => $staff ? 'staff' : 'owner',
            'horizon' => $this->normalizeHorizon($share->horizon),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'open_hour' => (int) ($salon->open_hour ?? 9),
            'close_hour' => (int) ($salon->close_hour ?? 21),
            'days' => $days,
            'book_message' => 'Randevu almak için Seyfibaba uygulamasını indirmeniz gerekir. Bu sayfada yalnızca dolu ve boş saatleri canlı takip edebilirsiniz.',
        ];
    }

    /**
     * @return array{0:Carbon,1:Carbon}
     */
    public function range(string $horizon): array
    {
        $from = Carbon::now('Europe/Istanbul')->startOfDay();
        $h = $this->normalizeHorizon($horizon);
        $to = match ($h) {
            SalonCrmCalendarShare::HORIZON_WEEK => $from->copy()->addDays(6)->endOfDay(),
            SalonCrmCalendarShare::HORIZON_MONTH => $from->copy()->addMonth()->subDay()->endOfDay(),
            default => $from->copy()->addDay()->endOfDay(),
        };

        return [$from, $to];
    }

    public function normalizeHorizon(string $horizon): string
    {
        return in_array($horizon, [
            SalonCrmCalendarShare::HORIZON_TODAY_TOMORROW,
            SalonCrmCalendarShare::HORIZON_WEEK,
            SalonCrmCalendarShare::HORIZON_MONTH,
        ], true) ? $horizon : SalonCrmCalendarShare::HORIZON_TODAY_TOMORROW;
    }

    private function dayLabel(Carbon $day, Carbon $now): string
    {
        $d = $day->toDateString();
        if ($d === $now->toDateString()) {
            return 'Bugün';
        }
        if ($d === $now->copy()->addDay()->toDateString()) {
            return 'Yarın';
        }

        return $day->format('d.m.Y').' '.$this->weekdayTr($day);
    }

    private function weekdayTr(Carbon $day): string
    {
        return match ((int) $day->dayOfWeek) {
            1 => 'Pazartesi',
            2 => 'Salı',
            3 => 'Çarşamba',
            4 => 'Perşembe',
            5 => 'Cuma',
            6 => 'Cumartesi',
            default => 'Pazar',
        };
    }

    private function newToken(): string
    {
        do {
            $token = Str::lower(Str::random(22));
        } while (SalonCrmCalendarShare::query()->where('token', $token)->exists());

        return $token;
    }
}
