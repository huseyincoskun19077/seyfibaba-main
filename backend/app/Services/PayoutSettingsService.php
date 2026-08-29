<?php

namespace App\Services;

use App\Models\Setting;

class PayoutSettingsService
{
    public const DEFAULT_AUTO_COMPLETE_DAYS = 15;

    public const DEFAULT_PAYOUT_HOLD_DAYS = 3;

    public const DEFAULT_RETURN_WINDOW_DAYS = 14;

    public function autoCompleteDays(): int
    {
        return max(1, (int) ($this->settings()->auto_complete_days ?? self::DEFAULT_AUTO_COMPLETE_DAYS));
    }

    public function payoutHoldDays(): int
    {
        return max(0, (int) ($this->settings()->payout_hold_days ?? self::DEFAULT_PAYOUT_HOLD_DAYS));
    }

    public function returnWindowDays(): int
    {
        return max(1, (int) ($this->settings()->return_window_days ?? self::DEFAULT_RETURN_WINDOW_DAYS));
    }

    public function iyzicoPayoutDryRun(): bool
    {
        return (bool) ($this->settings()->iyzico_payout_dry_run ?? false);
    }

    public function all(): array
    {
        return [
            'auto_complete_days' => $this->autoCompleteDays(),
            'payout_hold_days' => $this->payoutHoldDays(),
            'return_window_days' => $this->returnWindowDays(),
            'iyzico_payout_dry_run' => $this->iyzicoPayoutDryRun(),
        ];
    }

    protected function settings(): ?Setting
    {
        return Setting::query()->first();
    }
}
