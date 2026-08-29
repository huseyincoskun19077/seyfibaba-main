<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureNotCallCenterAgent
{
    public function handle(Request $request, Closure $next)
    {
        $admin = Auth::guard('admin')->user();

        if ($admin && (int) $admin->admin_type === Admin::TYPE_CALL_CENTER) {
            return redirect()->route('call-center.dashboard');
        }

        return $next($request);
    }
}
