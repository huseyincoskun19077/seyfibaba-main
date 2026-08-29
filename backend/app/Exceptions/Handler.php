<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Illuminate\Support\Arr;
use Tymon\JWTAuth\Exceptions\JWTException;
class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var string[]
     */
    protected $dontReport = [
        AuthenticationException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var string[]
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Return clean JSON for all unexpected API exceptions (hides internal details in production)
        $this->renderable(function (Throwable $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                if ($e instanceof ValidationException) {
                    return null;
                }

                if ($e instanceof AuthenticationException) {
                    return response()->json(['message' => 'UnAuthenticated'], 401);
                }

                if ($e instanceof JWTException) {
                    return response()->json(['message' => 'Unauthorized'], 401);
                }

                if ($e instanceof HttpExceptionInterface) {
                    $statusCode = $e->getStatusCode();
                    if ($statusCode < 500) {
                        return response()->json([
                            'message' => $e->getMessage() ?: 'Error',
                        ], $statusCode);
                    }
                }

                $statusCode = $e instanceof HttpExceptionInterface
                    ? $e->getStatusCode()
                    : 500;

                if ($statusCode >= 500) {
                    \Illuminate\Support\Facades\Log::error('API hatası', [
                        'exception' => get_class($e),
                        'message' => $e->getMessage(),
                        'file'    => $e->getFile(),
                        'line'    => $e->getLine(),
                        'url'     => $request->fullUrl(),
                    ]);
                    return response()->json([
                        'message' => 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.',
                    ], 500);
                }
            }
        });
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if($request->expectsJson()){
            return response()->json(['message' => 'UnAuthenticated'], 401);
        }
        $guard=Arr::get($exception->guards(),'0');
        switch($guard){
            case 'admin':
                $login="/admin/login";
            break;

            default:
                $login="/seller/login";
        }

        return Redirect()->guest($login);
    }

}
