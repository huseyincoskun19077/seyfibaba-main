<?php

namespace App\Http\Middleware;

use App\Support\GuestCheckout;
use Closure;
use Illuminate\Http\Request;

class EnsureGuestCheckoutEnabled
{
    public function handle(Request $request, Closure $next)
    {
        if (!GuestCheckout::enabled()) {
            return GuestCheckout::disabledResponse();
        }

        return $next($request);
    }
}
