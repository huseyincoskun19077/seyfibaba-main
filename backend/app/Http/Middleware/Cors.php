<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $origin = $request->headers->get('Origin');
        $allowedOrigins = config('cors.allowed_origins', []);

        if ($origin && in_array($origin, $allowedOrigins, true)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set(
                'Access-Control-Allow-Methods',
                implode(', ', config('cors.allowed_methods', ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']))
            );
            $response->headers->set(
                'Access-Control-Allow-Headers',
                implode(', ', Arr::wrap(config('cors.allowed_headers', ['Content-Type', 'Authorization', 'X-Requested-With'])))
            );
            $response->headers->set('Vary', 'Origin');

            if (config('cors.supports_credentials')) {
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
            }
        }

        if ($request->getMethod() === 'OPTIONS' && $response->getStatusCode() === Response::HTTP_OK) {
            $response->setStatusCode(Response::HTTP_NO_CONTENT);
        }

        return $response;
    }
}
