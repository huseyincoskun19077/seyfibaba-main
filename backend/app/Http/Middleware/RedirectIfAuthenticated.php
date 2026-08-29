<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  ...$guards
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            return $next($request);
        }

        $guards = empty($guards) ? [null] : $guards;
        foreach ($guards as $guard) {
            try {
                $authenticated = Auth::guard($guard)->check();
            } catch (\Throwable $e) {
                $authenticated = false;
            }
            if ($authenticated) {
                if($guard=='admin'){
                    return redirect()->route('admin.dashboard');
                }else{
                    return redirect()->route('seller.dashboard');
                }

            }
        }

        return $next($request);
    }
}
