<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SalonCrmCustomer;
use App\Models\SalonCrmSalon;
use App\Models\SalonCrmService;
use App\Models\SalonCrmStaff;
use App\Services\SalonCrmAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SalonCrmAuthController extends Controller
{
    public function __construct(private SalonCrmAccessService $accessService)
    {
    }

    public function patronRegister(Request $request)
    {
        $data = $request->validate([
            'salon_name' => ['required', 'string', 'max:160'],
            'owner_name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:80', 'alpha_dash'],
            'password' => ['required', 'string', 'min:4', 'max:80'],
            'type' => ['nullable', 'in:kuafor,guzellik'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        $username = Str::lower(trim($data['username']));
        if (SalonCrmSalon::query()->where('owner_username', $username)->exists()) {
            return response()->json(['message' => 'Bu kullanıcı adı alınmış.'], 422);
        }

        $token = $this->newToken();
        $salon = SalonCrmSalon::query()->create([
            'user_id' => null,
            'name' => trim($data['salon_name']),
            'owner_name' => trim($data['owner_name']),
            'owner_username' => $username,
            'owner_password' => Hash::make($data['password']),
            'api_token' => $token,
            'type' => $data['type'] ?? 'kuafor',
            'phone' => $data['phone'] ?? null,
            'trial_ends_at' => now()->addDays(30),
            'threshold_amount' => 10000,
            'join_code' => $this->newJoinCode(),
        ]);

        $this->seedDefaultServices($salon);

        $this->sendSalonCrmActivationMail($salon);

        return response()->json(array_merge(
            $this->accessService->snapshot($salon->fresh()),
            [
                'message' => 'Salon CRM kaydı oluşturuldu. 30 gün deneme başladı.',
                'role' => 'owner',
                'token' => $token,
            ]
        ), 201);
    }

    /** Alışveriş hesabı (JWT) ile patron — salon var mı / CRM token */
    public function patronBootstrap(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['message' => 'Giriş gerekli.'], 401);
        }

        if (!Schema::hasTable('salon_crm_salons')) {
            return response()->json([
                'has_salon' => false,
                'message' => 'Salon kaydı bulunamadı.',
            ]);
        }

        try {
            $salon = SalonCrmSalon::query()->where('user_id', $user->id)->first();
            if (!$salon) {
                return response()->json([
                    'has_salon' => false,
                    'message' => 'Salon kaydı bulunamadı.',
                ]);
            }

            $token = $this->newToken();
            $salon->api_token = $token;
            $salon->save();

            return response()->json(array_merge(
                $this->accessService->snapshot($salon, $user),
                [
                    'has_salon' => true,
                    'role' => 'owner',
                    'token' => $token,
                ]
            ));
        } catch (\Throwable $e) {
            Log::error('Salon CRM patronBootstrap', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'has_salon' => false,
                'message' => 'Salon paneli şu an açılamadı. Biraz sonra tekrar deneyin.',
            ]);
        }
    }

    /** Alışveriş hesabına bağlı yeni salon kaydı (ayrı CRM şifresi yok) */
    public function patronRegisterLinked(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['message' => 'Giriş gerekli.'], 401);
        }

        if (!Schema::hasTable('salon_crm_salons')) {
            return response()->json(['message' => 'Salon paneli henüz hazır değil.'], 400);
        }

        if (SalonCrmSalon::query()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Bu hesaba zaten bir salon bağlı.'], 400);
        }

        $data = $request->validate([
            'salon_name' => ['required', 'string', 'max:160'],
            'type' => ['nullable', 'in:kuafor,guzellik'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        try {
            $username = $this->uniqueOwnerUsername($data['salon_name'], $user->id);
            $token = $this->newToken();

            $salon = SalonCrmSalon::query()->create([
                'user_id' => $user->id,
                'name' => trim($data['salon_name']),
                'owner_name' => trim($user->name ?? 'Patron'),
                'owner_username' => $username,
                'owner_password' => null,
                'api_token' => $token,
                'type' => $data['type'] ?? 'kuafor',
                'phone' => $data['phone'] ?? null,
                'trial_ends_at' => now()->addDays(30),
                'threshold_amount' => 10000,
                'join_code' => $this->newJoinCode(),
            ]);

            $this->seedDefaultServices($salon);

            $this->sendSalonCrmActivationMail($salon, $user);

            return response()->json(array_merge(
                $this->accessService->snapshot($salon->fresh(), $user),
                [
                    'message' => 'Salonunuz hazır. 30 gün deneme başladı.',
                    'has_salon' => true,
                    'role' => 'owner',
                    'token' => $token,
                ]
            ), 201);
        } catch (\Throwable $e) {
            Log::error('Salon CRM patronRegisterLinked', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Salon kaydı oluşturulamadı. Biraz sonra tekrar deneyin.',
            ], 400);
        }
    }

    public function patronSalonSummary(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['message' => 'Giriş gerekli.'], 401);
        }

        $salon = SalonCrmSalon::query()
            ->where('user_id', $user->id)
            ->first(['id', 'name', 'owner_username', 'type']);

        return response()->json([
            'has_salon' => (bool) $salon,
            'salon' => $salon ? [
                'id' => $salon->id,
                'name' => $salon->name,
                'owner_username' => $salon->owner_username,
                'type' => $salon->type,
            ] : null,
        ]);
    }

    public function patronLogin(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $salon = SalonCrmSalon::query()
            ->where('owner_username', Str::lower(trim($data['username'])))
            ->first();

        if (!$salon || !$salon->owner_password || !Hash::check($data['password'], $salon->owner_password)) {
            return response()->json(['message' => 'Kullanıcı adı veya şifre hatalı.'], 401);
        }

        $token = $this->newToken();
        $salon->api_token = $token;
        $salon->save();

        return response()->json(array_merge(
            $this->accessService->snapshot($salon),
            [
                'message' => 'Giriş başarılı.',
                'role' => 'owner',
                'token' => $token,
            ]
        ));
    }

    public function staffLogin(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            // Eski uygulamalar için opsiyonel; yeni girişte gerekmez
            'salon_username' => ['nullable', 'string'],
        ]);

        $username = trim($data['username']);
        $password = $data['password'];

        $candidates = SalonCrmStaff::query()
            ->where('username', $username)
            ->where('is_active', true)
            ->when(!empty($data['salon_username']), function ($q) use ($data) {
                $salonId = SalonCrmSalon::query()
                    ->where('owner_username', Str::lower(trim($data['salon_username'])))
                    ->value('id');
                if ($salonId) {
                    $q->where('salon_id', $salonId);
                }
            })
            ->orderBy('id')
            ->get();

        $staff = null;
        foreach ($candidates as $candidate) {
            if (Hash::check($password, $candidate->password)) {
                $staff = $candidate;
                break;
            }
        }

        if (!$staff) {
            return response()->json([
                'message' => 'Kullanıcı adı veya şifre hatalı.',
            ], 401);
        }

        $salon = SalonCrmSalon::query()->find($staff->salon_id);
        if (!$salon) {
            return response()->json(['message' => 'Salon bulunamadı.'], 404);
        }

        $token = $this->newToken();
        $staff->api_token = $token;
        $staff->save();

        $snapshot = $this->accessService->snapshot($salon);

        return response()->json(array_merge($snapshot, [
            'message' => 'Personel girişi başarılı.',
            'role' => 'staff',
            'token' => $token,
            'staff' => [
                'id' => $staff->id,
                'name' => $staff->name,
                'username' => $staff->username,
            ],
            'salon' => [
                'id' => $salon->id,
                'name' => $salon->name,
                'owner_username' => $salon->owner_username,
            ],
        ]));
    }

    public function customerJoinPreview(string $code)
    {
        if (!Schema::hasTable('salon_crm_salons')) {
            return response()->json(['message' => 'Salon bulunamadı.'], 404);
        }

        $salon = $this->findSalonByJoinInput($code);
        if (!$salon) {
            return response()->json(['message' => 'Berber kodu geçersiz.'], 404);
        }

        $this->ensureJoinCode($salon);
        $showProfile = (bool) $salon->show_profile_to_customers;

        return response()->json([
            'salon' => [
                'id' => $salon->id,
                'name' => $salon->name,
                'type' => $salon->type,
                'join_code' => $salon->join_code,
                'phone' => $showProfile ? $salon->phone : null,
                'logo_image' => $showProfile ? $salon->logo_image : null,
                'profile_text' => $showProfile ? $salon->profile_text : null,
            ],
        ]);
    }

    public function customerRegister(Request $request)
    {
        $data = $request->validate([
            'join_code' => ['nullable', 'string', 'max:32'],
            'salon_username' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:32'],
            'password' => ['required', 'string', 'min:4', 'max:80'],
        ]);

        $salon = $this->resolveCustomerSalon($data);
        if (!$salon) {
            return response()->json(['message' => 'Berber kodu geçersiz.'], 404);
        }

        $phone = trim($data['phone']);
        $existing = SalonCrmCustomer::query()
            ->where('salon_id', $salon->id)
            ->where('phone', $phone)
            ->first();

        if ($existing && $existing->password) {
            return response()->json(['message' => 'Bu telefon ile kayıt zaten var. Giriş yapın.'], 422);
        }

        $token = $this->newToken();

        if ($existing) {
            $existing->name = trim($data['name']);
            $existing->password = Hash::make($data['password']);
            $existing->api_token = $token;
            $existing->save();
            $customer = $existing;
        } else {
            $customer = SalonCrmCustomer::query()->create([
                'salon_id' => $salon->id,
                'name' => trim($data['name']),
                'phone' => $phone,
                'password' => Hash::make($data['password']),
                'api_token' => $token,
            ]);
        }

        return response()->json([
            'message' => 'Müşteri kaydı oluşturuldu.',
            'role' => 'customer',
            'token' => $token,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
            ],
            'salon' => [
                'id' => $salon->id,
                'name' => $salon->name,
                'owner_username' => $salon->owner_username,
                'join_code' => $salon->join_code,
            ],
        ], 201);
    }

    public function customerLogin(Request $request)
    {
        $data = $request->validate([
            'join_code' => ['nullable', 'string', 'max:32'],
            'salon_username' => ['nullable', 'string'],
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $salon = $this->resolveCustomerSalon($data);
        if (!$salon) {
            return response()->json(['message' => 'Berber kodu geçersiz.'], 404);
        }

        $customer = SalonCrmCustomer::query()
            ->where('salon_id', $salon->id)
            ->where('phone', trim($data['phone']))
            ->first();

        if (!$customer || !$customer->password || !Hash::check($data['password'], $customer->password)) {
            return response()->json(['message' => 'Telefon veya şifre hatalı.'], 401);
        }

        $token = $this->newToken();
        $customer->api_token = $token;
        $customer->save();

        return response()->json([
            'message' => 'Müşteri girişi başarılı.',
            'role' => 'customer',
            'token' => $token,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
            ],
            'salon' => [
                'id' => $salon->id,
                'name' => $salon->name,
                'owner_username' => $salon->owner_username,
                'join_code' => $salon->join_code,
            ],
        ]);
    }

    private function resolveCustomerSalon(array $data): ?SalonCrmSalon
    {
        $joinCode = trim((string) ($data['join_code'] ?? ''));
        if ($joinCode !== '') {
            return $this->findSalonByJoinInput($joinCode);
        }

        $username = trim((string) ($data['salon_username'] ?? ''));
        if ($username === '') {
            return null;
        }

        return SalonCrmSalon::query()
            ->where('owner_username', Str::lower($username))
            ->first();
    }

    private function findSalonByJoinInput(string $raw): ?SalonCrmSalon
    {
        $code = $this->normalizeJoinCode($raw);
        if ($code === '') {
            return null;
        }

        $salon = SalonCrmSalon::query()
            ->where('join_code', $code)
            ->first();

        if ($salon) {
            return $salon;
        }

        return SalonCrmSalon::query()
            ->where('owner_username', Str::lower($code))
            ->first();
    }

    private function normalizeJoinCode(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        if (preg_match('/SEYCRM:([A-Z0-9]{6,8})/i', $raw, $m)) {
            return Str::upper($m[1]);
        }

        if (preg_match('/join[_-]?code=([A-Z0-9]{6,8})/i', $raw, $m)) {
            return Str::upper($m[1]);
        }

        return Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $raw) ?? '');
    }

    private function ensureJoinCode(SalonCrmSalon $salon): string
    {
        if (!empty($salon->join_code)) {
            return $salon->join_code;
        }

        if (!Schema::hasColumn('salon_crm_salons', 'join_code')) {
            return '';
        }

        $salon->join_code = $this->newJoinCode();
        $salon->save();

        return $salon->join_code;
    }

    private function newJoinCode(): string
    {
        if (!Schema::hasColumn('salon_crm_salons', 'join_code')) {
            return '';
        }

        do {
            $code = Str::upper(Str::random(6));
        } while (
            $code !== '' &&
            SalonCrmSalon::query()->where('join_code', $code)->exists()
        );

        return $code;
    }

    private function newToken(): string
    {
        return Str::random(60);
    }

    private function uniqueOwnerUsername(string $salonName, int $userId): string
    {
        $base = Str::slug($salonName, '');
        if ($base === '') {
            $base = 'salon';
        }
        $base = Str::lower(Str::limit($base, 40, ''));
        $candidate = $base;
        $i = 0;
        while (SalonCrmSalon::query()->where('owner_username', $candidate)->exists()) {
            $i++;
            $candidate = $base.$userId.($i > 1 ? (string) $i : '');
        }

        return $candidate;
    }

    private function seedDefaultServices(SalonCrmSalon $salon): void
    {
        if (!Schema::hasTable('salon_crm_services')) {
            return;
        }

        $defaults = $salon->type === 'guzellik'
            ? [
                ['name' => 'Manikür', 'duration_minutes' => 45, 'price' => 0],
                ['name' => 'Pedikür', 'duration_minutes' => 45, 'price' => 0],
                ['name' => 'Cilt bakımı', 'duration_minutes' => 60, 'price' => 0],
            ]
            : [
                ['name' => 'Saç kesimi', 'duration_minutes' => 30, 'price' => 0],
                ['name' => 'Sakal tıraşı', 'duration_minutes' => 20, 'price' => 0],
                ['name' => 'Yıkama + Fön', 'duration_minutes' => 40, 'price' => 0],
            ];

        foreach ($defaults as $row) {
            SalonCrmService::query()->create([
                'salon_id' => $salon->id,
                'name' => $row['name'],
                'duration_minutes' => $row['duration_minutes'],
                'price' => $row['price'],
                'is_active' => true,
            ]);
        }
    }

    private function sendSalonCrmActivationMail(SalonCrmSalon $salon, ?\App\Models\User $user = null): void
    {
        try {
            $email = $user?->email;
            if (!$email) return;

            \App\Helpers\MailHelper::setMailConfig();
            $content = "Salon CRM hesabınız oluşturuldu!\n\nSalon: {$salon->name}\nKullanıcı Adı: {$salon->owner_username}\n\n30 gün ücretsiz deneme başladı. Randevu, personel, kasa ve müşteri takibini mobil uygulamadan yapabilirsiniz.";
            \Mail::to($email)->send(new \App\Mail\SalonCrmActivatedMail($content));
        } catch (\Throwable $e) {
            \Log::warning('Salon CRM activation mail failed', ['salon_id' => $salon->id, 'error' => $e->getMessage()]);
        }
    }
}
