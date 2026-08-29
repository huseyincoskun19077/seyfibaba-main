<?php
// Debug middleware for guest checkout
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DebugGuestCheckout
{
    public function handle(Request $request, Closure $next)
    {
        // Log incoming address data
        if ($request->is('api/user/checkout/guest/*')) {
            \Log::info('Guest checkout request:', [
                'address' => $request->input('address'),
                'country' => $request->input('address.country'),
                'state' => $request->input('address.state'),
                'city' => $request->input('address.city'),
            ]);
        }
        return $next($request);
    }
}
