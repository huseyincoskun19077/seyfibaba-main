<?php

namespace App\Helpers;

/**
 * Kuaför Tedarik tek fiyat formatı (TR).
 * Örnek: ₺1.518,00  |  indirim: -₺62,69
 *
 * Web `frontend/src/utils/priceFormat.js` ile aynı kurallar.
 */
class PriceFormat
{
    public const SYMBOL = '₺';

    public static function money(mixed $value, bool $forceMinus = false): string
    {
        $n = self::toNumber($value);
        $abs = abs($n);
        $formatted = number_format($abs, 2, ',', '.');

        if ($forceMinus || $n < 0) {
            return '-'.self::SYMBOL.$formatted;
        }

        return self::SYMBOL.$formatted;
    }

    /**
     * @return array{current: float, list: ?float, savings: ?float, has_discount: bool, current_formatted: string, list_formatted: ?string, savings_formatted: ?string}
     */
    public static function block(mixed $listPrice, mixed $salePrice = null): array
    {
        $list = self::toNumber($listPrice);
        $sale = $salePrice !== null && $salePrice !== '' ? self::toNumber($salePrice) : null;
        $hasDiscount = $sale !== null && $sale > 0 && $list > 0 && $sale < $list;
        $current = $hasDiscount ? $sale : $list;
        $savings = $hasDiscount ? round($list - $current, 2) : null;

        return [
            'current' => $current,
            'list' => $hasDiscount ? $list : null,
            'savings' => $savings,
            'has_discount' => $hasDiscount,
            'current_formatted' => self::money($current),
            'list_formatted' => $hasDiscount ? self::money($list) : null,
            'savings_formatted' => ($hasDiscount && $savings > 0) ? self::money($savings, true) : null,
        ];
    }

    /**
     * E-posta / admin HTML bloğu.
     */
    public static function htmlBlock(mixed $listPrice, mixed $salePrice = null): string
    {
        $b = self::block($listPrice, $salePrice);
        $parts = [];
        if ($b['savings_formatted']) {
            $parts[] = '<div style="color:#e11d48;font-weight:700;font-size:12px;">'.e($b['savings_formatted']).'</div>';
        }
        if ($b['list_formatted']) {
            $parts[] = '<div style="color:#9ca3af;font-weight:500;font-size:12px;text-decoration:line-through;">'.e($b['list_formatted']).'</div>';
        }
        $parts[] = '<div style="color:#04334a;font-weight:800;font-size:16px;">'.e($b['current_formatted']).'</div>';

        return '<div style="display:inline-block;text-align:left;line-height:1.25;font-variant-numeric:tabular-nums;">'.implode('', $parts).'</div>';
    }

    public static function toNumber(mixed $value): float
    {
        if (! is_numeric($value)) {
            return 0.0;
        }

        return round((float) $value, 2);
    }
}
