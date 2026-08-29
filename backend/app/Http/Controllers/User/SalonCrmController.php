<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SalonCrmAppointment;
use App\Models\SalonCrmCustomer;
use App\Models\SalonCrmLedgerEntry;
use App\Models\SalonCrmSalaryPayment;
use App\Models\SalonCrmSalon;
use App\Models\SalonCrmService;
use App\Models\SalonCrmStaff;
use App\Models\SalonCrmStaffHour;
use App\Models\SalonCrmStaffService;
use App\Services\ProductImageStorage;
use App\Services\SalonCrmAccessService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SalonCrmController extends Controller
{
    public function __construct(private SalonCrmAccessService $accessService)
    {
    }

    public function status(Request $request)
    {
        try {
            [$salon, $snapshot, $error, $actor] = $this->resolveSalon($request);
            if ($error) {
                return $error;
            }

            return response()->json(array_merge($snapshot, [
                'role' => $actor['role'],
                'staff' => $actor['staff']
                    ? $this->staffPayload($actor['staff'])
                    : null,
                'customer' => $actor['customer'] ? [
                    'id' => $actor['customer']->id,
                    'name' => $actor['customer']->name,
                    'phone' => $actor['customer']->phone,
                ] : null,
            ]));
        } catch (\Throwable $e) {
            Log::error('Salon CRM status', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Salon paneli yüklenemedi. Biraz sonra tekrar deneyin.',
            ], 400);
        }
    }

    public function updateDeviceToken(Request $request)
    {
        [$salon, $snapshot, $error, $actor] = $this->resolveSalon($request);
        if ($error) {
            return $error;
        }

        $data = $request->validate([
            'device_token' => ['nullable', 'string', 'max:512'],
        ]);

        $token = trim((string) ($data['device_token'] ?? ''));
        $token = $token === '' ? null : $token;
        $role = $actor['role'] ?? '';

        if ($role === 'owner') {
            $salon->forceFill(['fcm_token' => $token])->save();
        } elseif ($role === 'staff' && !empty($actor['staff'])) {
            $actor['staff']->forceFill(['fcm_token' => $token])->save();
        } elseif ($role === 'customer' && !empty($actor['customer'])) {
            $actor['customer']->forceFill(['fcm_token' => $token])->save();
        } else {
            return response()->json(['message' => 'CRM girişi gerekli.'], 401);
        }

        return response()->json([
            'message' => 'Cihaz bildirimi güncellendi.',
            'has_token' => $token !== null,
        ]);
    }

    public function register(Request $request)
    {
        return response()->json([
            'message' => 'CRM kaydı artık ayrıdır. Patron kaydı için /api/user/salon-crm/auth/patron/register kullanın.',
        ], 410);
    }

    public function staffIndex(Request $request)
    {
        [$salon, $snapshot, $error] = $this->resolveSalon($request, ownerOnly: true);
        if ($error) {
            return $error;
        }

        $staff = SalonCrmStaff::query()
            ->where('salon_id', $salon->id)
            ->where('is_active', true)
            ->with(['staffServices.service'])
            ->orderBy('name')
            ->get();

        return response()->json([
            ...$snapshot,
            'staff' => $staff->map(fn (SalonCrmStaff $s) => $this->staffPayload($s, true)),
        ]);
    }

    public function staffStore(Request $request)
    {
        [$salon, $snapshot, $error] = $this->resolveSalon($request, requireWrite: true, ownerOnly: true);
        if ($error) {
            return $error;
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:80'],
            'password' => ['required', 'string', 'min:4', 'max:80'],
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'pay_type' => ['nullable', 'in:percent,net'],
            'pay_period' => ['nullable', 'in:daily,monthly'],
            'salary_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $username = trim($data['username']);
        if ($username === '') {
            return response()->json(['message' => 'Kullanıcı adı gerekli.', ...$snapshot], 422);
        }

        $payType = $data['pay_type'] ?? 'percent';
        $existing = SalonCrmStaff::query()
            ->where('salon_id', $salon->id)
            ->where('username', $username)
            ->first();

        // Aktif personelde aynı kullanıcı adı → net hata
        if ($existing && $existing->is_active) {
            return response()->json([
                'message' => '“'.$username.'” kullanıcı adı zaten kullanılıyor. Farklı bir kullanıcı adı yazın.',
                ...$snapshot,
            ], 422);
        }

        // Silinmiş (pasif) personel aynı kullanıcı adıyla tekrar eklenirse → yeniden aç
        if ($existing && !$existing->is_active) {
            $existing->name = trim($data['name']);
            $existing->password = Hash::make($data['password']);
            $existing->is_active = true;
            $existing->api_token = null;
            $existing->fcm_token = null;
            if (Schema::hasColumn('salon_crm_staff', 'commission_percent')) {
                $existing->commission_percent = $payType === 'percent'
                    ? (float) ($data['commission_percent'] ?? 0)
                    : 0;
            }
            if (Schema::hasColumn('salon_crm_staff', 'pay_type')) {
                $existing->pay_type = $payType;
            }
            if (Schema::hasColumn('salon_crm_staff', 'pay_period')) {
                $existing->pay_period = $data['pay_period'] ?? 'monthly';
            }
            if (Schema::hasColumn('salon_crm_staff', 'salary_amount')) {
                $existing->salary_amount = $payType === 'net'
                    ? (float) ($data['salary_amount'] ?? 0)
                    : 0;
            }
            $existing->save();

            return response()->json([
                'message' => 'Personel yeniden eklendi (önceki kayıt güncellendi).',
                ...$this->accessService->snapshot($salon, $salon->user),
                'staff' => $this->staffPayload($existing),
            ], 201);
        }

        $attrs = [
            'salon_id' => $salon->id,
            'name' => trim($data['name']),
            'username' => $username,
            'password' => Hash::make($data['password']),
            'is_active' => true,
        ];

        // Eksik migration olsa bile temel personel kaydı oluşabilsin
        if (Schema::hasColumn('salon_crm_staff', 'commission_percent')) {
            $attrs['commission_percent'] = $payType === 'percent'
                ? (float) ($data['commission_percent'] ?? 0)
                : 0;
        }
        if (Schema::hasColumn('salon_crm_staff', 'pay_type')) {
            $attrs['pay_type'] = $payType;
        }
        if (Schema::hasColumn('salon_crm_staff', 'pay_period')) {
            $attrs['pay_period'] = $data['pay_period'] ?? 'monthly';
        }
        if (Schema::hasColumn('salon_crm_staff', 'salary_amount')) {
            $attrs['salary_amount'] = $payType === 'net'
                ? (float) ($data['salary_amount'] ?? 0)
                : 0;
        }
        if (Schema::hasColumn('salon_crm_staff', 'show_photo_to_customers')) {
            $attrs['show_photo_to_customers'] = true;
        }

        try {
            $staff = SalonCrmStaff::query()->create($attrs);
        } catch (QueryException $e) {
            Log::error('salon-crm staffStore', [
                'message' => $e->getMessage(),
                'salon_id' => $salon->id,
            ]);

            $sql = $e->getMessage();
            if (str_contains($sql, '1062') || str_contains(strtolower($sql), 'duplicate')) {
                return response()->json([
                    'message' => '“'.$username.'” kullanıcı adı zaten kullanılıyor. Farklı bir kullanıcı adı yazın.',
                    ...$snapshot,
                ], 422);
            }
            if (str_contains($sql, 'Unknown column') || str_contains($sql, '42S22')) {
                return response()->json([
                    'message' => 'Veritabanı güncel değil. Sunucuda: php artisan migrate',
                    ...$snapshot,
                ], 500);
            }

            return response()->json([
                'message' => 'Personel eklenemedi. Lütfen tekrar deneyin.',
                ...$snapshot,
            ], 500);
        }

        return response()->json([
            'message' => 'Personel eklendi. Kullanıcı adı ve şifre ile giriş yapabilir.',
            ...$this->accessService->snapshot($salon, $salon->user),
            'staff' => $this->staffPayload($staff),
        ], 201);
    }

    public function staffUpdate(Request $request, int $id)
    {
        [$salon, $snapshot, $error] = $this->resolveSalon($request, requireWrite: true, ownerOnly: true);
        if ($error) {
            return $error;
        }

        $staff = SalonCrmStaff::query()
            ->where('salon_id', $salon->id)
            ->where('id', $id)
            ->firstOrFail();

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'pay_type' => ['nullable', 'in:percent,net'],
            'pay_period' => ['nullable', 'in:daily,monthly'],
            'salary_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (array_key_exists('name', $data) && trim((string) $data['name']) !== '') {
            $staff->name = trim($data['name']);
        }
        if (array_key_exists('is_active', $data)) {
            $staff->is_active = (bool) $data['is_active'];
        }
        if (array_key_exists('pay_type', $data)) {
            $staff->pay_type = $data['pay_type'];
        }
        if (array_key_exists('pay_period', $data)) {
            $staff->pay_period = $data['pay_period'];
        }
        if (array_key_exists('salary_amount', $data)) {
            $staff->salary_amount = (float) $data['salary_amount'];
        }
        if (array_key_exists('commission_percent', $data)) {
            $staff->commission_percent = (float) $data['commission_percent'];
        }
        if (($staff->pay_type ?? 'percent') === 'net') {
            $staff->commission_percent = 0;
        } else {
            $staff->salary_amount = 0;
        }
        $staff->save();

        return response()->json([
            'message' => 'Personel bilgileri güncellendi.',
            ...$this->accessService->snapshot($salon, $salon->user),
            'staff' => $this->staffPayload($staff->fresh(['staffServices.service']), true),
        ]);
    }

    public function staffDestroy(Request $request, int $id)
    {
        [$salon, $snapshot, $error] = $this->resolveSalon($request, requireWrite: true, ownerOnly: true);
        if ($error) {
            return $error;
        }

        $staff = SalonCrmStaff::query()
            ->where('salon_id', $salon->id)
            ->where('id', $id)
            ->firstOrFail();

        $staff->is_active = false;
        $staff->api_token = null;
        $staff->fcm_token = null;
        $staff->save();

        return response()->json([
            'message' => 'Personel kaldırıldı. Geçmiş randevular duruyor.',
            ...$snapshot,
        ]);
    }

    public function staffShow(Request $request, int $id)
    {
        [$salon, $snapshot, $error, $actor] = $this->resolveSalon($request);
        if ($error) {
            return $error;
        }

        $role = $actor['role'] ?? '';
        if ($role === 'staff') {
            $selfId = (int) ($actor['staff']?->id ?? 0);
            if ($selfId !== $id) {
                return response()->json(['message' => 'Yalnızca kendi kaydınızı görebilirsiniz.'], 403);
            }
        } elseif ($role !== 'owner') {
            return response()->json(['message' => 'Bu işlem için yetkiniz yok.'], 403);
        }

        $staff = SalonCrmStaff::query()
            ->where('salon_id', $salon->id)
            ->with(['staffServices.service'])
            ->where('id', $id)
            ->firstOrFail();

        $period = $this->staffPayPeriodInfo($salon, $staff);

        $payments = SalonCrmSalaryPayment::query()
            ->where('salon_id', $salon->id)
            ->where('staff_id', $staff->id)
            ->orderByDesc('id')
            ->limit(24)
            ->get()
            ->map(fn (SalonCrmSalaryPayment $p) => $this->salaryPaymentPayload($p));

        return response()->json([
            ...$snapshot,
            'staff' => $this->staffPayload($staff, true),
            'hours' => $this->staffHoursPayload($staff),
            'current_period' => $period,
            'payments' => $payments,
        ]);
    }

    public function staffHoursSync(Request $request, int $id)
    {
        [$salon, $snapshot, $error] = $this->resolveSalon($request, requireWrite: true, ownerOnly: true);
        if ($error) {
            return $error;
        }

        $staff = SalonCrmStaff::query()
            ->where('salon_id', $salon->id)
            ->where('id', $id)
            ->firstOrFail();

        if (!Schema::hasTable('salon_crm_staff_hours')) {
            return response()->json([
                'message' => 'Çalışma saatleri henüz kurulmadı.',
                ...$snapshot,
            ], 422);
        }

        $data = $request->validate([
            'hours' => ['required', 'array', 'min:1', 'max:7'],
            'hours.*.weekday' => ['required', 'integer', 'min:1', 'max:7'],
            'hours.*.start_time' => ['nullable', 'date_format:H:i'],
            'hours.*.end_time' => ['nullable', 'date_format:H:i'],
            'hours.*.is_off' => ['nullable', 'boolean'],
        ]);

        $seen = [];
        foreach ($data['hours'] as $row) {
            $weekday = (int) $row['weekday'];
            if (isset($seen[$weekday])) {
                continue;
            }
            $seen[$weekday] = true;
            $isOff = (bool) ($row['is_off'] ?? false);
            $start = $row['start_time'] ?? '09:00';
            $end = $row['end_time'] ?? '21:00';
            if (!$isOff && strcmp($end, $start) <= 0) {
                return response()->json([
                    'message' => 'Bitiş saati başlangıçtan sonra olmalı.',
                    ...$snapshot,
                ], 422);
            }

            SalonCrmStaffHour::query()->updateOrCreate(
                [
                    'staff_id' => $staff->id,
                    'weekday' => $weekday,
                ],
                [
                    'salon_id' => $salon->id,
                    'start_time' => $start,
                    'end_time' => $end,
                    'is_off' => $isOff,
                ]
            );
        }

        return response()->json([
            'message' => 'Çalışma saatleri kaydedildi.',
            ...$snapshot,
            'hours' => $this->staffHoursPayload($staff),
        ]);
    }

    public function staffServicesSync(Request $request, int $id)
    {
        [$salon, $snapshot, $error] = $this->resolveSalon($request, requireWrite: true, ownerOnly: true);
        if ($error) {
            return $error;
        }

        $staff = SalonCrmStaff::query()
            ->where('salon_id', $salon->id)
            ->where('id', $id)
            ->firstOrFail();

        $data = $request->validate([
            'services' => ['required', 'array', 'max:80'],
            'services.*.service_id' => ['nullable', 'integer'],
            'services.*.name' => ['nullable', 'string', 'max:120'],
            'services.*.price' => ['required', 'numeric', 'min:0'],
            'services.*.duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
        ]);

        $keepIds = [];
        foreach ($data['services'] as $row) {
            $service = null;
            if (!empty($row['service_id'])) {
                $service = SalonCrmService::query()
                    ->where('salon_id', $salon->id)
                    ->where('id', $row['service_id'])
                    ->first();
            }
            if (!$service) {
                $name = trim((string) ($row['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $service = SalonCrmService::query()->create([
                    'salon_id' => $salon->id,
                    'name' => $name,
                    'duration_minutes' => (int) ($row['duration_minutes'] ?? 30),
                    'price' => (float) $row['price'],
                    'is_active' => true,
                ]);
            }

            $link = SalonCrmStaffService::query()->updateOrCreate(
                [
                    'staff_id' => $staff->id,
                    'service_id' => $service->id,
                ],
                [
                    'salon_id' => $salon->id,
                    'price' => (float) $row['price'],
                    'duration_minutes' => $row['duration_minutes'] ?? $service->duration_minutes,
                ]
            );
            $keepIds[] = $link->id;
        }

        SalonCrmStaffService::query()
            ->where('staff_id', $staff->id)
            ->when($keepIds !== [], fn ($q) => $q->whereNotIn('id', $keepIds))
            ->delete();

        return response()->json([
            'message' => 'Personel hizmetleri kaydedildi.',
            ...$snapshot,
            'staff' => $this->staffPayload($staff->fresh(['staffServices.service']), true),
        ]);
    }

    public function salaryPaymentsIndex(Request $request)
    {
        [$salon, $snapshot, $error, $actor] = $this->resolveSalon($request);
        if ($error) {
            return $error;
        }

        $role = $actor['role'] ?? '';
        $query = SalonCrmSalaryPayment::query()
            ->with(['staff:id,name'])
            ->where('salon_id', $salon->id)
            ->orderByDesc('id');

        if ($role === 'staff') {
            $query->where('staff_id', (int) ($actor['staff']?->id ?? 0));
        } elseif ($role !== 'owner') {
            return response()->json(['message' => 'Bu işlem için yetkiniz yok.'], 403);
        }

        if ($request->filled('staff_id') && $role === 'owner') {
            $query->where('staff_id', (int) $request->query('staff_id'));
        }

        $payments = $query->limit(80)->get()->map(fn (SalonCrmSalaryPayment $p) => $this->salaryPaymentPayload($p));

        return response()->json([
            ...$snapshot,
            'payments' => $payments,
        ]);
    }

    public function salaryPaymentsStore(Request $request)
    {
        [$salon, $snapshot, $error] = $this->resolveSalon($request, requireWrite: true, ownerOnly: true);
        if ($error) {
            return $error;
        }

        $data = $request->validate([
            'staff_id' => ['required', 'integer'],
            'period_key' => ['nullable', 'string', 'max:16'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $staff = SalonCrmStaff::query()
            ->where('salon_id', $salon->id)
            ->where('id', $data['staff_id'])
            ->firstOrFail();

        $period = $this->staffPayPeriodInfo($salon, $staff, $data['period_key'] ?? null);
        $existing = SalonCrmSalaryPayment::query()
            ->where('salon_id', $salon->id)
            ->where('staff_id', $staff->id)
            ->where('period_key', $period['key'])
            ->whereIn('status', ['pending', 'paid'])
            ->first();
        if ($existing) {
            return response()->json([
                'message' => $existing->status === 'paid'
                    ? 'Bu dönem için maaş zaten ödendi.'
                    : 'Bu dönem için bekleyen bir maaş ödemesi var.',
                'payment' => $this->salaryPaymentPayload($existing),
            ], 422);
        }

        $amount = array_key_exists('amount', $data)
            ? (float) $data['amount']
            : (float) $period['suggested_amount'];
        if ($amount <= 0) {
            return response()->json(['message' => 'Ödenecek tutar 0 olamaz.'], 422);
        }

        $payment = SalonCrmSalaryPayment::query()->create([
            'salon_id' => $salon->id,
            'staff_id' => $staff->id,
            'pay_type' => $staff->pay_type ?? 'percent',
            'pay_period' => $staff->pay_period ?? 'monthly',
            'period_key' => $period['key'],
            'suggested_amount' => (float) $period['suggested_amount'],
            'amount' => $amount,
            'status' => 'pending',
            'owner_confirmed_at' => now(),
            'notes' => $data['notes'] ?? null,
        ]);
        $payment->setRelation('staff', $staff);

        return response()->json([
            'message' => 'Maaş ödemesi oluşturuldu. Personel onaylayınca “Maaş ödendi” yazılır.',
            ...$snapshot,
            'payment' => $this->salaryPaymentPayload($payment),
        ], 201);
    }

    public function salaryPaymentsConfirm(Request $request, int $id)
    {
        [$salon, $snapshot, $error, $actor] = $this->resolveSalon($request, requireWrite: true);
        if ($error) {
            return $error;
        }

        $payment = SalonCrmSalaryPayment::query()
            ->where('salon_id', $salon->id)
            ->where('id', $id)
            ->firstOrFail();

        $role = $actor['role'] ?? '';
        if ($role === 'staff') {
            $selfId = (int) ($actor['staff']?->id ?? 0);
            if ($selfId !== (int) $payment->staff_id) {
                return response()->json(['message' => 'Yalnızca kendi maaşınızı onaylayabilirsiniz.'], 403);
            }
            $payment->staff_confirmed_at = now();
        } elseif ($role === 'owner') {
            $payment->owner_confirmed_at = now();
        } else {
            return response()->json(['message' => 'Bu işlem için yetkiniz yok.'], 403);
        }

        if ($payment->owner_confirmed_at && $payment->staff_confirmed_at && $payment->status !== 'paid') {
            $this->markSalaryPaid($salon, $payment);
        } else {
            $payment->save();
        }

        $label = $payment->status === 'paid'
            ? 'Maaş ödendi.'
            : ($role === 'staff'
                ? 'Onayınız alındı. Patron onayı da gelince maaş ödendi yazılır.'
                : 'Onayınız alındı. Personel de onaylayınca maaş ödendi yazılır.');

        return response()->json([
            'message' => $label,
            ...$snapshot,
            'payment' => $this->salaryPaymentPayload($payment->fresh()),
        ]);
    }

    public function profileShow(Request $request)
    {
        [$salon, $snapshot, $error, $actor] = $this->resolveSalon($request);
        if ($error) {
            return $error;
        }

        $this->ensureSalonJoinCode($salon);

        return response()->json([
            ...$snapshot,
            'profile' => $this->salonProfilePayload($salon->fresh()),
        ]);
    }

    public function profileUpdate(Request $request, ProductImageStorage $imageStorage)
    {
        [$salon, $snapshot, $error] = $this->resolveSalon($request, requireWrite: true, ownerOnly: true);
        if ($error) {
            return $error;
        }

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:32'],
            'profile_text' => ['nullable', 'string', 'max:2000'],
            'show_profile_to_customers' => ['nullable', 'boolean'],
            'open_hour' => ['nullable', 'integer', 'min:0', 'max:23'],
            'close_hour' => ['nullable', 'integer', 'min:0', 'max:23'],
            'logo_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_cover' => ['nullable', 'boolean'],
        ]);

        if (array_key_exists('name', $data) && trim((string) $data['name']) !== '') {
            $salon->name = trim($data['name']);
        }
        if (array_key_exists('phone', $data)) {
            $salon->phone = trim((string) ($data['phone'] ?? '')) ?: null;
        }
        if (array_key_exists('profile_text', $data)) {
            $salon->profile_text = trim((string) ($data['profile_text'] ?? '')) ?: null;
        }
        if (array_key_exists('show_profile_to_customers', $data)) {
            $salon->show_profile_to_customers = (bool) $data['show_profile_to_customers'];
        }
        if (array_key_exists('open_hour', $data) && Schema::hasColumn('salon_crm_salons', 'open_hour')) {
            $salon->open_hour = (int) $data['open_hour'];
        }
        if (array_key_exists('close_hour', $data) && Schema::hasColumn('salon_crm_salons', 'close_hour')) {
            $salon->close_hour = (int) $data['close_hour'];
        }
        if (isset($salon->open_hour, $salon->close_hour) && (int) $salon->close_hour <= (int) $salon->open_hour) {
            return response()->json([
                'message' => 'Kapanış saati açılıştan sonra olmalı.',
                ...$snapshot,
            ], 422);
        }

        if ($request->boolean('remove_logo')) {
            $salon->logo_image = null;
        } elseif ($request->hasFile('logo_image')) {
            $salon->logo_image = $imageStorage->store($request->file('logo_image'), 'salon-crm-logo');
        }

        if ($request->boolean('remove_cover')) {
            $salon->cover_image = null;
        } elseif ($request->hasFile('cover_image')) {
            $salon->cover_image = $imageStorage->store($request->file('cover_image'), 'salon-crm-cover');
        }

        $salon->save();

        return response()->json([
            'message' => 'Salon profili güncellendi.',
            ...$this->accessService->snapshot($salon, $salon->user),
            'profile' => $this->salonProfilePayload($salon),
        ]);
    }

    public function staffPhotoUpdate(Request $request, int $id, ProductImageStorage $imageStorage)
    {
        [$salon, $snapshot, $error, $actor] = $this->resolveSalon($request, requireWrite: true);
        if ($error) {
            return $error;
        }

        $role = $actor['role'] ?? '';
        if ($role === 'staff') {
            $selfId = (int) ($actor['staff']?->id ?? 0);
            if ($selfId !== $id) {
                return response()->json([
                    'message' => 'Yalnızca kendi fotoğrafınızı güncelleyebilirsiniz.',
                ], 403);
            }
        } elseif ($role !== 'owner') {
            return response()->json(['message' => 'Bu işlem için yetkiniz yok.'], 403);
        }

        $staff = SalonCrmStaff::query()
            ->where('salon_id', $salon->id)
            ->where('id', $id)
            ->firstOrFail();

        $data = $request->validate([
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'show_photo_to_customers' => ['nullable', 'boolean'],
            'remove_photo' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('remove_photo')) {
            $staff->photo = null;
        } elseif ($request->hasFile('photo')) {
            $staff->photo = $imageStorage->store($request->file('photo'), 'salon-crm-staff');
        }

        if (array_key_exists('show_photo_to_customers', $data)) {
            $staff->show_photo_to_customers = (bool) $data['show_photo_to_customers'];
        }

        $staff->save();

        return response()->json([
            'message' => 'Personel fotoğrafı güncellendi.',
            ...$this->accessService->snapshot($salon, $salon->user),
            'staff' => $this->staffPayload($staff),
        ]);
    }

    public function servicesIndex(Request $request)
    {
        [$salon, $snapshot, $error] = $this->resolveSalon($request);
        if ($error) {
            return $error;
        }

        $services = SalonCrmService::query()
            ->where('salon_id', $salon->id)
            ->orderBy('name')
            ->get();

        return response()->json([
            ...$snapshot,
            'services' => $services,
        ]);
    }

    public function servicesStore(Request $request)
    {
        [$salon, $snapshot, $error] = $this->resolveSalon($request, requireWrite: true, ownerOnly: true);
        if ($error) {
            return $error;
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $service = SalonCrmService::query()->create([
            'salon_id' => $salon->id,
            'name' => trim($data['name']),
            'duration_minutes' => (int) ($data['duration_minutes'] ?? 30),
            'price' => (float) ($data['price'] ?? 0),
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Hizmet eklendi.',
            ...$snapshot,
            'service' => $service,
        ], 201);
    }

    public function servicesUpdate(Request $request, int $id)
    {
        [$salon, $snapshot, $error] = $this->resolveSalon($request, requireWrite: true, ownerOnly: true);
        if ($error) {
            return $error;
        }

        $service = SalonCrmService::query()
            ->where('salon_id', $salon->id)
            ->where('id', $id)
            ->first();

        if (!$service) {
            return response()->json(['message' => 'Hizmet bulunamadı.', ...$snapshot], 404);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (array_key_exists('name', $data)) {
            $service->name = trim($data['name']);
        }
        if (array_key_exists('duration_minutes', $data) && $data['duration_minutes'] !== null) {
            $service->duration_minutes = (int) $data['duration_minutes'];
        }
        if (array_key_exists('price', $data) && $data['price'] !== null) {
            $service->price = (float) $data['price'];
        }
        if (array_key_exists('is_active', $data)) {
            $service->is_active = (bool) $data['is_active'];
        }
        $service->save();

        return response()->json([
            'message' => 'Hizmet güncellendi.',
            ...$snapshot,
            'service' => $service,
        ]);
    }

    public function appointmentsIndex(Request $request)
    {
        [$salon, $snapshot, $error, $actor] = $this->resolveSalon($request);
        if ($error) {
            return $error;
        }

        $date = $request->query('date');
        $day = $date
            ? Carbon::parse($date, 'Europe/Istanbul')->startOfDay()
            : Carbon::now('Europe/Istanbul')->startOfDay();

        $role = $actor['role'] ?? 'owner';
        $staffSelfId = ($role === 'staff') ? (int) ($actor['staff']?->id ?? 0) : null;

        $itemsQuery = SalonCrmAppointment::query()
            ->with(['staff:id,name,commission_percent', 'service:id,name', 'customer:id,name,phone,notes'])
            ->where('salon_id', $salon->id)
            ->whereBetween('starts_at', [$day, $day->copy()->endOfDay()]);
        if ($staffSelfId) {
            // Personel yalnızca kendine atanmış randevuları görür (patronun / diğerlerinin değil)
            $itemsQuery->where('staff_id', $staffSelfId);
        }
        $items = $itemsQuery
            ->orderBy('starts_at')
            ->get()
            ->map(fn (SalonCrmAppointment $a) => $this->appointmentPayload($a));

        $occFrom = Carbon::now('Europe/Istanbul')->startOfDay();
        $occTo = Carbon::now('Europe/Istanbul')->addDays(13)->endOfDay();
        $occRows = SalonCrmAppointment::query()
            ->where('salon_id', $salon->id)
            ->when($staffSelfId, fn ($q) => $q->where('staff_id', $staffSelfId))
            ->whereBetween('starts_at', [$occFrom, $occTo])
            ->selectRaw('DATE(starts_at) as d')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_block = 1 THEN 1 ELSE 0 END) as blocked')
            ->selectRaw("SUM(CASE WHEN is_block = 0 AND status = 'scheduled' THEN 1 ELSE 0 END) as scheduled")
            ->groupBy('d')
            ->get()
            ->keyBy('d');

        $occupancy = [];
        for ($i = 0; $i < 14; $i++) {
            $d = $occFrom->copy()->addDays($i);
            $key = $d->toDateString();
            $row = $occRows->get($key);
            $total = (int) ($row->total ?? 0);
            $blocked = (int) ($row->blocked ?? 0);
            $scheduled = (int) ($row->scheduled ?? 0);
            $occupancy[] = [
                'date' => $key,
                'total' => $total,
                'blocked' => $blocked,
                'scheduled' => $scheduled,
                'label' => $total === 0
                    ? 'Boş'
                    : ($blocked > 0 && $scheduled === 0 ? 'Kapalı' : $total.' randevu'),
            ];
        }

        return response()->json([
            ...$snapshot,
            'date' => $day->toDateString(),
            'appointments' => $items,
            'occupancy' => $occupancy,
            'day_summary' => $this->appointmentDaySummary($salon->id, $day, $staffSelfId),
        ]);
    }

    public function appointmentsStore(Request $request)
    {
        [$salon, $snapshot, $error, $actor] = $this->resolveSalon($request, requireWrite: true);
        if ($error) {
            return $error;
        }

        $data = $request->validate([
            'customer_id' => ['nullable', 'integer'],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:32'],
            'starts_at' => ['required', 'date'],
            'staff_id' => ['nullable', 'integer'],
            'service_id' => ['nullable', 'integer'],
            'service_name' => ['nullable', 'string', 'max:120'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_block' => ['nullable', 'boolean'],
            'block_type' => ['nullable', 'in:lunch,wedding,leave,closed,other'],
        ]);

        $isBlock = (bool) ($data['is_block'] ?? false);
        $blockType = $isBlock ? ($data['block_type'] ?? 'other') : null;
        $blockLabels = [
            'lunch' => 'Öğle arası',
            'wedding' => 'Düğün / özel gün',
            'leave' => 'İzin',
            'closed' => 'Kapalı',
            'other' => 'Mola / dolu',
        ];

        $role = $actor['role'] ?? 'owner';
        $staffId = null;
        $staffModel = null;
        if ($role === 'staff') {
            $staffModel = $actor['staff'] ?? null;
            if (!$staffModel) {
                return response()->json(['message' => 'Personel oturumu geçersiz.', ...$snapshot], 403);
            }
            $staffId = (int) $staffModel->id;
        } elseif (!empty($data['staff_id'])) {
            $staffModel = SalonCrmStaff::query()
                ->where('salon_id', $salon->id)
                ->where('id', $data['staff_id'])
                ->where('is_active', true)
                ->first();
            if (!$staffModel) {
                return response()->json(['message' => 'Personel bulunamadı.', ...$snapshot], 422);
            }
            $staffId = $staffModel->id;
        }
        $customerStaffKey = $role === 'staff' ? (int) $staffId : 0;

        $customer = null;
        if (!empty($data['customer_id'])) {
            $customerQuery = SalonCrmCustomer::query()
                ->where('salon_id', $salon->id)
                ->where('id', $data['customer_id']);
            // Personel yalnızca kendi müşteri defterinden seçebilir
            if ($role === 'staff') {
                $customerQuery->where('staff_id', $customerStaffKey);
            }
            $customer = $customerQuery->first();
            if (!$customer) {
                return response()->json(['message' => 'Müşteri bulunamadı.', ...$snapshot], 422);
            }
        }

        $customerName = $customer?->name
            ?? trim((string) ($data['customer_name'] ?? ''));
        $customerPhone = $customer?->phone
            ?? trim((string) ($data['customer_phone'] ?? ''));

        if ($isBlock) {
            $label = $blockLabels[$blockType] ?? 'Dolu';
            if ($customerName === '' || strcasecmp($customerName, 'Dolu') === 0) {
                $customerName = $label;
            }
            if ($customerPhone === '') {
                $customerPhone = '-';
            }
        } elseif ($customerName === '') {
            return response()->json([
                'message' => 'Müşteri adı gerekli veya Dolu olarak işaretleyin.',
                ...$snapshot,
            ], 422);
        }

        if ($customerPhone === '') {
            $customerPhone = '-';
        }

        if (!$isBlock && !$customer && $customerPhone !== '-' && $customerPhone !== '') {
            $createKeys = [
                'salon_id' => $salon->id,
                'phone' => $customerPhone,
            ];
            if (Schema::hasColumn('salon_crm_customers', 'staff_id')) {
                $createKeys['staff_id'] = $customerStaffKey;
            }
            $customer = SalonCrmCustomer::query()->firstOrCreate(
                $createKeys,
                [
                    'name' => $customerName,
                ]
            );
        }

        $service = null;
        if (!empty($data['service_id'])) {
            $service = SalonCrmService::query()
                ->where('salon_id', $salon->id)
                ->where('id', $data['service_id'])
                ->first();
            if (!$service) {
                return response()->json(['message' => 'Hizmet bulunamadı.', ...$snapshot], 422);
            }
        }

        $serviceName = $service?->name
            ?? trim((string) ($data['service_name'] ?? ''));
        if ($serviceName === '') {
            $serviceName = $isBlock ? ($blockLabels[$blockType] ?? 'Dolu') : 'Hizmet';
        }

        $priced = $this->resolveStaffServicePrice($staffModel, $service);
        $duration = (int) ($data['duration_minutes'] ?? $priced['duration'] ?? $service?->duration_minutes ?? 30);
        if ($isBlock && empty($data['duration_minutes'])) {
            $duration = match ($blockType) {
                'lunch' => 60,
                'wedding' => 180,
                'leave', 'closed' => 480,
                default => 30,
            };
        }

        $startsAt = $this->parseSalonDateTime((string) $data['starts_at']);
        $hoursError = $this->staffHoursConflict($staffModel, $startsAt, $duration);
        if ($hoursError) {
            return response()->json(['message' => $hoursError, ...$snapshot], 422);
        }
        if ($this->appointmentOverlaps($salon, $startsAt, $duration, $staffId, null, $isBlock)) {
            return response()->json([
                'message' => $isBlock
                    ? 'Seçilen saat dolu. Başka bir saat deneyin.'
                    : 'Bu saatte zaten randevu var. Dakikayı değiştirin veya mevcut randevunun süresini kısaltın.',
                ...$snapshot,
            ], 422);
        }

        $appointment = SalonCrmAppointment::query()->create([
            'salon_id' => $salon->id,
            'customer_id' => $customer?->id,
            'staff_id' => $staffId,
            'service_id' => $service?->id,
            'service_name' => $serviceName,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
            'duration_minutes' => $duration,
            'price' => $isBlock ? 0 : (float) (($priced['linked'] ?? false)
                ? $priced['price']
                : ($data['price'] ?? $priced['price'] ?? $service?->price ?? 0)),
            'status' => 'scheduled',
            'notes' => $data['notes'] ?? null,
            'is_block' => $isBlock,
            'block_type' => $blockType,
        ]);

        $appointment->load(['staff:id,name', 'service:id,name', 'customer:id,name,phone']);

        return response()->json([
            'message' => $isBlock ? 'Dolu slot kaydedildi.' : 'Randevu oluşturuldu.',
            ...$this->accessService->snapshot($salon, $salon->user),
            'appointment' => $this->appointmentPayload($appointment),
        ], 201);
    }

    public function appointmentsUpdate(Request $request, int $id)
    {
        [$salon, $snapshot, $error, $actor] = $this->resolveSalon($request, requireWrite: true);
        if ($error) {
            return $error;
        }

        $appointment = SalonCrmAppointment::query()
            ->where('salon_id', $salon->id)
            ->where('id', $id)
            ->first();

        if (!$appointment) {
            return response()->json(['message' => 'Randevu bulunamadı.', ...$snapshot], 404);
        }

        $role = $actor['role'] ?? 'owner';
        $actorStaffId = (int) ($actor['staff']?->id ?? 0);
        if ($role === 'staff') {
            if ((int) ($appointment->staff_id ?? 0) !== $actorStaffId) {
                return response()->json([
                    'message' => 'Yalnızca kendi randevunuzu düzenleyebilirsiniz.',
                    ...$snapshot,
                ], 403);
            }
        }

        if ($appointment->status !== 'scheduled') {
            return response()->json([
                'message' => 'Yalnızca planlanan randevunun saati kaydırılabilir.',
                ...$snapshot,
            ], 422);
        }

        $data = $request->validate([
            'customer_id' => ['nullable', 'integer'],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:32'],
            'starts_at' => ['nullable', 'date'],
            'staff_id' => ['nullable', 'integer'],
            'service_id' => ['nullable', 'integer'],
            'service_name' => ['nullable', 'string', 'max:120'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_block' => ['nullable', 'boolean'],
            'block_type' => ['nullable', 'in:lunch,wedding,leave,closed,other'],
        ]);

        $isBlock = array_key_exists('is_block', $data)
            ? (bool) $data['is_block']
            : (bool) $appointment->is_block;
        $blockType = $isBlock
            ? ($data['block_type'] ?? $appointment->block_type ?? 'other')
            : null;
        $blockLabels = [
            'lunch' => 'Öğle arası',
            'wedding' => 'Düğün / özel gün',
            'leave' => 'İzin',
            'closed' => 'Kapalı',
            'other' => 'Mola / dolu',
        ];

        $customer = $appointment->customer_id
            ? SalonCrmCustomer::query()
                ->where('salon_id', $salon->id)
                ->where('id', $appointment->customer_id)
                ->first()
            : null;
        if (array_key_exists('customer_id', $data)) {
            $customer = null;
            if (!empty($data['customer_id'])) {
                $customerQuery = SalonCrmCustomer::query()
                    ->where('salon_id', $salon->id)
                    ->where('id', $data['customer_id']);
                if ($role === 'staff') {
                    $customerQuery->where('staff_id', $actorStaffId);
                }
                $customer = $customerQuery->first();
                if (!$customer) {
                    return response()->json(['message' => 'Müşteri bulunamadı.', ...$snapshot], 422);
                }
            }
        }

        $customerName = $customer?->name
            ?? trim((string) ($data['customer_name'] ?? $appointment->customer_name ?? ''));
        $customerPhone = $customer?->phone
            ?? trim((string) ($data['customer_phone'] ?? $appointment->customer_phone ?? ''));

        if ($isBlock) {
            $label = $blockLabels[$blockType] ?? 'Dolu';
            if ($customerName === '' || strcasecmp($customerName, 'Dolu') === 0) {
                $customerName = $label;
            }
            if ($customerPhone === '') {
                $customerPhone = '-';
            }
        } elseif ($customerName === '') {
            return response()->json([
                'message' => 'Müşteri adı gerekli.',
                ...$snapshot,
            ], 422);
        }

        if ($customerPhone === '') {
            $customerPhone = '-';
        }

        $service = $appointment->service_id
            ? SalonCrmService::query()
                ->where('salon_id', $salon->id)
                ->where('id', $appointment->service_id)
                ->first()
            : null;
        if (array_key_exists('service_id', $data)) {
            $service = null;
            if (!empty($data['service_id'])) {
                $service = SalonCrmService::query()
                    ->where('salon_id', $salon->id)
                    ->where('id', $data['service_id'])
                    ->first();
                if (!$service) {
                    return response()->json(['message' => 'Hizmet bulunamadı.', ...$snapshot], 422);
                }
            }
        }

        $staffId = $appointment->staff_id;
        $staffModel = $staffId
            ? SalonCrmStaff::query()
                ->where('salon_id', $salon->id)
                ->where('id', $staffId)
                ->where('is_active', true)
                ->first()
            : null;
        if ($role === 'staff') {
            // Personel atamayı değiştiremez; kendisinde kalır
            $staffId = $actorStaffId;
            $staffModel = $actor['staff'] ?? $staffModel;
        } elseif (array_key_exists('staff_id', $data)) {
            $staffId = null;
            $staffModel = null;
            if (!empty($data['staff_id'])) {
                $staffModel = SalonCrmStaff::query()
                    ->where('salon_id', $salon->id)
                    ->where('id', $data['staff_id'])
                    ->where('is_active', true)
                    ->first();
                if (!$staffModel) {
                    return response()->json(['message' => 'Personel bulunamadı.', ...$snapshot], 422);
                }
                $staffId = $staffModel->id;
            }
        }

        $serviceName = $service?->name
            ?? trim((string) ($data['service_name'] ?? $appointment->service_name ?? ''));
        if ($serviceName === '') {
            $serviceName = $isBlock ? ($blockLabels[$blockType] ?? 'Dolu') : 'Hizmet';
        }

        $duration = (int) ($data['duration_minutes'] ?? $appointment->duration_minutes ?? 30);
        $startsAt = !empty($data['starts_at'])
            ? $this->parseSalonDateTime((string) $data['starts_at'])
            : $this->parseSalonDateTime((string) $appointment->starts_at);

        $hoursError = $this->staffHoursConflict($staffModel, $startsAt, $duration);
        if ($hoursError) {
            return response()->json(['message' => $hoursError, ...$snapshot], 422);
        }
        if ($this->appointmentOverlaps($salon, $startsAt, $duration, $staffId, $appointment->id, $isBlock)) {
            return response()->json([
                'message' => $isBlock
                    ? 'Seçilen saat dolu. Başka bir saat deneyin.'
                    : 'Bu saatte zaten randevu var. Dakikayı değiştirin.',
                ...$snapshot,
            ], 422);
        }

        $appointment->fill([
            'customer_id' => $customer?->id,
            'staff_id' => $staffId,
            'service_id' => $service?->id,
            'service_name' => $serviceName,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
            'duration_minutes' => $duration,
            'notes' => array_key_exists('notes', $data) ? ($data['notes'] ?? null) : $appointment->notes,
            'is_block' => $isBlock,
            'block_type' => $blockType,
        ]);
        $appointment->save();
        $appointment->load(['staff:id,name', 'service:id,name', 'customer:id,name,phone']);

        return response()->json([
            'message' => 'Randevu güncellendi.',
            ...$snapshot,
            'appointment' => $this->appointmentPayload($appointment),
        ]);
    }

    public function customersIndex(Request $request)
    {
        [$salon, $snapshot, $error, $actor] = $this->resolveSalon($request);
        if ($error) {
            return $error;
        }

        $q = trim((string) $request->query('q', ''));
        $query = SalonCrmCustomer::query()
            ->where('salon_id', $salon->id)
            ->orderBy('name');

        $role = $actor['role'] ?? 'owner';
        // Personel yalnızca kendi defterindeki müşterileri görür (patronunkiler değil)
        if ($role === 'staff') {
            $staffSelfId = (int) ($actor['staff']?->id ?? 0);
            if ($staffSelfId <= 0) {
                $query->whereRaw('1 = 0');
            } elseif (Schema::hasColumn('salon_crm_customers', 'staff_id')) {
                $query->where('staff_id', $staffSelfId);
            } else {
                // Migration öncesi geçici: sadece kendi randevularındaki müşteriler
                $ownCustomerIds = SalonCrmAppointment::query()
                    ->where('salon_id', $salon->id)
                    ->where('staff_id', $staffSelfId)
                    ->where('is_block', false)
                    ->whereNotNull('customer_id')
                    ->distinct()
                    ->pluck('customer_id');
                $query->whereIn('id', $ownCustomerIds->isEmpty() ? [0] : $ownCustomerIds);
            }
        }

        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', '%' . $q . '%')
                    ->orWhere('phone', 'like', '%' . $q . '%');
            });
        }

        $customers = $query->get();
        $customerIds = $customers->pluck('id')->filter()->values();
        $phones = $customers->pluck('phone')->filter()->values();

        $recentAppts = collect();
        if ($customerIds->isNotEmpty() || $phones->isNotEmpty()) {
            $recentAppts = SalonCrmAppointment::query()
                ->where('salon_id', $salon->id)
                ->where('is_block', false)
                ->when(
                    $role === 'staff',
                    fn ($b) => $b->where('staff_id', (int) ($actor['staff']?->id ?? 0))
                )
                ->where(function ($builder) use ($customerIds, $phones) {
                    if ($customerIds->isNotEmpty()) {
                        $builder->whereIn('customer_id', $customerIds);
                    }
                    if ($phones->isNotEmpty()) {
                        $builder->orWhereIn('customer_phone', $phones);
                    }
                })
                ->orderByDesc('starts_at')
                ->get(['id', 'customer_id', 'customer_phone', 'status', 'starts_at', 'service_name']);
        }

        $lastByCustomer = [];
        $noShowCount = [];
        foreach ($recentAppts as $appt) {
            $keys = [];
            if ($appt->customer_id) {
                $keys[] = 'id:'.$appt->customer_id;
            }
            if ($appt->customer_phone) {
                $keys[] = 'ph:'.$appt->customer_phone;
            }
            foreach ($keys as $key) {
                if (!isset($lastByCustomer[$key])) {
                    $lastByCustomer[$key] = $appt;
                }
                if ($appt->status === 'no_show') {
                    $noShowCount[$key] = ($noShowCount[$key] ?? 0) + 1;
                }
            }
        }

        $payload = $customers->map(function (SalonCrmCustomer $c) use ($lastByCustomer, $noShowCount) {
            $last = $lastByCustomer['id:'.$c->id] ?? $lastByCustomer['ph:'.$c->phone] ?? null;
            $missed = $last && $last->status === 'no_show';
            $count = (int) (($noShowCount['id:'.$c->id] ?? 0) ?: ($noShowCount['ph:'.$c->phone] ?? 0));

            return [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'notes' => $c->notes,
                'staff_id' => (int) ($c->staff_id ?? 0),
                'created_at' => optional($c->created_at)->toIso8601String(),
                'missed_last' => (bool) $missed,
                'no_show_count' => $count,
                'last_status' => $last?->status,
                'last_service_name' => $last?->service_name,
                'last_starts_at' => $last
                    ? $this->formatSalonDateTime($last->getRawOriginal('starts_at') ?? $last->starts_at)
                    : null,
            ];
        });

        return response()->json([
            ...$snapshot,
            'role' => $role,
            'staff_id' => $role === 'staff' ? (int) ($actor['staff']?->id ?? 0) : null,
            'customers' => $payload,
        ]);
    }

    public function customersStore(Request $request)
    {
        [$salon, $snapshot, $error, $actor] = $this->resolveSalon($request, requireWrite: true);
        if ($error) {
            return $error;
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:32'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $role = $actor['role'] ?? 'owner';
        $ownerStaffKey = 0;
        $staffKey = $role === 'staff'
            ? (int) ($actor['staff']?->id ?? 0)
            : $ownerStaffKey;
        if ($role === 'staff' && $staffKey <= 0) {
            return response()->json(['message' => 'Personel oturumu geçersiz.', ...$snapshot], 403);
        }

        $phone = trim($data['phone']);
        $hasStaffOwnerCol = Schema::hasColumn('salon_crm_customers', 'staff_id');

        $existingQuery = SalonCrmCustomer::query()
            ->where('salon_id', $salon->id)
            ->where('phone', $phone);
        if ($hasStaffOwnerCol) {
            $existingQuery->where('staff_id', $staffKey);
        } elseif ($role === 'staff') {
            // Eski şemada salon genelinde telefon tekildir; personel patron kaydını göremesin
            $existing = $existingQuery->first();
            if ($existing) {
                return response()->json([
                    'message' => 'Bu telefon salonda kayıtlı. Farklı bir telefon deneyin veya migration uygulayın.',
                    ...$snapshot,
                ], 422);
            }
        }

        $existing = $existingQuery->first();

        if ($existing) {
            return response()->json([
                'message' => 'Bu telefon kendi listenizde zaten kayıtlı.',
                ...$snapshot,
                'customer' => [
                    'id' => $existing->id,
                    'name' => $existing->name,
                    'phone' => $existing->phone,
                    'notes' => $existing->notes,
                ],
            ], 422);
        }

        $attrs = [
            'salon_id' => $salon->id,
            'name' => trim($data['name']),
            'phone' => $phone,
            'notes' => $data['notes'] ?? null,
        ];
        if ($hasStaffOwnerCol) {
            $attrs['staff_id'] = $staffKey;
        }

        try {
            $customer = SalonCrmCustomer::query()->create($attrs);
        } catch (\Throwable $e) {
            Log::error('salon-crm customersStore', [
                'message' => $e->getMessage(),
                'role' => $role,
                'staff_key' => $staffKey,
            ]);

            return response()->json([
                'message' => 'Müşteri eklenemedi. Telefon çakışıyor olabilir.',
                ...$snapshot,
            ], 422);
        }

        return response()->json([
            'message' => 'Müşteri eklendi.',
            ...$snapshot,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'notes' => $customer->notes,
                'staff_id' => (int) ($customer->staff_id ?? 0),
            ],
        ], 201);
    }

    public function customersUpdate(Request $request, int $id)
    {
        [$salon, $snapshot, $error, $actor] = $this->resolveSalon($request, requireWrite: true);
        if ($error) {
            return $error;
        }

        $customerQuery = SalonCrmCustomer::query()
            ->where('salon_id', $salon->id)
            ->where('id', $id);
        if (($actor['role'] ?? '') === 'staff') {
            $customerQuery->where('staff_id', (int) ($actor['staff']?->id ?? 0));
        }
        $customer = $customerQuery->first();
        if (!$customer) {
            return response()->json(['message' => 'Müşteri bulunamadı.', ...$snapshot], 404);
        }

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $customer->notes = trim((string) ($data['notes'] ?? '')) ?: null;
        $customer->save();

        return response()->json([
            'message' => 'Müşteri notu kaydedildi.',
            ...$snapshot,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'notes' => $customer->notes,
            ],
        ]);
    }

    public function ledgerIndex(Request $request)
    {
        [$salon, $snapshot, $error] = $this->resolveSalon($request, ownerOnly: true);
        if ($error) {
            return $error;
        }

        $date = $request->query('date');
        $day = $date
            ? Carbon::parse($date, 'Europe/Istanbul')->startOfDay()
            : Carbon::now('Europe/Istanbul')->startOfDay();

        $entries = SalonCrmLedgerEntry::query()
            ->with(['staff:id,name'])
            ->where('salon_id', $salon->id)
            ->whereDate('entry_date', $day->toDateString())
            ->orderByDesc('id')
            ->get()
            ->map(fn (SalonCrmLedgerEntry $e) => $this->ledgerPayload($e));

        $income = (float) SalonCrmLedgerEntry::query()
            ->where('salon_id', $salon->id)
            ->whereDate('entry_date', $day->toDateString())
            ->where('type', 'income')
            ->sum('amount');

        $expense = (float) SalonCrmLedgerEntry::query()
            ->where('salon_id', $salon->id)
            ->whereDate('entry_date', $day->toDateString())
            ->where('type', 'expense')
            ->sum('amount');

        [$marketEntries, $marketExpense] = $this->marketplaceExpenses($salon, $day);
        $expense += $marketExpense;
        $entries = $entries->concat($marketEntries)->values();

        return response()->json([
            ...$snapshot,
            'date' => $day->toDateString(),
            'summary' => [
                'income' => round($income, 2),
                'expense' => round($expense, 2),
                'net' => round($income - $expense, 2),
                'marketplace_expense' => round($marketExpense, 2),
            ],
            'entries' => $entries,
        ]);
    }

    public function ledgerStore(Request $request)
    {
        [$salon, $snapshot, $error] = $this->resolveSalon($request, requireWrite: true, ownerOnly: true);
        if ($error) {
            return $error;
        }

        $data = $request->validate([
            'type' => ['required', 'in:income,expense'],
            'title' => ['required', 'string', 'max:160'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'entry_date' => ['nullable', 'date'],
            'category' => ['nullable', 'string', 'max:80'],
            'staff_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $staffId = null;
        if (!empty($data['staff_id'])) {
            $staff = SalonCrmStaff::query()
                ->where('salon_id', $salon->id)
                ->where('id', $data['staff_id'])
                ->first();
            if (!$staff) {
                return response()->json(['message' => 'Personel bulunamadı.', ...$snapshot], 422);
            }
            $staffId = $staff->id;
        }

        $entry = SalonCrmLedgerEntry::query()->create([
            'salon_id' => $salon->id,
            'staff_id' => $staffId,
            'type' => $data['type'],
            'category' => $data['category'] ?? null,
            'title' => trim($data['title']),
            'amount' => (float) $data['amount'],
            'entry_date' => isset($data['entry_date'])
                ? Carbon::parse($data['entry_date'])->toDateString()
                : Carbon::now('Europe/Istanbul')->toDateString(),
            'notes' => $data['notes'] ?? null,
        ]);

        $entry->load(['staff:id,name']);

        return response()->json([
            'message' => $data['type'] === 'income' ? 'Gelir eklendi.' : 'Gider eklendi.',
            ...$snapshot,
            'entry' => $this->ledgerPayload($entry),
        ], 201);
    }

    public function staffPerformance(Request $request)
    {
        [$salon, $snapshot, $error, $actor] = $this->resolveSalon($request);
        if ($error) {
            return $error;
        }

        $from = $request->query('from')
            ? Carbon::parse($request->query('from'), 'Europe/Istanbul')->startOfDay()
            : Carbon::now('Europe/Istanbul')->startOfMonth();
        $to = $request->query('to')
            ? Carbon::parse($request->query('to'), 'Europe/Istanbul')->endOfDay()
            : Carbon::now('Europe/Istanbul')->endOfMonth();

        if ($to->lt($from)) {
            return response()->json(['message' => 'Geçersiz tarih aralığı.'], 422);
        }

        $role = $actor['role'] ?? 'owner';
        $staffQuery = SalonCrmStaff::query()
            ->where('salon_id', $salon->id)
            ->orderBy('name');

        if ($role === 'staff' && !empty($actor['staff'])) {
            $staffQuery->where('id', $actor['staff']->id);
        }

        $staffList = $staffQuery->get(['id', 'name', 'is_active', 'commission_percent']);
        $staffIds = $staffList->pluck('id')->all();

        $apptRows = SalonCrmAppointment::query()
            ->where('salon_id', $salon->id)
            ->where('is_block', false)
            ->whereBetween('starts_at', [$from, $to])
            ->when($role === 'staff', fn ($q) => $q->whereIn('staff_id', $staffIds ?: [0]))
            ->selectRaw('staff_id')
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count")
            ->selectRaw("SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled_count")
            ->selectRaw("SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count")
            ->selectRaw("SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) as no_show_count")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN price ELSE 0 END) as completed_revenue")
            ->selectRaw("SUM(CASE WHEN status = 'completed' AND payment_status = 'paid' THEN staff_share ELSE 0 END) as staff_share_total")
            ->selectRaw("SUM(CASE WHEN status = 'completed' AND payment_status = 'paid' THEN owner_share ELSE 0 END) as owner_share_total")
            ->selectRaw("SUM(CASE WHEN status = 'completed' AND payment_status = 'unpaid' THEN price ELSE 0 END) as credit_total")
            ->selectRaw('COUNT(*) as total_count')
            ->groupBy('staff_id')
            ->get()
            ->keyBy(fn ($r) => $r->staff_id === null ? 'none' : (string) $r->staff_id);

        $ledgerRows = SalonCrmLedgerEntry::query()
            ->where('salon_id', $salon->id)
            ->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])
            ->when($role === 'staff', fn ($q) => $q->whereIn('staff_id', $staffIds ?: [0]))
            // Randevu tahsilatı appointment.price ile sayılır; kasa kopyasını ciroya ekleme
            ->whereNull('appointment_id')
            ->where(function ($q) {
                $q->whereNull('category')->orWhere('category', '!=', 'randevu');
            })
            ->selectRaw('staff_id')
            ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income_total")
            ->selectRaw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense_total")
            ->groupBy('staff_id')
            ->get()
            ->keyBy(fn ($r) => $r->staff_id === null ? 'none' : (string) $r->staff_id);

        $items = [];
        foreach ($staffList as $staff) {
            $key = (string) $staff->id;
            $appt = $apptRows->get($key);
            $led = $ledgerRows->get($key);
            $completedRevenue = round((float) ($appt->completed_revenue ?? 0), 2);
            $ledgerIncome = round((float) ($led->income_total ?? 0), 2);
            $staffShare = round((float) ($appt->staff_share_total ?? 0), 2);
            $ownerShare = round((float) ($appt->owner_share_total ?? 0), 2);
            $creditTotal = round((float) ($appt->credit_total ?? 0), 2);
            $items[] = [
                'staff_id' => $staff->id,
                'staff_name' => $staff->name,
                'is_active' => (bool) $staff->is_active,
                'commission_percent' => (float) ($staff->commission_percent ?? 0),
                'appointments' => [
                    'total' => (int) ($appt->total_count ?? 0),
                    'completed' => (int) ($appt->completed_count ?? 0),
                    'scheduled' => (int) ($appt->scheduled_count ?? 0),
                    'cancelled' => (int) ($appt->cancelled_count ?? 0),
                    'no_show' => (int) ($appt->no_show_count ?? 0),
                    'revenue' => $completedRevenue,
                    'staff_share' => $staffShare,
                    'owner_share' => $ownerShare,
                    'credit' => $creditTotal,
                ],
                'ledger' => [
                    'income' => $ledgerIncome,
                    'expense' => round((float) ($led->expense_total ?? 0), 2),
                ],
                'total_revenue' => round($completedRevenue + $ledgerIncome, 2),
                'staff_share' => $staffShare,
                'owner_share' => $ownerShare,
            ];
        }

        usort($items, fn ($a, $b) => $b['total_revenue'] <=> $a['total_revenue']);

        $unassigned = null;
        if ($role === 'owner') {
            $apptNone = $apptRows->get('none');
            $ledNone = $ledgerRows->get('none');
            $completedRevenue = round((float) ($apptNone->completed_revenue ?? 0), 2);
            $ledgerIncome = round((float) ($ledNone->income_total ?? 0), 2);
            $unassigned = [
                'staff_id' => null,
                'staff_name' => 'Atanmamış',
                'commission_percent' => 0,
                'appointments' => [
                    'total' => (int) ($apptNone->total_count ?? 0),
                    'completed' => (int) ($apptNone->completed_count ?? 0),
                    'scheduled' => (int) ($apptNone->scheduled_count ?? 0),
                    'cancelled' => (int) ($apptNone->cancelled_count ?? 0),
                    'no_show' => (int) ($apptNone->no_show_count ?? 0),
                    'revenue' => $completedRevenue,
                    'staff_share' => 0,
                    'owner_share' => round((float) ($apptNone->owner_share_total ?? 0), 2),
                    'credit' => round((float) ($apptNone->credit_total ?? 0), 2),
                ],
                'ledger' => [
                    'income' => $ledgerIncome,
                    'expense' => round((float) ($ledNone->expense_total ?? 0), 2),
                ],
                'total_revenue' => round($completedRevenue + $ledgerIncome, 2),
                'staff_share' => 0,
                'owner_share' => round((float) ($apptNone->owner_share_total ?? 0), 2),
            ];
        }

        $totals = [
            'completed' => array_sum(array_map(fn ($i) => $i['appointments']['completed'], $items)),
            'scheduled' => array_sum(array_map(fn ($i) => $i['appointments']['scheduled'], $items)),
            'cancelled' => array_sum(array_map(fn ($i) => $i['appointments']['cancelled'], $items)),
            'no_show' => array_sum(array_map(fn ($i) => $i['appointments']['no_show'], $items)),
            'appointment_revenue' => round(array_sum(array_map(fn ($i) => $i['appointments']['revenue'], $items)), 2),
            'ledger_income' => round(array_sum(array_map(fn ($i) => $i['ledger']['income'], $items)), 2),
            'ledger_expense' => round(array_sum(array_map(fn ($i) => $i['ledger']['expense'], $items)), 2),
            'total_revenue' => round(array_sum(array_map(fn ($i) => $i['total_revenue'], $items)), 2),
            'staff_share' => round(array_sum(array_map(fn ($i) => $i['staff_share'], $items)), 2),
            'owner_share' => round(array_sum(array_map(fn ($i) => $i['owner_share'], $items)), 2),
            'credit' => round(array_sum(array_map(fn ($i) => $i['appointments']['credit'], $items)), 2),
        ];
        if ($unassigned) {
            $totals['completed'] += $unassigned['appointments']['completed'];
            $totals['scheduled'] += $unassigned['appointments']['scheduled'];
            $totals['cancelled'] += $unassigned['appointments']['cancelled'];
            $totals['no_show'] += $unassigned['appointments']['no_show'];
            $totals['appointment_revenue'] = round(
                $totals['appointment_revenue'] + $unassigned['appointments']['revenue'],
                2
            );
            $totals['ledger_income'] = round(
                $totals['ledger_income'] + $unassigned['ledger']['income'],
                2
            );
            $totals['ledger_expense'] = round(
                $totals['ledger_expense'] + $unassigned['ledger']['expense'],
                2
            );
            $totals['total_revenue'] = round(
                $totals['total_revenue'] + $unassigned['total_revenue'],
                2
            );
            $totals['staff_share'] = round($totals['staff_share'] + $unassigned['staff_share'], 2);
            $totals['owner_share'] = round($totals['owner_share'] + $unassigned['owner_share'], 2);
            $totals['credit'] = round($totals['credit'] + $unassigned['appointments']['credit'], 2);
        }

        // Tahsil edilen randevu cirosu ve salona kalan (personel komisyonu düşülmüş)
        $paidRevenueRow = SalonCrmAppointment::query()
            ->where('salon_id', $salon->id)
            ->where('is_block', false)
            ->where('status', 'completed')
            ->where('payment_status', 'paid')
            ->whereBetween('starts_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->when($role === 'staff', fn ($q) => $q->whereIn('staff_id', $staffIds ?: [0]))
            ->selectRaw('COALESCE(SUM(price), 0) as paid_revenue')
            ->selectRaw('COALESCE(SUM(staff_share), 0) as staff_share_paid')
            ->first();
        $totals['paid_revenue'] = round((float) ($paidRevenueRow->paid_revenue ?? 0), 2);
        $totals['staff_share'] = round((float) ($paidRevenueRow->staff_share_paid ?? $totals['staff_share']), 2);
        // Salona kalan = tahsil edilen − personel payı (eski owner_share sapmalarını düzeltir)
        $totals['owner_share'] = round(
            max(0, $totals['paid_revenue'] - $totals['staff_share']),
            2
        );

        // Salon geneli: manuel kasa (randevu kopyası hariç) + gider
        if ($role === 'owner') {
            $salonLedger = SalonCrmLedgerEntry::query()
                ->where('salon_id', $salon->id)
                ->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])
                ->where(function ($q) {
                    $q->whereNull('appointment_id')
                        ->where(function ($q2) {
                            $q2->whereNull('category')->orWhere('category', '!=', 'randevu');
                        });
                })
                ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income_total")
                ->selectRaw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense_total")
                ->first();
            $totals['ledger_income'] = round((float) ($salonLedger->income_total ?? 0), 2);
            $totals['ledger_expense'] = round((float) ($salonLedger->expense_total ?? 0), 2);
            // Personel satırlarından gelen total_revenue'yu salon manuel kasaya göre yeniden hesapla
            $totals['total_revenue'] = round(
                $totals['appointment_revenue'] + $totals['ledger_income'],
                2
            );
        }

        $totals['net'] = round(
            $totals['appointment_revenue'] + $totals['ledger_income'] - $totals['ledger_expense'],
            2
        );
        $totals['came'] = $totals['completed'];
        $totals['did_not_come'] = $totals['no_show'];

        $customerStats = SalonCrmAppointment::query()
            ->where('salon_id', $salon->id)
            ->where('is_block', false)
            ->where('status', 'completed')
            ->whereBetween('starts_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->whereNotNull('customer_id')
            ->when($role === 'staff', fn ($q) => $q->whereIn('staff_id', $staffIds ?: [0]))
            ->selectRaw('customer_id, COUNT(*) as visit_count')
            ->groupBy('customer_id')
            ->get();

        $totals['unique_customers'] = $customerStats->count();
        $totals['repeat_customers'] = $customerStats->where('visit_count', '>=', 2)->count();

        // Süresi geçmiş ama sonucu girilmemiş randevular (gün özeti)
        $now = Carbon::now('Europe/Istanbul');
        $needsOutcomeQuery = SalonCrmAppointment::query()
            ->where('salon_id', $salon->id)
            ->where('is_block', false)
            ->whereIn('status', ['scheduled', 'pending'])
            ->whereRaw('DATE_ADD(starts_at, INTERVAL duration_minutes MINUTE) < ?', [$now->format('Y-m-d H:i:s')])
            ->when($role === 'staff', fn ($q) => $q->whereIn('staff_id', $staffIds ?: [0]));
        $totals['needs_outcome'] = (clone $needsOutcomeQuery)
            ->whereBetween('starts_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->count();

        // Ödeme tipi dağılımı (tamamlanan randevular)
        $payStats = SalonCrmAppointment::query()
            ->where('salon_id', $salon->id)
            ->where('is_block', false)
            ->where('status', 'completed')
            ->whereBetween('starts_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->when($role === 'staff', fn ($q) => $q->whereIn('staff_id', $staffIds ?: [0]))
            ->selectRaw("SUM(CASE WHEN payment_method = 'cash' AND payment_status = 'paid' THEN price ELSE 0 END) as cash_amount")
            ->selectRaw("SUM(CASE WHEN payment_method = 'card' AND payment_status = 'paid' THEN price ELSE 0 END) as card_amount")
            ->selectRaw("SUM(CASE WHEN payment_method = 'iban' AND payment_status = 'paid' THEN price ELSE 0 END) as iban_amount")
            ->selectRaw("SUM(CASE WHEN payment_method = 'credit' OR payment_status = 'unpaid' THEN price ELSE 0 END) as credit_amount")
            ->selectRaw("SUM(CASE WHEN payment_method = 'cash' AND payment_status = 'paid' THEN 1 ELSE 0 END) as cash_count")
            ->selectRaw("SUM(CASE WHEN payment_method = 'card' AND payment_status = 'paid' THEN 1 ELSE 0 END) as card_count")
            ->selectRaw("SUM(CASE WHEN payment_method = 'iban' AND payment_status = 'paid' THEN 1 ELSE 0 END) as iban_count")
            ->selectRaw("SUM(CASE WHEN payment_method = 'credit' OR payment_status = 'unpaid' THEN 1 ELSE 0 END) as credit_count")
            ->first();

        $totals['payments'] = [
            'cash' => round((float) ($payStats->cash_amount ?? 0), 2),
            'card' => round((float) ($payStats->card_amount ?? 0), 2),
            'iban' => round((float) ($payStats->iban_amount ?? 0), 2),
            'credit' => round((float) ($payStats->credit_amount ?? 0), 2),
            'cash_count' => (int) ($payStats->cash_count ?? 0),
            'card_count' => (int) ($payStats->card_count ?? 0),
            'iban_count' => (int) ($payStats->iban_count ?? 0),
            'credit_count' => (int) ($payStats->credit_count ?? 0),
        ];
        $totals['payments']['collected'] = round(
            $totals['payments']['cash'] + $totals['payments']['card'] + $totals['payments']['iban'],
            2
        );

        return response()->json([
            ...$snapshot,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'role' => $role,
            'totals' => $totals,
            'staff' => $items,
            'unassigned' => $unassigned,
        ]);
    }

    public function appointmentsUpdateStatus(Request $request, int $id)
    {
        [$salon, $snapshot, $error, $actor] = $this->resolveSalon($request, requireWrite: true);
        if ($error) {
            return $error;
        }

        $data = $request->validate([
            'status' => ['required', 'in:scheduled,completed,cancelled,no_show,pending'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'in:cash,card,credit,iban'],
        ]);

        $appointment = SalonCrmAppointment::query()
            ->where('salon_id', $salon->id)
            ->where('id', $id)
            ->first();

        if (!$appointment) {
            return response()->json(['message' => 'Randevu bulunamadı.', ...$snapshot], 404);
        }

        if ($appointment->is_block) {
            if ($data['status'] !== 'cancelled') {
                return response()->json(['message' => 'Kapalı saat tahsil edilemez.', ...$snapshot], 422);
            }
            $appointment->status = 'cancelled';
            $appointment->save();

            return response()->json([
                'message' => 'Kapalı saat kaldırıldı.',
                ...$snapshot,
                'appointment' => $this->appointmentPayload($appointment),
            ]);
        }

        $role = $actor['role'] ?? '';
        $actorStaffId = (int) ($actor['staff']?->id ?? 0);
        $assignedStaffId = $appointment->staff_id ? (int) $appointment->staff_id : null;
        $wantsPriceChange = array_key_exists('price', $data) && $data['price'] !== null;
        $completing = $data['status'] === 'completed';

        if ($role === 'staff') {
            if (!$assignedStaffId || $assignedStaffId !== $actorStaffId) {
                return response()->json([
                    'message' => 'Yalnızca kendi randevularınızı güncelleyebilirsiniz.',
                    ...$snapshot,
                ], 403);
            }
        }

        if ($assignedStaffId) {
            if ($completing || $wantsPriceChange) {
                if ($role !== 'staff' || $actorStaffId !== $assignedStaffId) {
                    return response()->json([
                        'message' => 'Bu randevunun ücretini yalnızca ilgili personel girebilir veya düzeltebilir.',
                        ...$snapshot,
                    ], 422);
                }
            }
        } elseif ($completing || $wantsPriceChange) {
            if ($role === 'staff') {
                return response()->json([
                    'message' => 'Salon sahibi randevusunu yalnızca patron tamamlayabilir.',
                    ...$snapshot,
                ], 422);
            }
        }

        $previousStatus = $appointment->status;
        $alreadyPaid = ($appointment->payment_status ?? '') === 'paid';

        if ($data['status'] === 'completed' && array_key_exists('price', $data)) {
            $appointment->price = (float) $data['price'];
        }

        if ($data['status'] === 'completed') {
            $price = (float) $appointment->price;
            $method = $data['payment_method'] ?? $appointment->payment_method;
            if ($price > 0 && empty($method)) {
                return response()->json([
                    'message' => 'Ödeme tipi seçin: nakit, kart, veresiye veya IBAN.',
                    ...$snapshot,
                ], 422);
            }

            if ($previousStatus !== 'completed' || (!$alreadyPaid && $method && $method !== 'credit')) {
                $this->applyAppointmentPayment($salon, $appointment, $method ?: 'cash');
            }
        }

        $appointment->status = $data['status'];
        $appointment->save();
        $appointment->load(['staff:id,name,commission_percent', 'service:id,name', 'customer:id,name,phone,notes']);

        return response()->json([
            'message' => 'Randevu güncellendi.',
            ...$snapshot,
            'appointment' => $this->appointmentPayload($appointment),
        ]);
    }

    public function customerCatalog(Request $request)
    {
        [$salon, $snapshot, $error, $actor] = $this->resolveSalon($request);
        if ($error) {
            return $error;
        }
        if (($actor['role'] ?? '') !== 'customer') {
            return response()->json(['message' => 'Müşteri girişi gerekli.'], 403);
        }

        $showProfile = (bool) $salon->show_profile_to_customers;

        $services = SalonCrmService::query()
            ->where('salon_id', $salon->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'duration_minutes', 'price', 'is_active']);

        $staff = SalonCrmStaff::query()
            ->where('salon_id', $salon->id)
            ->where('is_active', true)
            ->with(['staffServices.service'])
            ->orderBy('name')
            ->get(['id', 'name', 'photo', 'show_photo_to_customers']);

        return response()->json([
            'salon' => [
                'id' => $salon->id,
                'name' => $salon->name,
                'owner_username' => $salon->owner_username,
                'type' => $salon->type,
                'phone' => $showProfile ? $salon->phone : null,
                'logo_image' => $showProfile ? $salon->logo_image : null,
                'cover_image' => $showProfile ? $salon->cover_image : null,
                'profile_text' => $showProfile ? $salon->profile_text : null,
                'show_profile_to_customers' => $showProfile,
            ],
            'customer' => [
                'id' => $actor['customer']->id,
                'name' => $actor['customer']->name,
                'phone' => $actor['customer']->phone,
            ],
            'services' => $services,
            'staff' => $staff->map(function (SalonCrmStaff $s) use ($showProfile) {
                $visible = $showProfile && $s->show_photo_to_customers;

                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'photo' => $visible ? $s->photo : null,
                    'services' => $this->staffServiceRows($s),
                ];
            }),
        ]);
    }

    public function customerAppointmentsIndex(Request $request)
    {
        [$salon, $snapshot, $error, $actor] = $this->resolveSalon($request);
        if ($error) {
            return $error;
        }
        if (($actor['role'] ?? '') !== 'customer' || empty($actor['customer'])) {
            return response()->json(['message' => 'Müşteri girişi gerekli.'], 403);
        }

        $customer = $actor['customer'];
        $items = SalonCrmAppointment::query()
            ->with(['staff:id,name', 'service:id,name'])
            ->where('salon_id', $salon->id)
            ->where(function ($q) use ($customer) {
                $q->where('customer_id', $customer->id)
                    ->orWhere(function ($q2) use ($customer) {
                        $q2->whereNull('customer_id')
                            ->where('customer_phone', $customer->phone);
                    });
            })
            ->where('is_block', false)
            ->orderByDesc('starts_at')
            ->limit(50)
            ->get()
            ->map(fn (SalonCrmAppointment $a) => $this->appointmentPayload($a));

        return response()->json([
            'appointments' => $items,
        ]);
    }

    public function customerAppointmentsStore(Request $request)
    {
        [$salon, $snapshot, $error, $actor] = $this->resolveSalon($request);
        if ($error) {
            return $error;
        }
        if (($actor['role'] ?? '') !== 'customer' || empty($actor['customer'])) {
            return response()->json(['message' => 'Müşteri girişi gerekli.'], 403);
        }

        $customer = $actor['customer'];

        $data = $request->validate([
            'starts_at' => ['required', 'date', 'after:now'],
            'service_id' => ['nullable', 'integer'],
            'service_name' => ['nullable', 'string', 'max:120'],
            'staff_id' => ['nullable', 'integer'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $service = null;
        if (!empty($data['service_id'])) {
            $service = SalonCrmService::query()
                ->where('salon_id', $salon->id)
                ->where('is_active', true)
                ->where('id', $data['service_id'])
                ->first();
            if (!$service) {
                return response()->json(['message' => 'Hizmet bulunamadı.'], 422);
            }
        }

        $serviceName = $service?->name
            ?? trim((string) ($data['service_name'] ?? ''));
        if ($serviceName === '') {
            return response()->json(['message' => 'Hizmet seçin.'], 422);
        }

        $staffId = null;
        $staffModel = null;
        if (!empty($data['staff_id'])) {
            $staffModel = SalonCrmStaff::query()
                ->where('salon_id', $salon->id)
                ->where('is_active', true)
                ->where('id', $data['staff_id'])
                ->first();
            if (!$staffModel) {
                return response()->json(['message' => 'Personel bulunamadı.'], 422);
            }
            $staffId = $staffModel->id;
        }

        $startsAt = $this->parseSalonDateTime((string) $data['starts_at']);
        $priced = $this->resolveStaffServicePrice($staffModel, $service);
        $duration = (int) ($data['duration_minutes'] ?? $priced['duration'] ?? $service?->duration_minutes ?? 30);
        $hoursError = $this->staffHoursConflict($staffModel, $startsAt, $duration);
        if ($hoursError) {
            return response()->json(['message' => $hoursError], 422);
        }
        if ($this->appointmentOverlaps($salon, $startsAt, $duration, $staffId, null, false)) {
            return response()->json([
                'message' => 'Bu saatte zaten randevu var. Başka bir dakika seçin.',
            ], 422);
        }

        $appointment = SalonCrmAppointment::query()->create([
            'salon_id' => $salon->id,
            'customer_id' => $customer->id,
            'staff_id' => $staffId,
            'service_id' => $service?->id,
            'service_name' => $serviceName,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
            'duration_minutes' => $duration,
            'price' => (float) (($priced['linked'] ?? false)
                ? $priced['price']
                : ($priced['price'] ?? $service?->price ?? 0)),
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
            'is_block' => false,
        ]);

        $appointment->load(['staff:id,name', 'service:id,name']);

        return response()->json([
            'message' => 'Talebiniz alındı. Salon onaylayınca randevunuz kesinleşir.',
            'appointment' => $this->appointmentPayload($appointment),
        ], 201);
    }

    /**
     * @return array{0:?SalonCrmSalon,1:array,2:?\Illuminate\Http\JsonResponse,3:?array}
     */
    private function resolveSalon(Request $request, bool $requireWrite = false, bool $ownerOnly = false): array
    {
        $actor = $request->attributes->get('salon_crm_actor');
        if (!$actor || empty($actor['salon'])) {
            $empty = $this->accessService->snapshot(null);
            return [null, $empty, response()->json(array_merge($empty, [
                'message' => 'CRM girişi gerekli.',
            ]), 401), null];
        }

        /** @var SalonCrmSalon $salon */
        $salon = $actor['salon'];
        $role = $actor['role'] ?? 'owner';
        $linkedUser = $salon->user_id ? $salon->user : null;

        if ($ownerOnly && $role !== 'owner') {
            $snapshot = $this->accessService->snapshot($salon, $linkedUser);
            return [null, $snapshot, response()->json(array_merge($snapshot, [
                'message' => 'Bu işlem yalnızca salon patronu tarafından yapılabilir.',
            ]), 403), $actor];
        }

        $snapshot = $this->accessService->snapshot($salon, $linkedUser);

        // Personel/müşteri yazma: salon kilidi geçerli
        $canWriteRole = in_array($role, ['owner', 'staff'], true);
        if ($requireWrite) {
            if (!$canWriteRole) {
                return [null, $snapshot, response()->json(array_merge($snapshot, [
                    'message' => 'Bu işlem için yetkiniz yok.',
                ]), 403), $actor];
            }
            if (!$snapshot['access']['can_write']) {
                return [null, $snapshot, response()->json(array_merge($snapshot, [
                    'message' => 'CRM kilitli. İşlem yapılamaz.',
                ]), 403), $actor];
            }
        }

        return [$salon, $snapshot, null, $actor];
    }

    private function appointmentPayload(SalonCrmAppointment $a): array
    {
        return [
            'id' => $a->id,
            'customer_id' => $a->customer_id,
            'staff_id' => $a->staff_id,
            'staff_name' => $a->staff?->name,
            'staff_commission_percent' => (float) ($a->staff?->commission_percent ?? 0),
            'service_id' => $a->service_id,
            'service_name' => $a->service_name,
            'customer_name' => $a->customer_name,
            'customer_phone' => $a->customer_phone,
            'customer_notes' => $a->customer?->notes,
            'starts_at' => $this->formatSalonDateTime($a->getRawOriginal('starts_at') ?? $a->starts_at),
            'duration_minutes' => $a->duration_minutes,
            'price' => (float) $a->price,
            'status' => $a->status,
            'notes' => $a->notes,
            'is_block' => (bool) $a->is_block,
            'block_type' => $a->block_type,
            'payment_method' => $a->payment_method,
            'payment_status' => $a->payment_status,
            'commission_percent' => $a->commission_percent !== null ? (float) $a->commission_percent : null,
            'staff_share' => $a->staff_share !== null ? (float) $a->staff_share : null,
            'owner_share' => $a->owner_share !== null ? (float) $a->owner_share : null,
        ];
    }

    private function applyAppointmentPayment(SalonCrmSalon $salon, SalonCrmAppointment $appointment, string $method): void
    {
        $price = (float) $appointment->price;
        $staff = $appointment->staff_id
            ? SalonCrmStaff::query()->where('salon_id', $salon->id)->where('id', $appointment->staff_id)->first()
            : null;
        $percent = 0;
        if (($staff?->pay_type ?? 'percent') !== 'net') {
            $percent = (float) ($staff?->commission_percent ?? 0);
        }
        $staffShare = round($price * $percent / 100, 2);
        $ownerShare = round($price - $staffShare, 2);
        $paid = $method !== 'credit';

        $appointment->payment_method = $method;
        $appointment->payment_status = $price <= 0 ? 'paid' : ($paid ? 'paid' : 'unpaid');
        $appointment->commission_percent = $percent;
        $appointment->staff_share = $staffShare;
        $appointment->owner_share = $ownerShare;
        $appointment->save();

        if (!$paid || $price <= 0) {
            return;
        }

        $exists = Schema::hasColumn('salon_crm_ledger_entries', 'appointment_id')
            && SalonCrmLedgerEntry::query()
                ->where('appointment_id', $appointment->id)
                ->where('type', 'income')
                ->exists();
        if ($exists) {
            return;
        }

        $labels = [
            'cash' => 'Nakit',
            'card' => 'Kart',
            'iban' => 'IBAN',
            'credit' => 'Veresiye',
        ];
        $payLabel = $labels[$method] ?? $method;
        $split = $percent > 0
            ? ' · Personel '.$staffShare.' ₺ / Salon '.$ownerShare.' ₺'
            : '';

        SalonCrmLedgerEntry::query()->create([
            'salon_id' => $salon->id,
            'staff_id' => $appointment->staff_id,
            'appointment_id' => $appointment->id,
            'type' => 'income',
            'category' => 'randevu',
            'payment_method' => $method,
            'title' => trim($appointment->service_name.' · '.$appointment->customer_name),
            'amount' => $price,
            'staff_share' => $staffShare,
            'owner_share' => $ownerShare,
            'entry_date' => optional($appointment->starts_at)->toDateString() ?: Carbon::now('Europe/Istanbul')->toDateString(),
            'notes' => $payLabel.$split,
        ]);
    }

    private function ledgerPayload(SalonCrmLedgerEntry $e): array
    {
        return [
            'id' => $e->id,
            'type' => $e->type,
            'category' => $e->category,
            'title' => $e->title,
            'amount' => (float) $e->amount,
            'entry_date' => optional($e->entry_date)->toDateString(),
            'notes' => $e->notes,
            'staff_id' => $e->staff_id,
            'staff_name' => $e->staff?->name,
            'payment_method' => $e->payment_method,
            'staff_share' => $e->staff_share !== null ? (float) $e->staff_share : null,
            'owner_share' => $e->owner_share !== null ? (float) $e->owner_share : null,
            'source' => 'manual',
            'created_at' => optional($e->created_at)->toIso8601String(),
        ];
    }

    /**
     * @return array{0:array<int,array<string,mixed>>,1:float}
     */
    private function marketplaceExpenses(SalonCrmSalon $salon, Carbon $day): array
    {
        if (!$salon->user_id || !Schema::hasTable('orders')) {
            return [[], 0.0];
        }

        try {
            $orders = Order::query()
                ->where('user_id', $salon->user_id)
                ->where('payment_status', 1)
                ->whereDate('created_at', $day->toDateString())
                ->get();
        } catch (\Throwable $e) {
            return [[], 0.0];
        }

        $entries = [];
        $sum = 0.0;
        foreach ($orders as $order) {
            $amount = (float) ($order->total_amount ?: $order->amount_real_currency ?: 0);
            if ($amount <= 0) {
                continue;
            }
            $sum += $amount;
            $orderNo = $order->order_id ?: $order->id;
            $entries[] = [
                'id' => -((int) $order->id),
                'type' => 'expense',
                'category' => 'seyfibaba',
                'title' => 'Seyfibaba alışveriş #'.$orderNo,
                'amount' => round($amount, 2),
                'entry_date' => $day->toDateString(),
                'notes' => 'Pazaryeri siparişi gider olarak işlendi',
                'staff_id' => null,
                'staff_name' => null,
                'source' => 'marketplace',
                'created_at' => optional($order->created_at)->toIso8601String(),
            ];
        }

        return [$entries, round($sum, 2)];
    }

    private function salonProfilePayload(SalonCrmSalon $salon): array
    {
        return [
            'name' => $salon->name,
            'type' => $salon->type,
            'phone' => $salon->phone,
            'logo_image' => $salon->logo_image,
            'cover_image' => $salon->cover_image,
            'profile_text' => $salon->profile_text,
            'show_profile_to_customers' => (bool) $salon->show_profile_to_customers,
            'open_hour' => Schema::hasColumn('salon_crm_salons', 'open_hour')
                ? (int) ($salon->open_hour ?? 9)
                : 9,
            'close_hour' => Schema::hasColumn('salon_crm_salons', 'close_hour')
                ? (int) ($salon->close_hour ?? 21)
                : 21,
            'join_code' => $salon->join_code,
        ];
    }

    private function ensureSalonJoinCode(SalonCrmSalon $salon): void
    {
        if (!Schema::hasColumn('salon_crm_salons', 'join_code') || !empty($salon->join_code)) {
            return;
        }

        do {
            $code = Str::upper(Str::random(6));
        } while (SalonCrmSalon::query()->where('join_code', $code)->exists());

        $salon->join_code = $code;
        $salon->save();
    }

    private function appointmentOverlaps(
        SalonCrmSalon $salon,
        Carbon $startsAt,
        int $duration,
        ?int $staffId,
        ?int $ignoreId = null,
        bool $asBlock = false
    ): bool {
        // Duvar saati string — DB ile aynı formatta karşılaştır
        $startStr = $startsAt->copy()->timezone('Europe/Istanbul')->format('Y-m-d H:i:s');
        $endStr = $startsAt->copy()->timezone('Europe/Istanbul')->addMinutes($duration)->format('Y-m-d H:i:s');

        $scope = function ($q) use ($staffId) {
            // Aynı kişi/koltuk: personel kendi + bloklar; patron kendi (staff_id null) + bloklar
            if ($staffId) {
                $q->where(function ($q2) use ($staffId) {
                    $q2->where('staff_id', $staffId)->orWhere('is_block', true);
                });
            } else {
                $q->where(function ($q2) {
                    $q2->whereNull('staff_id')->orWhere('is_block', true);
                });
            }
        };

        // Kapalı saat / blok: süre aralığıyla çakışan her şey yasak
        if ($asBlock) {
            $query = SalonCrmAppointment::query()
                ->where('salon_id', $salon->id)
                ->whereIn('status', ['pending', 'scheduled', 'completed'])
                ->where('starts_at', '<', $endStr)
                ->whereRaw('DATE_ADD(starts_at, INTERVAL duration_minutes MINUTE) > ?', [$startStr]);
            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }
            $scope($query);

            return $query->exists();
        }

        // Normal randevu: aynı başlangıç dakikası çakışır; ayrıca kapalı saat aralığına denk gelirse engellenir
        $exact = SalonCrmAppointment::query()
            ->where('salon_id', $salon->id)
            ->whereIn('status', ['pending', 'scheduled', 'completed'])
            ->where('is_block', false)
            ->where('starts_at', $startStr);
        if ($ignoreId) {
            $exact->where('id', '!=', $ignoreId);
        }
        $scope($exact);
        if ($exact->exists()) {
            return true;
        }

        $blockHit = SalonCrmAppointment::query()
            ->where('salon_id', $salon->id)
            ->where('is_block', true)
            ->whereIn('status', ['pending', 'scheduled', 'completed'])
            ->where('starts_at', '<', $endStr)
            ->whereRaw('DATE_ADD(starts_at, INTERVAL duration_minutes MINUTE) > ?', [$startStr]);
        if ($ignoreId) {
            $blockHit->where('id', '!=', $ignoreId);
        }
        // Bloklar salon geneli sayılır (tüm personel)
        return $blockHit->exists();
    }

    /** Gelen tarihi Avrupa/İstanbul duvar saati olarak çöz. */
    private function parseSalonDateTime(string $value): Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return Carbon::now('Europe/Istanbul');
        }
        if (preg_match('/[zZ]|[+-]\d{2}:?\d{2}$/', $value)) {
            return Carbon::parse($value)->timezone('Europe/Istanbul');
        }
        $normalized = str_replace('T', ' ', $value);
        $normalized = preg_replace('/\.\d+$/', '', $normalized) ?? $normalized;

        return Carbon::parse($normalized, 'Europe/Istanbul');
    }

    /** DB / Carbon değerini offset'siz duvar saati ISO olarak ver (Flutter kaymasın). */
    private function appointmentDaySummary(int $salonId, Carbon $day, ?int $staffId = null): array
    {
        $start = $day->copy()->startOfDay()->format('Y-m-d H:i:s');
        $end = $day->copy()->endOfDay()->format('Y-m-d H:i:s');
        $now = Carbon::now('Europe/Istanbul')->format('Y-m-d H:i:s');

        $base = SalonCrmAppointment::query()
            ->where('salon_id', $salonId)
            ->where('is_block', false)
            ->when($staffId, fn ($q) => $q->where('staff_id', $staffId))
            ->whereBetween('starts_at', [$start, $end]);

        $completed = (clone $base)->where('status', 'completed')->count();
        $noShow = (clone $base)->where('status', 'no_show')->count();
        $cancelled = (clone $base)->where('status', 'cancelled')->count();
        $scheduled = (clone $base)->whereIn('status', ['scheduled', 'pending'])->count();
        $needsOutcome = (clone $base)
            ->whereIn('status', ['scheduled', 'pending'])
            ->whereRaw('DATE_ADD(starts_at, INTERVAL duration_minutes MINUTE) < ?', [$now])
            ->count();

        $message = null;
        if ($needsOutcome > 0) {
            $message = $needsOutcome === 1
                ? '1 randevu için sonuç girilmedi (geldi / gelmedi).'
                : "{$needsOutcome} randevu için sonuç girilmedi (geldi / gelmedi).";
        }

        return [
            'completed' => $completed,
            'no_show' => $noShow,
            'cancelled' => $cancelled,
            'scheduled' => $scheduled,
            'needs_outcome' => $needsOutcome,
            'message' => $message,
        ];
    }

    private function formatSalonDateTime($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof Carbon) {
            return $value->copy()->timezone('Europe/Istanbul')->format('Y-m-d H:i:s');
        }
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->timezone('Europe/Istanbul')->format('Y-m-d H:i:s');
        }

        return $this->parseSalonDateTime((string) $value)->format('Y-m-d H:i:s');
    }

    private function staffHoursConflict(?SalonCrmStaff $staff, Carbon $startsAt, int $duration): ?string
    {
        if (!$staff || !Schema::hasTable('salon_crm_staff_hours')) {
            return null;
        }

        $weekday = (int) $startsAt->isoWeekday();
        $row = SalonCrmStaffHour::query()
            ->where('staff_id', $staff->id)
            ->where('weekday', $weekday)
            ->first();
        if (!$row) {
            return null;
        }
        if ($row->is_off) {
            return 'Bu gün personelin izin günü.';
        }

        $start = Carbon::parse($startsAt->toDateString().' '.$row->start_time);
        $end = Carbon::parse($startsAt->toDateString().' '.$row->end_time);
        $apptEnd = $startsAt->copy()->addMinutes($duration);
        if ($startsAt->lt($start) || $apptEnd->gt($end)) {
            return 'Seçilen saat personelin çalışma saatleri dışında.';
        }

        return null;
    }

    private function staffHoursPayload(SalonCrmStaff $staff): array
    {
        if (!Schema::hasTable('salon_crm_staff_hours')) {
            return $this->defaultStaffHours();
        }

        $existing = SalonCrmStaffHour::query()
            ->where('staff_id', $staff->id)
            ->get()
            ->keyBy('weekday');
        $out = [];
        for ($day = 1; $day <= 7; $day++) {
            $row = $existing->get($day);
            $out[] = [
                'weekday' => $day,
                'start_time' => $row ? substr((string) $row->start_time, 0, 5) : '09:00',
                'end_time' => $row ? substr((string) $row->end_time, 0, 5) : '21:00',
                'is_off' => $row ? (bool) $row->is_off : false,
            ];
        }

        return $out;
    }

    private function defaultStaffHours(): array
    {
        $out = [];
        for ($day = 1; $day <= 7; $day++) {
            $out[] = [
                'weekday' => $day,
                'start_time' => '09:00',
                'end_time' => '21:00',
                'is_off' => false,
            ];
        }

        return $out;
    }

    private function staffPayload(SalonCrmStaff $staff, bool $withServices = false): array
    {
        $row = [
            'id' => $staff->id,
            'name' => $staff->name,
            'username' => $staff->username,
            'photo' => $staff->photo,
            'show_photo_to_customers' => (bool) $staff->show_photo_to_customers,
            'is_active' => (bool) $staff->is_active,
            'commission_percent' => (float) ($staff->commission_percent ?? 0),
            'pay_type' => $staff->pay_type ?: 'percent',
            'pay_period' => $staff->pay_period ?: 'monthly',
            'salary_amount' => (float) ($staff->salary_amount ?? 0),
            'created_at' => optional($staff->created_at)->toIso8601String(),
        ];
        if ($withServices) {
            $row['services'] = $this->staffServiceRows($staff);
        }

        return $row;
    }

    private function staffServiceRows(SalonCrmStaff $staff): array
    {
        if (!$staff->relationLoaded('staffServices')) {
            $staff->load(['staffServices.service']);
        }

        return $staff->staffServices
            ->filter(fn (SalonCrmStaffService $link) => $link->service)
            ->map(function (SalonCrmStaffService $link) {
                $svc = $link->service;

                return [
                    'id' => $svc->id,
                    'name' => $svc->name,
                    'price' => (float) ($link->price ?? $svc->price ?? 0),
                    'duration_minutes' => (int) ($link->duration_minutes ?? $svc->duration_minutes ?? 30),
                    'is_active' => (bool) $svc->is_active,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{price: float, duration: int}
     */
    private function resolveStaffServicePrice(?SalonCrmStaff $staff, ?SalonCrmService $service): array
    {
        $price = (float) ($service?->price ?? 0);
        $duration = (int) ($service?->duration_minutes ?? 30);
        if (!$staff || !$service) {
            return ['price' => $price, 'duration' => $duration, 'linked' => false];
        }

        $link = SalonCrmStaffService::query()
            ->where('staff_id', $staff->id)
            ->where('service_id', $service->id)
            ->first();
        if ($link) {
            $price = (float) $link->price;
            if ($link->duration_minutes) {
                $duration = (int) $link->duration_minutes;
            }

            return ['price' => $price, 'duration' => $duration, 'linked' => true];
        }

        return ['price' => $price, 'duration' => $duration, 'linked' => false];
    }

    private function staffPayPeriodInfo(SalonCrmSalon $salon, SalonCrmStaff $staff, ?string $periodKey = null): array
    {
        $period = $staff->pay_period ?: 'monthly';
        $now = Carbon::now('Europe/Istanbul');
        $key = $periodKey ?: ($period === 'daily' ? $now->toDateString() : $now->format('Y-m'));
        $suggested = 0.0;

        if (($staff->pay_type ?? 'percent') === 'net') {
            $suggested = (float) ($staff->salary_amount ?? 0);
        } else {
            $query = SalonCrmAppointment::query()
                ->where('salon_id', $salon->id)
                ->where('staff_id', $staff->id)
                ->where('status', 'completed')
                ->where('payment_status', 'paid');
            if ($period === 'daily') {
                $query->whereDate('starts_at', $key);
            } else {
                try {
                    $month = Carbon::createFromFormat('Y-m', substr($key, 0, 7))->startOfMonth();
                } catch (\Throwable $e) {
                    $month = $now->copy()->startOfMonth();
                }
                $query->whereBetween('starts_at', [$month, $month->copy()->endOfMonth()]);
            }
            $suggested = round((float) $query->sum('staff_share'), 2);
        }

        $existing = SalonCrmSalaryPayment::query()
            ->where('salon_id', $salon->id)
            ->where('staff_id', $staff->id)
            ->where('period_key', $key)
            ->whereIn('status', ['pending', 'paid'])
            ->first();

        return [
            'key' => $key,
            'pay_type' => $staff->pay_type ?: 'percent',
            'pay_period' => $period,
            'label' => $period === 'daily' ? $key : $now->translatedFormat('F Y'),
            'suggested_amount' => $suggested,
            'existing_payment' => $existing ? $this->salaryPaymentPayload($existing) : null,
        ];
    }

    private function salaryPaymentPayload(SalonCrmSalaryPayment $p): array
    {
        $status = $p->status;
        $label = 'Bekliyor';
        if ($status === 'paid') {
            $label = 'Maaş ödendi';
        } elseif ($p->owner_confirmed_at && !$p->staff_confirmed_at) {
            $label = 'Patron ödedi · personel onayı bekleniyor';
        } elseif ($p->staff_confirmed_at && !$p->owner_confirmed_at) {
            $label = 'Personel onayladı · patron onayı bekleniyor';
        }

        return [
            'id' => $p->id,
            'staff_id' => $p->staff_id,
            'staff_name' => $p->staff?->name,
            'pay_type' => $p->pay_type,
            'pay_period' => $p->pay_period,
            'period_key' => $p->period_key,
            'suggested_amount' => (float) $p->suggested_amount,
            'amount' => (float) $p->amount,
            'status' => $status,
            'status_label' => $label,
            'owner_confirmed' => (bool) $p->owner_confirmed_at,
            'staff_confirmed' => (bool) $p->staff_confirmed_at,
            'paid_at' => optional($p->paid_at)->toIso8601String(),
            'notes' => $p->notes,
            'created_at' => optional($p->created_at)->toIso8601String(),
        ];
    }

    private function markSalaryPaid(SalonCrmSalon $salon, SalonCrmSalaryPayment $payment): void
    {
        $payment->loadMissing('staff');
        $payment->status = 'paid';
        $payment->paid_at = now();
        $payment->save();

        $periodLabel = $payment->pay_period === 'daily' ? $payment->period_key : $payment->period_key;
        $entry = SalonCrmLedgerEntry::query()->create([
            'salon_id' => $salon->id,
            'staff_id' => $payment->staff_id,
            'type' => 'expense',
            'category' => 'maas',
            'title' => 'Maaş · '.$payment->staff?->name.' · '.$periodLabel,
            'amount' => (float) $payment->amount,
            'entry_date' => Carbon::now('Europe/Istanbul')->toDateString(),
            'notes' => 'Karşılıklı onaylandı',
        ]);
        $payment->ledger_entry_id = $entry->id;
        $payment->save();
    }
}
