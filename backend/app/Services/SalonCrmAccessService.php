<?php

namespace App\Services;

use App\Models\Order;
use App\Models\SalonCrmAccessGrant;
use App\Models\SalonCrmSalon;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SalonCrmAccessService
{
    /**
     * Evaluate marketplace spend and apply B-rule grants, then return access snapshot.
     *
     * B rule:
     * - Locked + 10k → immediate_unlock for current month only (no next month).
     * - Already open via trial or next_month_credit + 10k → next_month_credit for next month.
     * - Open only via immediate_unlock → that spend does not earn next month.
     */
    public function snapshot(?SalonCrmSalon $salon, ?User $user = null): array
    {
        try {
            return $this->buildSnapshot($salon, $user);
        } catch (\Throwable $e) {
            Log::error('Salon CRM snapshot', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            if (!$salon) {
                return $this->emptySnapshot();
            }

            return [
                'has_salon' => true,
                'salon' => [
                    'id' => $salon->id,
                    'name' => $salon->name,
                    'owner_name' => $salon->owner_name,
                    'owner_username' => $salon->owner_username,
                    'type' => $salon->type,
                    'phone' => $salon->phone,
                    'open_hour' => $this->salonOpenHour($salon),
                    'close_hour' => $this->salonCloseHour($salon),
                    'trial_ends_at' => optional($salon->trial_ends_at)->toIso8601String(),
                    'created_at' => optional($salon->created_at)->toIso8601String(),
                ],
                'access' => [
                    'is_unlocked' => true,
                    'reason' => 'trial',
                    'can_write' => true,
                    'can_read_history' => true,
                    'trial_ends_at' => optional($salon->trial_ends_at)->toIso8601String(),
                    'in_trial' => true,
                    'period' => now()->format('Y-m'),
                    'month_spend' => 0,
                    'threshold' => (int) ($salon->threshold_amount ?: 10000),
                    'remaining_to_unlock' => 0,
                    'message' => 'Deneme süreniz aktif.',
                ],
            ];
        }
    }

    private function emptySnapshot(): array
    {
        return [
            'has_salon' => false,
            'salon' => null,
            'access' => [
                'is_unlocked' => false,
                'reason' => 'no_salon',
                'can_write' => false,
                'can_read_history' => false,
                'trial_ends_at' => null,
                'in_trial' => false,
                'period' => now()->format('Y-m'),
                'month_spend' => 0,
                'threshold' => 10000,
                'remaining_to_unlock' => 10000,
                'message' => 'Salon kaydı bulunamadı.',
            ],
        ];
    }

    private function buildSnapshot(?SalonCrmSalon $salon, ?User $user = null): array
    {
        if (!$salon) {
            return $this->emptySnapshot();
        }

        if ($user) {
            $this->applySpendGrants($salon, $user);
        }

        $now = Carbon::now();
        $period = $now->format('Y-m');
        $threshold = (int) ($salon->threshold_amount ?: 10000);
        $monthSpend = $user ? $this->monthSpend($user->id, $now) : 0;
        $inTrial = $salon->trial_ends_at && $now->lte($salon->trial_ends_at);
        $adminFree = $salon->admin_free_until && $now->lte($salon->admin_free_until);
        $hasImmediate = $this->hasGrant($salon->id, $period, SalonCrmAccessGrant::TYPE_IMMEDIATE_UNLOCK);
        $hasCredit = $this->hasGrant($salon->id, $period, SalonCrmAccessGrant::TYPE_NEXT_MONTH_CREDIT);

        $isUnlocked = $adminFree || $inTrial || $hasImmediate || $hasCredit;
        $reason = 'locked';
        if ($adminFree) {
            $reason = 'admin_free';
        } elseif ($inTrial) {
            $reason = 'trial';
        } elseif ($hasCredit) {
            $reason = 'next_month_credit';
        } elseif ($hasImmediate) {
            $reason = 'immediate_unlock';
        }

        $remaining = max(0, $threshold - $monthSpend);

        return [
            'has_salon' => true,
            'salon' => [
                'id' => $salon->id,
                'name' => $salon->name,
                'owner_name' => $salon->owner_name,
                'owner_username' => $salon->owner_username,
                'type' => $salon->type,
                'phone' => $salon->phone,
                'open_hour' => $this->salonOpenHour($salon),
                'close_hour' => $this->salonCloseHour($salon),
                'trial_ends_at' => optional($salon->trial_ends_at)->toIso8601String(),
                'admin_free_until' => optional($salon->admin_free_until)->toIso8601String(),
                'created_at' => optional($salon->created_at)->toIso8601String(),
            ],
            'access' => [
                'is_unlocked' => $isUnlocked,
                'reason' => $reason,
                'can_write' => $isUnlocked,
                'can_read_history' => true,
                'trial_ends_at' => optional($salon->trial_ends_at)->toIso8601String(),
                'admin_free_until' => optional($salon->admin_free_until)->toIso8601String(),
                'in_trial' => $inTrial,
                'admin_free' => $adminFree,
                'period' => $period,
                'month_spend' => round($monthSpend, 2),
                'threshold' => $threshold,
                'remaining_to_unlock' => round($remaining, 2),
                'message' => $this->message($isUnlocked, $reason, $remaining, $threshold),
            ],
        ];
    }

    public function applySpendGrants(SalonCrmSalon $salon, User $user): void
    {
        $now = Carbon::now();
        $period = $now->format('Y-m');
        $nextPeriod = $now->copy()->addMonthNoOverflow()->format('Y-m');
        $threshold = (float) ($salon->threshold_amount ?: 10000);
        $monthSpend = $this->monthSpend($user->id, $now);

        if ($monthSpend < $threshold) {
            return;
        }

        $inTrial = $salon->trial_ends_at && $now->lte($salon->trial_ends_at);
        $hasImmediate = $this->hasGrant($salon->id, $period, SalonCrmAccessGrant::TYPE_IMMEDIATE_UNLOCK);
        $hasCredit = $this->hasGrant($salon->id, $period, SalonCrmAccessGrant::TYPE_NEXT_MONTH_CREDIT);
        $openWithoutImmediate = $inTrial || $hasCredit;

        if (!$openWithoutImmediate && !$hasImmediate) {
            SalonCrmAccessGrant::query()->firstOrCreate(
                [
                    'salon_id' => $salon->id,
                    'period' => $period,
                    'type' => SalonCrmAccessGrant::TYPE_IMMEDIATE_UNLOCK,
                ],
                ['qualified_amount' => $monthSpend]
            );
            return;
        }

        if ($openWithoutImmediate) {
            SalonCrmAccessGrant::query()->firstOrCreate(
                [
                    'salon_id' => $salon->id,
                    'period' => $nextPeriod,
                    'type' => SalonCrmAccessGrant::TYPE_NEXT_MONTH_CREDIT,
                ],
                ['qualified_amount' => $monthSpend]
            );
        }
    }

    public function monthSpend(int $userId, Carbon $month): float
    {
        try {
            if (!Schema::hasTable('orders')) {
                return 0;
            }

            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();
            $hasTotal = Schema::hasColumn('orders', 'total_amount');
            $hasReal = Schema::hasColumn('orders', 'amount_real_currency');
            if ($hasTotal) {
                $amountSql = 'COALESCE(orders.total_amount, 0)';
            } elseif ($hasReal) {
                $amountSql = 'COALESCE(orders.amount_real_currency, 0)';
            } else {
                return 0;
            }

            $sum = Order::query()
                ->where('user_id', $userId)
                ->where('payment_status', 1)
                ->whereBetween('created_at', [$start, $end])
                ->value(DB::raw("COALESCE(SUM($amountSql), 0)"));

            return (float) $sum;
        } catch (\Throwable $e) {
            Log::error('Salon CRM monthSpend', ['message' => $e->getMessage()]);
            return 0;
        }
    }

    private function hasGrant(int $salonId, string $period, string $type): bool
    {
        if (!Schema::hasTable('salon_crm_access_grants')) {
            return false;
        }

        return SalonCrmAccessGrant::query()
            ->where('salon_id', $salonId)
            ->where('period', $period)
            ->where('type', $type)
            ->exists();
    }

    private function message(bool $unlocked, string $reason, float $remaining, int $threshold): string
    {
        if ($unlocked) {
            return match ($reason) {
                'admin_free' => 'Admin tarafından ücretsiz erişim verildi.',
                'trial' => 'Deneme süreniz aktif.',
                'next_month_credit' => 'Bu ay ücretsiz erişiminiz aktif.',
                'immediate_unlock' => 'Bu ay alışveriş eşiği ile erişim açıldı.',
                default => 'CRM erişiminiz açık.',
            };
        }

        if ($remaining > 0) {
            return sprintf(
                'CRM kilitli. Açmak için bu ay en az %s TL ürün siparişi verin (kalan: %s TL).',
                number_format($threshold, 0, ',', '.'),
                number_format($remaining, 0, ',', '.')
            );
        }

        return 'CRM kilitli.';
    }

    private function salonOpenHour(SalonCrmSalon $salon): int
    {
        if (!Schema::hasColumn('salon_crm_salons', 'open_hour')) {
            return 9;
        }
        $hour = (int) ($salon->open_hour ?? 9);

        return max(0, min(23, $hour));
    }

    private function salonCloseHour(SalonCrmSalon $salon): int
    {
        if (!Schema::hasColumn('salon_crm_salons', 'close_hour')) {
            return 21;
        }
        $hour = (int) ($salon->close_hour ?? 21);

        return max(0, min(23, $hour));
    }
}
