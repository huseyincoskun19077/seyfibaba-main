<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/seller/dashboard';
    public const ADMIN = '/admin/dashboard';

    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->as('api.')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            // JWT guard default: token yokken user() 500 üretebiliyor.
            $ip = (string) $request->ip();
            $ua = strtolower((string) $request->userAgent());

            // Filigranlı görseller — ürün listelerinde çok istek
            if ($request->is('api/media/wm') || $request->is('media/wm')) {
                return Limit::perMinute(1200)->by('wm:'.$ip);
            }

            // Next.js SSR (çok sekme açınca aynı sunucu IP’si) — sıkı 60/dk 429 → sahte 500 üretir
            $isLoopback = in_array($ip, ['127.0.0.1', '::1'], true);
            $isNodeSsr =
                str_contains($ua, 'node') ||
                str_contains($ua, 'next.js') ||
                str_contains($ua, 'seyfibaba-next-ssr');
            if ($isLoopback || $isNodeSsr) {
                return Limit::perMinute(600)->by('ssr:'.$ip);
            }

            return Limit::perMinute(120)->by($ip);
        });

        RateLimiter::for('auth-login', function (Request $request) {
            return Limit::perMinute(5)->by(($request->input('email') ?: 'guest').'|'.$request->ip());
        });

        RateLimiter::for('auth-register', function (Request $request) {
            return Limit::perMinute(3)->by(($request->input('email') ?: 'guest').'|'.$request->ip());
        });

        RateLimiter::for('auth-otp', function (Request $request) {
            return Limit::perMinute(5)->by(($request->input('email') ?: $request->input('phone') ?: 'guest').'|'.$request->ip());
        });

        RateLimiter::for('otp-send', function (Request $request) {
            return Limit::perMinutes(15, 3)->by(($request->input('phone') ?: 'guest').'|'.$request->ip());
        });

        RateLimiter::for('otp-verify', function (Request $request) {
            return Limit::perMinutes(5, 5)->by($request->ip());
        });

        RateLimiter::for('otp-resend', function (Request $request) {
            return Limit::perMinute(1)->by($request->input('phone') ?: $request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(4)->by(($request->input('email') ?: 'guest').'|'.$request->ip());
        });

        RateLimiter::for('public-form', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}
