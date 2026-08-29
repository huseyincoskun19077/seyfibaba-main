<?php

namespace App\Services\CallCenter;

use App\Models\Vendor;

/**
 * Çağrı merkezi temsilcisi hakediş hesaplama.
 *
 * Kurallar (satıcı başına, call_center kaynağı):
 * - KYC onaylı + en az 1 ürün → 160 TL (tek seferlik bileşen)
 * - Ürün başı 3 TL, en fazla 200 ürün sayılır
 * - Her 100 ürün tamamlandığında +200 TL, toplam ürün cap 500
 */
class CallCenterCommissionCalculator
{
    public const BASE_BONUS = 160;

    public const PER_PRODUCT_RATE = 3;

    public const PER_PRODUCT_CAP = 200;

    public const MILESTONE_EVERY = 100;

    public const MILESTONE_BONUS = 200;

    public const PRODUCT_CAP = 500;

    /**
     * @return array{
     *     eligible: bool,
     *     product_count: int,
     *     capped_product_count: int,
     *     kyc_approved: bool,
     *     base_amount: float,
     *     per_product_amount: float,
     *     per_product_units: int,
     *     milestone_amount: float,
     *     milestone_count: int,
     *     total: float,
     *     summary: string
     * }
     */
    public function calculate(Vendor $vendor, ?int $productCount = null): array
    {
        if (! $vendor->isCallCenterRegistration()) {
            return $this->emptyResult();
        }

        $productCount = $productCount ?? (int) $vendor->products()->count();
        $cappedCount = min($productCount, self::PRODUCT_CAP);
        $kycApproved = ($vendor->kyc_status ?? '') === 'approved';

        $baseAmount = ($kycApproved && $cappedCount >= 1) ? (float) self::BASE_BONUS : 0.0;

        $perProductUnits = min($cappedCount, self::PER_PRODUCT_CAP);
        $perProductAmount = $perProductUnits * self::PER_PRODUCT_RATE;

        $milestoneCount = intdiv($cappedCount, self::MILESTONE_EVERY);
        $milestoneAmount = $milestoneCount * self::MILESTONE_BONUS;

        $total = $baseAmount + $perProductAmount + $milestoneAmount;

        return [
            'eligible' => $total > 0,
            'product_count' => $productCount,
            'capped_product_count' => $cappedCount,
            'kyc_approved' => $kycApproved,
            'base_amount' => $baseAmount,
            'per_product_amount' => (float) $perProductAmount,
            'per_product_units' => $perProductUnits,
            'milestone_amount' => (float) $milestoneAmount,
            'milestone_count' => $milestoneCount,
            'total' => (float) $total,
            'summary' => $this->buildSummary($baseAmount, $perProductUnits, $milestoneCount, $total),
        ];
    }

    protected function buildSummary(float $base, int $perProductUnits, int $milestones, float $total): string
    {
        if ($total <= 0) {
            return 'Henüz hakediş oluşmadı (KYC + ürün gerekli)';
        }

        $parts = [];
        if ($base > 0) {
            $parts[] = '160 TL kayıt';
        }
        if ($perProductUnits > 0) {
            $parts[] = "{$perProductUnits}×3 TL ürün";
        }
        if ($milestones > 0) {
            $parts[] = "{$milestones}×200 TL milestone";
        }

        return implode(' + ', $parts).' = '.number_format($total, 2, ',', '.').' TL';
    }

    /**
     * @return array{
     *     eligible: bool,
     *     product_count: int,
     *     capped_product_count: int,
     *     kyc_approved: bool,
     *     base_amount: float,
     *     per_product_amount: float,
     *     per_product_units: int,
     *     milestone_amount: float,
     *     milestone_count: int,
     *     total: float,
     *     summary: string
     * }
     */
    protected function emptyResult(): array
    {
        return [
            'eligible' => false,
            'product_count' => 0,
            'capped_product_count' => 0,
            'kyc_approved' => false,
            'base_amount' => 0.0,
            'per_product_amount' => 0.0,
            'per_product_units' => 0,
            'milestone_amount' => 0.0,
            'milestone_count' => 0,
            'total' => 0.0,
            'summary' => 'Çağrı merkezi kaydı değil',
        ];
    }
}
