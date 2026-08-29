<?php

namespace App\Http\Middleware;

use App\Models\Vendor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureSellerTermsAccepted
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard('web')->user();

        if (! $user) {
            return $next($request);
        }

        $vendor = Vendor::query()->where('user_id', $user->id)->first();

        if (! $vendor || $vendor->seller_terms_accepted_at) {
            return $next($request);
        }

        if ($request->routeIs(
            'seller.accept-terms',
            'seller.accept-terms.store',
            'seller.change-password',
            'seller.password-update',
            'seller.logout'
        )) {
            return $next($request);
        }

        return redirect()
            ->route('seller.accept-terms')
            ->with([
                'messege' => 'Devam etmek için satıcı sözleşmesini okuyup onaylamanız gerekir.',
                'alert-type' => 'warning',
            ]);
    }
}
