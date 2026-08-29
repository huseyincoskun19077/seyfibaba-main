<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureCallCenterAgent
{
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::guard('admin')->check()) {
            return redirect()->route('call-center.login');
        }

        $admin = Auth::guard('admin')->user();

        if ((int) $admin->admin_type !== Admin::TYPE_CALL_CENTER) {
            abort(403, 'Bu alana yalnızca çağrı merkezi kullanıcıları erişebilir.');
        }

        if ((int) $admin->status !== 1) {
            Auth::guard('admin')->logout();

            return redirect()
                ->route('call-center.login')
                ->with(['messege' => 'Hesabınız pasif durumda.', 'alert-type' => 'error']);
        }

        return $next($request);
    }
}
