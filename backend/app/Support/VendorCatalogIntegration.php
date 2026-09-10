<?php

namespace App\Support;

use App\Models\VendorSentosSetting;
use App\Models\VendorSofttrSetting;

/**
 * One catalog integration per vendor (Sentos XOR Softtr XOR …).
 * Prevents dual sync / stock conflicts.
 */
class VendorCatalogIntegration
{
    public const SENTOS = 'sentos';

    public const SOFTTR = 'softtr';

    /**
     * Currently active catalog integration key, or null.
     */
    public static function activeKey(int $vendorId): ?string
    {
        $sentos = VendorSentosSetting::query()
            ->where('vendor_id', $vendorId)
            ->where('is_enabled', true)
            ->exists();

        if ($sentos) {
            return self::SENTOS;
        }

        $softtr = VendorSofttrSetting::query()
            ->where('vendor_id', $vendorId)
            ->where('is_enabled', true)
            ->exists();

        if ($softtr) {
            return self::SOFTTR;
        }

        return null;
    }

    public static function label(?string $key): string
    {
        return match ($key) {
            self::SENTOS => 'Sentos',
            self::SOFTTR => 'Softtr',
            'other' => 'Diğer',
            default => $key ? (string) $key : 'Yok',
        };
    }

    /**
     * Can this vendor enable/use the given integration?
     */
    public static function canUse(int $vendorId, string $integrationKey): bool
    {
        $active = self::activeKey($vendorId);

        return $active === null || $active === $integrationKey;
    }

    public static function blockMessage(int $vendorId, string $wantedKey): string
    {
        $active = self::activeKey($vendorId);
        if ($active === null || $active === $wantedKey) {
            return '';
        }

        return sprintf(
            'Bu mağazada zaten %s entegrasyonu açık. Başka bir entegrasyon için önce %s’u kapatın.',
            self::label($active),
            self::label($active)
        );
    }
}
