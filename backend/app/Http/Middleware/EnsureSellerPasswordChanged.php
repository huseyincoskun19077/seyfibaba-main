<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureSellerPasswordChanged
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard('web')->user();

        if (! $user || ! $user->must_change_password) {
            return $next($request);
        }

        if ($request->routeIs('seller.change-password') || $request->routeIs('seller.password-update')) {
            return $next($request);
        }

        return redirect()
            ->route('seller.change-password')
            ->with([
                'messege' => 'Devam etmek için önce yeni şifrenizi oluşturun.',
                'alert-type' => 'warning',
            ]);
    }
}
