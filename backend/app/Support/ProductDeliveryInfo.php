<?php

namespace App\Support;

final class ProductDeliveryInfo
{
    public static function normalize(mixed $value): ?string
    {
        $text = trim(strip_tags((string) ($value ?? '')));
        if ($text === '') {
            return null;
        }

        return mb_substr($text, 0, 500);
    }

    /** @return list<string> */
    public static function presets(): array
    {
        // Metinler ileride etiket için kullanılır:
        // - "bugün kargoda" → Bugün kargoda
        // - "özel üretim" → Özel üretim (cayma/iade istisnası adayı)
        return [
            'Bugün kargoda',
            "Saat 15:00'e kadar verilen siparişler bugün kargoda",
            '1 iş günü içinde kargoya verilir',
            '2-3 iş günü içinde kargoya verilir',
            'Özel üretim — 7-14 gün içinde siparişe / kargoya verilir',
            'Özel üretim — 15-30 gün içinde siparişe / kargoya verilir',
            'Özel üretim — yaklaşık 7 gün sonra kargoya verilir',
            'Özel üretim — yaklaşık 15 gün sonra kargoya verilir',
            'Özel üretim — ölçüye / siparişe özel; üretim sonrası kargoya verilir',
        ];
    }

    public static function isSameDayShip(?string $value): bool
    {
        $text = self::fold($value);
        if ($text === '') {
            return false;
        }

        return str_contains($text, 'bugün kargoda')
            || str_contains($text, 'bugun kargoda')
            || str_contains($text, 'aynı gün kargo')
            || str_contains($text, 'ayni gun kargo');
    }

    public static function isCustomProduction(?string $value): bool
    {
        $text = self::fold($value);
        if ($text === '') {
            return false;
        }

        return str_contains($text, 'özel üretim')
            || str_contains($text, 'ozel uretim')
            || str_contains($text, 'siparişe özel')
            || str_contains($text, 'siparise ozel')
            || str_contains($text, 'ölçüye')
            || str_contains($text, 'olcuye')
            || str_contains($text, '7-14')
            || str_contains($text, '15-30');
    }

    private static function fold(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }
}
