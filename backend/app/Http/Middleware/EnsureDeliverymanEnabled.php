<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureDeliverymanEnabled
{
    public function handle(Request $request, Closure $next)
    {
        if (! config('features.deliveryman_enabled', false)) {
            abort(404);
        }

        return $next($request);
    }
}
