<?php

namespace App\Http\Controllers\WEB\Seller\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vendor;
use App\Services\SellerSsoTicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Support\SellerLoginUrl;

class SellerSsoController extends Controller
{
    /**
     * Next.js (JWT) oturumunu seller web (session) oturumuna çevirir.
     * URL: /seller/sso?code=... (tek kullanımlık, 90 sn geçerli)
     */
    public function __invoke(Request $request, SellerSsoTicketService $tickets)
    {
        $code = (string) $request->query('code', '');
        if ($code === '') {
            return redirect()->away(SellerLoginUrl::public());
        }

        $payload = $tickets->consume($code);
        if (! $payload) {
            return redirect()->away(SellerLoginUrl::public());
        }

        $user = User::query()->find($payload['user_id']);
        if (! $user || (int) ($user->status ?? 0) !== 1) {
            return redirect()->away(SellerLoginUrl::public());
        }

        $vendor = Vendor::query()->where('user_id', $user->id)->first();
        if (! $vendor || (int) ($vendor->status ?? 0) !== 1) {
            return redirect()->away(SellerLoginUrl::public());
        }

        Auth::guard('web')->login($user, true);
        $request->session()->regenerate();

        $next = (string) ($payload['next'] ?? '');
        if ($next === 'change-password' && (bool) ($user->must_change_password ?? false)) {
            $request->session()->put('seller_first_login_verified', true);

            return redirect()->route('seller.change-password');
        }

        return redirect()->route('seller.dashboard');
    }
}
