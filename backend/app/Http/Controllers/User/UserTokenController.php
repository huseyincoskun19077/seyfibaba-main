<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\SellerSsoTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserTokenController extends Controller
{
    public function refresh(): JsonResponse
    {
        try {
            $newToken = JWTAuth::parseToken()->refresh();
        } catch (JWTException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 401);
        }

        $ttlMinutes = (int) config('jwt.ttl', 10080);

        return response()->json([
            'access_token' => $newToken,
            'token_type' => 'bearer',
            'expires_in' => $ttlMinutes * 60,
        ]);
    }

    public function sellerSsoTicket(Request $request, SellerSsoTicketService $tickets): JsonResponse
    {
        try {
            $token = $request->bearerToken() ?: $request->input('token');
            $token = is_string($token) ? trim($token) : '';
            if ($token === '') {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            try {
                $user = auth('api')->setToken($token)->user();
            } catch (\Throwable $e) {
                $user = JWTAuth::setToken($token)->toUser();
            }

            if (! $user || (int) ($user->status ?? 0) !== 1) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $vendor = DB::table('vendors')->where('user_id', $user->id)->first();
            if (! $vendor) {
                return response()->json(['message' => 'Bu hesap bir satıcı hesabı değil.'], 403);
            }
            if ((int) ($vendor->status ?? 0) !== 1) {
                return response()->json([
                    'message' => 'Mağazanız henüz onaylanmadı veya pasif. Onay sonrası tekrar deneyin.',
                ], 403);
            }

            $next = $request->input('next');
            $next = is_string($next) && $next !== '' ? $next : null;

            return response()->json([
                'redirect_url' => $tickets->redirectUrl((int) $user->id, $next),
            ]);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Unauthorized'], 401);
        } catch (\Throwable $e) {
            Log::error('seller SSO ticket', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Satıcı paneline yönlendirilemedi. Lütfen tekrar deneyin.',
            ], 500);
        }
    }
}
