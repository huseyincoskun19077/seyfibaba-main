<?php

namespace App\Support;

use Illuminate\Support\Str;

class ProductSlug
{
    protected const LEGACY_SLUG_MAP = [
        'bayan-kuafr-tarama-koltuu-profesyonel-kuafr-tarama-' => 'bayan-kuaforu-tarama-koltugu-profesyonel-kuafor-tarama',
        'bayan-kuafr-tarama-koltuu-profesyonel-kuafr-tarama' => 'bayan-kuaforu-tarama-koltugu-profesyonel-kuafor-tarama',
        'ikili-erkek-kuafr-tezgh-profesyonel-berber-alma-tezgh' => 'ikili-erkek-kuafor-tezgahi-profesyonel-berber-calisma-tezgahi',
        'erkek-kuafr-koltuu-profesyonel-berber-koltuu' => 'erkek-kuafor-koltugu-profesyonel-berber-koltugu',
        'fonex-briyantin-saa-parlaklk-ve-ekil-veren-sa-ekillendirici' => 'fonex-briyantin-saca-parlaklik-ve-sekil-veren-sac-sekillendirici',
        'zenix-yz-maskesi-cilt-temizleyici-ve-bakm-maskesi' => 'zenix-yuz-maskesi-cilt-temizleyici-ve-bakim-maskesi',
        'erkek-kuafr-tra-seti-profesyonel-berber-ekipman-paketi' => 'erkek-kuafor-tiras-seti-profesyonel-berber-ekipman-paketi',
        'profesyonel-erkek-kuafr-koltuu' => 'profesyonel-erkek-kuafor-koltugu',
    ];

    public static function normalize(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $value = rawurldecode($value);
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = strtr($value, [
            'İ' => 'I',
            'I' => 'I',
            'ı' => 'i',
            'Ş' => 'S',
            'ş' => 's',
            'Ğ' => 'G',
            'ğ' => 'g',
            'Ü' => 'U',
            'ü' => 'u',
            'Ö' => 'O',
            'ö' => 'o',
            'Ç' => 'C',
            'ç' => 'c',
            'â' => 'a', 'Â' => 'A',
            'î' => 'i', 'Î' => 'I',
            'û' => 'u', 'Û' => 'U',
        ]);

        return Str::slug(Str::ascii($value));
    }

    public static function candidates(?string $value): array
    {
        $value = trim((string) $value);

        if ($value === '') {
            return [];
        }

        $decodedValue = Str::lower(rawurldecode($value));
        $legacySlug = self::LEGACY_SLUG_MAP[$decodedValue] ?? null;

        return collect([
            Str::lower($value),
            $decodedValue,
            $legacySlug,
            self::normalize($value),
            Str::slug(Str::ascii($decodedValue)),
        ])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function tagsToText($tags): string
    {
        if (!$tags) {
            return '';
        }

        if (is_array($tags)) {
            return collect($tags)
                ->map(function ($tag) {
                    if (is_array($tag)) {
                        return trim((string) ($tag['value'] ?? ''));
                    }

                    return trim((string) $tag);
                })
                ->filter()
                ->implode(',');
        }

        $decoded = json_decode((string) $tags, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return collect($decoded)
                ->map(function ($tag) {
                    if (is_array($tag)) {
                        return trim((string) ($tag['value'] ?? ''));
                    }

                    return trim((string) $tag);
                })
                ->filter()
                ->implode(',');
        }

        return collect(preg_split('/\s*,\s*/', (string) $tags) ?: [])
            ->map(fn ($tag) => trim((string) $tag))
            ->filter()
            ->implode(',');
    }
}
