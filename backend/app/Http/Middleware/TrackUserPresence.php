<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tymon\JWTAuth\Facades\JWTAuth;

class TrackUserPresence
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! Schema::hasColumn('users', 'last_seen_at')) {
            return $response;
        }

        $user = null;
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Throwable $e) {
            $user = null;
        }

        if (! $user) {
            return $response;
        }

        $platform = strtolower((string) $request->header('X-Client-Platform', ''));
        if (! in_array($platform, ['mobile', 'web'], true)) {
            $ua = strtolower((string) $request->userAgent());
            $platform = (str_contains($ua, 'okhttp') || str_contains($ua, 'dart')) ? 'mobile' : 'web';
        }

        $last = $user->last_seen_at;
        if ($last && $last->gt(now()->subMinutes(2))) {
            return $response;
        }

        $user->forceFill([
            'last_seen_at' => now(),
            'last_seen_platform' => $platform,
        ])->saveQuietly();

        return $response;
    }
};
