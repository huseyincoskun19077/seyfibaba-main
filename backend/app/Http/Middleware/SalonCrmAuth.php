<?php

namespace App\Http\Middleware;

use App\Models\SalonCrmCustomer;
use App\Models\SalonCrmSalon;
use App\Models\SalonCrmStaff;
use Closure;
use Illuminate\Http\Request;

class SalonCrmAuth
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $token = $request->bearerToken()
            ?: $request->header('X-Salon-Crm-Token')
            ?: $request->query('crm_token');

        if (!$token) {
            return response()->json(['message' => 'CRM girişi gerekli.'], 401);
        }

        $allowed = empty($roles)
            ? ['owner', 'staff', 'customer']
            : array_values(array_filter(array_map('trim', $roles)));

        $actor = null;

        if (in_array('owner', $allowed, true)) {
            $salon = SalonCrmSalon::query()->where('api_token', $token)->first();
            if ($salon) {
                $actor = [
                    'role' => 'owner',
                    'salon' => $salon,
                    'staff' => null,
                    'customer' => null,
                ];
            }
        }

        if (!$actor && in_array('staff', $allowed, true)) {
            $staff = SalonCrmStaff::query()
                ->with('salon')
                ->where('api_token', $token)
                ->where('is_active', true)
                ->first();
            if ($staff && $staff->salon) {
                $actor = [
                    'role' => 'staff',
                    'salon' => $staff->salon,
                    'staff' => $staff,
                    'customer' => null,
                ];
            }
        }

        if (!$actor && in_array('customer', $allowed, true)) {
            $customer = SalonCrmCustomer::query()
                ->with('salon')
                ->where('api_token', $token)
                ->first();
            if ($customer && $customer->salon) {
                $actor = [
                    'role' => 'customer',
                    'salon' => $customer->salon,
                    'staff' => null,
                    'customer' => $customer,
                ];
            }
        }

        if (!$actor) {
            return response()->json(['message' => 'CRM oturumu geçersiz.'], 401);
        }

        $request->attributes->set('salon_crm_actor', $actor);

        return $next($request);
    }
}
