<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

class ProductImageUrl
{
    public static function isExternal(?string $path): bool
    {
        $path = trim((string) $path);

        if ($path === '') {
            return false;
        }

        return (bool) preg_match('/^https?:\/\//i', $path) || str_starts_with($path, '//');
    }

    /**
     * Excel / import için: geçerli harici görsel URL'si ise olduğu gibi döndür.
     */
    public static function normalizeForStorage(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '//')) {
            $url = 'https:' . $url;
        }

        if (! filter_var($url, FILTER_VALIDATE_URL) || ! preg_match('/^https?:\/\//i', $url)) {
            return null;
        }

        return $url;
    }

    public static function resolve(?string $path): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        if (self::isExternal($path)) {
            return str_starts_with($path, '//') ? 'https:' . $path : $path;
        }

        return asset(ltrim($path, '/'));
    }

    public static function hasImage(?string $path): bool
    {
        $path = trim((string) $path);
        if ($path === '') {
            return false;
        }

        if (self::isExternal($path)) {
            return true;
        }

        $absolute = public_path(ltrim($path, '/'));

        return File::exists($absolute) && filesize($absolute) > 0;
    }
}
