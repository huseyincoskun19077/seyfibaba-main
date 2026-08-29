<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class XSSProtection
{
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><ul><ol><li><a><span><h1><h2><h3><h4><h5><h6><blockquote><code><pre>';

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $input = array_filter($request->all());

            array_walk_recursive($input, function (&$value) {
                if (! is_string($value)) {
                    return;
                }

                $sanitized = str_replace(['&lt;', '&gt;'], '', $value);
                $sanitized = strip_tags($sanitized, self::ALLOWED_TAGS);
                $sanitized = preg_replace('/<\s*script[^>]*>.*?<\s*\/\s*script\s*>/is', '', $sanitized) ?? $sanitized;
                $sanitized = preg_replace('/\s+on\w+\s*=\s*(["\']).*?\1/iu', '', $sanitized) ?? $sanitized;
                $sanitized = preg_replace('/\s+on\w+\s*=\s*[^\s>]+/iu', '', $sanitized) ?? $sanitized;
                $sanitized = preg_replace('/\s+style\s*=\s*(["\']).*?\1/iu', '', $sanitized) ?? $sanitized;
                $sanitized = preg_replace('/(href|src)\s*=\s*(["\'])\s*(javascript:|data:).*?\2/iu', '$1=$2#$2', $sanitized) ?? $sanitized;

                $value = $sanitized;
            });

            $request->merge($input);
        } catch (\Throwable $e) {
            // XSS temizliği başarısız olsa da isteği düşürme
        }

        return $next($request);
    }
}
