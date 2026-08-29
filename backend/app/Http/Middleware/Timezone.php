<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

class Timezone
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Schema::hasTable('settings')) {
            return $next($request);
        }

        $setting = Setting::first();
        if ($setting && $setting->timezone) {
            try {
                $tz = trim((string) $setting->timezone);
                if ($tz !== '' && in_array($tz, timezone_identifiers_list(), true)) {
                    config(['app.timezone' => $tz]);
                    date_default_timezone_set($tz);
                }
            } catch (\Throwable $e) {
                // Geçersiz timezone tüm istekleri düşürmesin
            }
        }

        return $next($request);
    }
}
