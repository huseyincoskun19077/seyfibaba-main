<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth;

class CheckSeller
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
        $user = Auth::guard('api')->user() ?: Auth::guard('web')->user();

        if (! $user || ! $user->seller) {
            return response()->json([
                'notification' => trans('Seller account is required'),
            ], 403);
        }

        if ((int) $user->seller->status !== 1) {
            return response()->json([
                'notification' => trans('Seller account is inactive'),
            ], 403);
        }

        return $next($request);
    }
}
