<?php

namespace App\Http\Middleware;

use App\Support\SellerLoginUrl;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if ($request->expectsJson()) {
            return null;
        }

        if ($request->is('seller') || $request->is('seller/*')) {
            return SellerLoginUrl::public();
        }

        if ($request->is('call-center') || $request->is('call-center/*')) {
            return route('call-center.login');
        }

        if ($request->is('admin') || $request->is('admin/*')) {
            return route('admin.login');
        }

        return route('admin.login');
    }
}
