<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Image;
use RuntimeException;
use Throwable;

class ProductImageStorage
{
    public const DIRECTORY = 'uploads/custom-images';

    /**
     * Store under public/uploads/custom-images (same path as existing products).
     * Prefer Intervention Image (eski davranış); GD/Imagick yoksa dosyayı doğrudan taşı.
     */
    public function store(UploadedFile $file, string $filenamePrefix = 'product'): string
    {
        $uploadDir = public_path(self::DIRECTORY);
        if (! File::isDirectory($uploadDir)) {
            File::makeDirectory($uploadDir, 0755, true);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $extension = 'jpg';
        }

        $basename = Str::slug($filenamePrefix) ?: 'product';
        $relativePath = self::DIRECTORY.'/'.$basename.date('-Y-m-d-h-i-s-').rand(999, 9999).'.'.$extension;
        $absolutePath = public_path($relativePath);

        // 1) Eski çalışan yol: Intervention Image
        try {
            $image = Image::make($file);
            try {
                $image->orientate();
            } catch (Throwable $ignored) {
            }
            $image->save($absolutePath);

            return $relativePath;
        } catch (Throwable $interventionError) {
            Log::warning('Product image Intervention save failed; falling back to move', [
                'message' => $interventionError->getMessage(),
                'path' => $relativePath,
            ]);
        }

        // 2) Yedek: doğrudan taşı (GD şart değil)
        try {
            if (File::exists($absolutePath)) {
                @unlink($absolutePath);
            }

            // move() UploadedFile'ı tüketir; copy + unlink daha güvenli olabilir
            if (! @copy($file->getRealPath(), $absolutePath)) {
                $file->move($uploadDir, basename($relativePath));
            }

            if (! File::exists($absolutePath) || filesize($absolutePath) === 0) {
                throw new RuntimeException('Dosya kaydedilemedi: '.$absolutePath);
            }

            return $relativePath;
        } catch (Throwable $moveError) {
            Log::error('Product image upload failed after fallback', [
                'message' => $moveError->getMessage(),
                'dir' => $uploadDir,
                'dir_exists' => File::isDirectory($uploadDir),
                'writable' => @is_writable($uploadDir),
                'gd' => extension_loaded('gd'),
                'imagick' => extension_loaded('imagick'),
            ]);

            throw new RuntimeException(
                'Kapak görseli kaydedilemedi. Sunucuda şu klasöre yazma izni verin: '.self::DIRECTORY,
                0,
                $moveError
            );
        }
    }

    /**
     * Download image from URL and store locally. Returns null on failure.
     */
    public function storeFromUrl(string $url, string $filenamePrefix = 'product', int $timeoutSeconds = 30): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        $url = $this->normalizeImageUrl($url);
        if ($url === null) {
            return null;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout($timeoutSeconds)
                ->withOptions(['allow_redirects' => true])
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                    'Accept' => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                    'Accept-Language' => 'tr-TR,tr;q=0.9,en-US;q=0.8,en;q=0.7',
                ])
                ->get($url);

            if (! $response->successful()) {
                Log::warning('Product image URL HTTP error', ['url' => $url, 'status' => $response->status()]);

                return null;
            }

            $body = $response->body();
            if ($body === '' || strlen($body) > 8 * 1024 * 1024) {
                return null;
            }

            if (! $this->looksLikeImage($body, (string) $response->header('Content-Type'))) {
                Log::warning('Product image URL response is not an image', ['url' => $url]);

                return null;
            }

            $extension = $this->guessExtension($url, (string) $response->header('Content-Type'));

            $uploadDir = public_path(self::DIRECTORY);
            if (! File::isDirectory($uploadDir)) {
                File::makeDirectory($uploadDir, 0755, true);
            }

            $basename = Str::slug($filenamePrefix) ?: 'product';
            $relativePath = self::DIRECTORY.'/'.$basename.date('-Y-m-d-h-i-s-').rand(999, 9999).'.'.$extension;
            $absolutePath = public_path($relativePath);

            File::put($absolutePath, $body);

            if (! File::exists($absolutePath) || filesize($absolutePath) === 0) {
                return null;
            }

            return $relativePath;
        } catch (Throwable $e) {
            Log::warning('Product image URL download failed', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function hasLocalImage(?string $relativePath): bool
    {
        if ($relativePath === null || trim($relativePath) === '') {
            return false;
        }

        $absolutePath = public_path(ltrim($relativePath, '/'));

        return File::exists($absolutePath) && filesize($absolutePath) > 0;
    }

    private function normalizeImageUrl(string $url): ?string
    {
        if (! preg_match('/^https?:\/\//i', $url) && preg_match('/^\/\//', $url)) {
            $url = 'https:' . $url;
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        if (! preg_match('/^https?:\/\//i', $url)) {
            return null;
        }

        return $url;
    }

    private function guessExtension(string $url, string $contentType): string
    {
        $contentType = strtolower($contentType);
        if (str_contains($contentType, 'png')) {
            return 'png';
        }
        if (str_contains($contentType, 'webp')) {
            return 'webp';
        }
        if (str_contains($contentType, 'gif')) {
            return 'gif';
        }
        if (str_contains($contentType, 'jpeg') || str_contains($contentType, 'jpg')) {
            return 'jpg';
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '';
        if (preg_match('/\.(jpe?g|png|webp|gif)(?:\?|$)/i', $path, $m)) {
            return strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
        }

        return 'jpg';
    }

    private function looksLikeImage(string $body, string $contentType): bool
    {
        $contentType = strtolower($contentType);
        if ($contentType !== '' && (str_starts_with($contentType, 'image/') || str_contains($contentType, 'octet-stream'))) {
            if (str_starts_with($contentType, 'image/')) {
                return true;
            }
        }

        if (function_exists('getimagesizefromstring')) {
            $info = @getimagesizefromstring($body);

            return is_array($info) && ! empty($info[0]);
        }

        return str_starts_with($body, "\xFF\xD8\xFF")
            || str_starts_with($body, "\x89PNG")
            || str_starts_with($body, 'GIF')
            || str_starts_with($body, 'RIFF');
    }
}
