<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;

/**
 * Ürün görsellerini "Kuaför Tedarik" filigranı ile sunar.
 * Sağ tık → Kaydet ile indirilen dosyada da filigran kalır.
 * Orijinal uploads/ dosyasına dokunmaz; cache: storage/app/public/wm-cache
 */
class ProductImageWatermarkController extends Controller
{
    private const WATERMARK_TEXT = 'Kuaför Tedarik';

    private const CACHE_VERSION = 'v2';

    public function show(Request $request)
    {
        $rel = $this->normalizePath((string) $request->query('path', ''));
        if ($rel === null) {
            abort(404);
        }

        $source = public_path($rel);
        if (! is_file($source) || ! $this->isAllowedImage($source)) {
            abort(404);
        }

        $cacheRel = $this->cacheRelativePath($rel, $source);
        $cacheFull = storage_path('app/public/'.$cacheRel);

        if (! is_file($cacheFull) || filemtime($cacheFull) < filemtime($source)) {
            $this->buildWatermarked($source, $cacheFull);
        }

        $mime = mime_content_type($cacheFull) ?: 'image/jpeg';

        return response()->file($cacheFull, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=604800, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function normalizePath(string $raw): ?string
    {
        $raw = trim(rawurldecode($raw));
        $raw = str_replace('\\', '/', $raw);
        $raw = ltrim($raw, '/');

        // Absolute own-domain URL → path
        if (preg_match('#^https?://[^/]+/(.+)$#i', $raw, $m)) {
            $raw = $m[1];
        }

        if ($raw === '' || str_contains($raw, '..')) {
            return null;
        }

        // Yalnızca uploads altı (ürün/galeri)
        if (! str_starts_with($raw, 'uploads/')) {
            return null;
        }

        // Çok büyük / şüpheli uzantılar
        $ext = strtolower(pathinfo($raw, PATHINFO_EXTENSION));
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            return null;
        }

        return $raw;
    }

    private function isAllowedImage(string $fullPath): bool
    {
        $real = realpath($fullPath);
        $uploadsRoot = realpath(public_path('uploads'));
        if ($real === false || $uploadsRoot === false) {
            return false;
        }

        return str_starts_with($real, $uploadsRoot.DIRECTORY_SEPARATOR)
            || $real === $uploadsRoot;
    }

    private function cacheRelativePath(string $rel, string $source): string
    {
        $hash = substr(sha1($rel.'|'.filesize($source).'|'.filemtime($source).'|'.self::CACHE_VERSION), 0, 20);
        $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION)) ?: 'jpg';
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }

        return 'wm-cache/'.self::CACHE_VERSION.'/'.$hash.'.'.$ext;
    }

    private function buildWatermarked(string $source, string $dest): void
    {
        $dir = dirname($dest);
        if (! is_dir($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $img = Image::make($source);
        $w = $img->width();
        $h = $img->height();
        if ($w < 40 || $h < 40) {
            $img->save($dest, 88);
            return;
        }

        $fontSize = max(14, (int) round(min($w, $h) * 0.045));
        $pad = max(10, (int) round($fontSize * 0.6));
        $text = self::WATERMARK_TEXT;
        $fontFile = $this->resolveFont();

        // Gölge + beyaz metin (sağ alt) — indirilen JPG'de kalır
        $tx = $w - $pad;
        $ty = $h - $pad;
        $img->text($text, $tx + 1, $ty + 1, function ($font) use ($fontSize, $fontFile) {
            if ($fontFile) {
                $font->file($fontFile);
            }
            $font->size($fontSize);
            $font->color('rgba(0,0,0,0.55)');
            $font->align('right');
            $font->valign('bottom');
        });
        $img->text($text, $tx, $ty, function ($font) use ($fontSize, $fontFile) {
            if ($fontFile) {
                $font->file($fontFile);
            }
            $font->size($fontSize);
            $font->color('#ffffff');
            $font->align('right');
            $font->valign('bottom');
        });

        // Orta diagonal hafif damga
        $diagSize = max(12, (int) round(min($w, $h) * 0.038));
        $img->text($text, (int) round($w * 0.5), (int) round($h * 0.52), function ($font) use ($diagSize, $fontFile) {
            if ($fontFile) {
                $font->file($fontFile);
            }
            $font->size($diagSize);
            $font->color('rgba(4,51,74,0.28)');
            $font->align('center');
            $font->valign('center');
            $font->angle(30);
        });

        $ext = strtolower(pathinfo($dest, PATHINFO_EXTENSION));
        if (in_array($ext, ['png', 'webp'], true)) {
            $img->save($dest);
        } else {
            $img->save($dest, 88);
        }
    }

    private function resolveFont(): ?string
    {
        $candidates = [
            public_path('fonts/DejaVuSans.ttf'),
            public_path('backend/fonts/DejaVuSans.ttf'),
            storage_path('fonts/DejaVuSans.ttf'),
            // Linux
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            // Windows (yerel geliştirme)
            'C:/Windows/Fonts/arial.ttf',
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
