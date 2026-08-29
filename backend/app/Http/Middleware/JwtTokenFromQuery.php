<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class JwtTokenFromQuery
{
    /**
     * Mobile app sends JWT as ?token= — promote it to Authorization header.
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->query('token');

        if (is_string($token) && $token !== '') {
            $request->headers->set('Authorization', 'Bearer '.$token);
        }

        return $next($request);
    }
}
