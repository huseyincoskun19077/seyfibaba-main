<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SalonCrmSalon;
use App\Services\SalonCrmCalendarShareService;
use Illuminate\Http\Request;

class SalonCrmCalendarController extends Controller
{
    public function __construct(private SalonCrmCalendarShareService $shares)
    {
    }

    public function show(Request $request)
    {
        [$salon, $error, $staffId] = $this->actorSalon($request);
        if ($error) {
            return $error;
        }

        $share = $this->shares->ensureShare($salon, $staffId);
        $staff = $staffId ? ($request->attributes->get('salon_crm_actor')['staff'] ?? null) : null;

        return response()->json($this->shares->ownerPayload($share, $salon, $staff));
    }

    public function update(Request $request)
    {
        [$salon, $error, $staffId] = $this->actorSalon($request);
        if ($error) {
            return $error;
        }

        $data = $request->validate([
            'horizon' => ['required', 'in:today_tomorrow,week,month'],
        ]);

        $share = $this->shares->ensureShare($salon, $staffId);
        $share = $this->shares->updateHorizon($share, $data['horizon']);
        $staff = $staffId ? ($request->attributes->get('salon_crm_actor')['staff'] ?? null) : null;

        return response()->json([
            'message' => 'Paylaşım aralığı güncellendi.',
            ...$this->shares->ownerPayload($share, $salon, $staff),
        ]);
    }

    public function publicShow(string $token)
    {
        $payload = $this->shares->publicPayload($token);
        if (!$payload) {
            return response()->json(['message' => 'Takvim linki bulunamadı.'], 404);
        }

        return response()->json($payload);
    }

    /**
     * @return array{0:?SalonCrmSalon,1:?\Illuminate\Http\JsonResponse,2:?int}
     */
    private function actorSalon(Request $request): array
    {
        $actor = $request->attributes->get('salon_crm_actor');
        if (!$actor || empty($actor['salon'])) {
            return [null, response()->json(['message' => 'CRM girişi gerekli.'], 401), null];
        }

        /** @var SalonCrmSalon $salon */
        $salon = $actor['salon'];
        $role = $actor['role'] ?? 'owner';
        if (!in_array($role, ['owner', 'staff'], true)) {
            return [null, response()->json(['message' => 'Bu işlem için yetkiniz yok.'], 403), null];
        }

        $staffId = $role === 'staff' ? (int) ($actor['staff']?->id ?? 0) : null;
        if ($role === 'staff' && !$staffId) {
            return [null, response()->json(['message' => 'Personel oturumu geçersiz.'], 403), null];
        }

        return [$salon, null, $staffId ?: null];
    }
}
