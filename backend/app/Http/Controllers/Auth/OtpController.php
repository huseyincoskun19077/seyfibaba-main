<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpVerification;
use App\Models\User;
use App\Services\SmsServiceInterface;
use App\Support\OtpMessageBuilder;
use App\Support\PasswordRules;
use App\Support\PhoneNormalizer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class OtpController extends Controller
{
    public function __construct(protected SmsServiceInterface $smsService)
    {
        $this->middleware('guest:api');
    }

    protected function normalizePhone(string $phone): string
    {
        return PhoneNormalizer::toE164($phone);
    }

    public function send(Request $request)
    {
        $data = $this->validateRequest($request);
        $phone = $this->normalizePhone($data['phone']);
        $purpose = $data['purpose'];
        $email = isset($data['email']) ? trim((string) $data['email']) : null;

        // Kayıt akışında OTP göndermeden önce kullanıcı var mı kontrol et
        if ($purpose === 'register') {
            if (User::where('phone', $phone)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bu telefon numarası ile zaten kayıt var.',
                ], 422);
            }

            if ($email && User::where('email', $email)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bu e-posta adresi ile zaten kayıt var.',
                ], 422);
            }
        }

        $latestOtp = OtpVerification::where('phone', $phone)
            ->where('purpose', $purpose)
            ->latest('id')
            ->first();

        $cooldownSeconds = (int) config('sms.otp.cooldown_seconds', 60);
        if ($latestOtp && $latestOtp->created_at->diffInSeconds(now()) < $cooldownSeconds) {
            return response()->json([
                'success' => false,
                'message' => 'Lütfen yeni kod istemeden önce bekleyin.',
                'retry_after' => $cooldownSeconds - $latestOtp->created_at->diffInSeconds(now()),
            ], 429);
        }

        OtpVerification::where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->delete();

        $otp = OtpVerification::create([
            'phone' => $phone,
            'otp_code' => $this->generateOtpCode(),
            'purpose' => $purpose,
            'attempts' => 0,
            'max_attempts' => (int) config('sms.otp.max_attempts', 3),
            'expires_at' => Carbon::now()->addMinutes((int) config('sms.otp.expire_minutes', 5)),
            'ip_address' => $request->ip(),
        ]);

        $message = OtpMessageBuilder::build($otp->otp_code);

        $smsSent = $this->smsService->send($phone, $message);

        if (!$smsSent) {
            return response()->json([
                'success' => false,
                'message' => 'Doğrulama kodu gönderilemedi. Lütfen daha sonra tekrar deneyin.',
            ], 500);
        }

        $response = [
            'success' => true,
            'message' => 'Doğrulama kodu gönderildi.',
            'expires_in' => (int) config('sms.otp.expire_minutes', 5) * 60,
        ];

        // In local/testing, include OTP in response so developers can test without real SMS
        if (app()->environment('local', 'testing')) {
            $response['otp_code'] = $otp->otp_code;
            $response['message'] = "OTP: {$otp->otp_code} (geliştirici modu — SMS gönderilmedi)";
        }

        return response()->json($response);
    }

    public function verify(Request $request)
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'otp_code' => ['required', 'digits:' . (int) config('sms.otp.length', 6)],
            'purpose' => ['nullable', 'in:register,password_reset,phone_verify'],
        ]);

        $phone = $this->normalizePhone($validated['phone']);
        $purpose = $validated['purpose'] ?? 'register';
        $phoneDigits = PhoneNormalizer::digitsOnly($phone);
        $last10 = strlen($phoneDigits) >= 10 ? substr($phoneDigits, -10) : $phoneDigits;

        $otp = OtpVerification::whereIn('phone', array_values(array_unique(array_filter([
                $phone,
                $phoneDigits,
                '0'.$last10,
                '+90'.$last10,
                '90'.$last10,
            ]))))
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (!$otp) {
            \Log::warning('OTP verify: kayıt bulunamadı', [
                'phone' => $phone,
                'purpose' => $purpose,
                'raw_phone' => $validated['phone'],
            ]);
            return response()->json([
                'success' => false,
                'verified' => false,
                'message' => 'Aktif doğrulama kodu bulunamadı. Lütfen yeni kod isteyin.',
            ], 404);
        }

        if ($otp->isExpired()) {
            return response()->json([
                'success' => false,
                'verified' => false,
                'message' => 'Doğrulama kodunun süresi doldu. Lütfen yeni kod isteyin.',
            ], 422);
        }

        if (!$otp->hasAttemptsRemaining()) {
            return response()->json([
                'success' => false,
                'verified' => false,
                'message' => 'Maksimum deneme sayısına ulaşıldı. Lütfen yeni kod isteyin.',
            ], 429);
        }

        if ($otp->otp_code !== $validated['otp_code']) {
            $otp->increment('attempts');
            $otp->refresh();

            return response()->json([
                'success' => false,
                'verified' => false,
                'message' => 'Doğrulama kodu hatalı.',
                'attempts_left' => max(0, $otp->max_attempts - $otp->attempts),
            ], 422);
        }

        $otp->markVerified();

        $verifiedToken = bin2hex(random_bytes(24));
        $tokenTtlMinutes = $purpose === 'password_reset'
            ? max(15, (int) config('sms.otp.expire_minutes', 5))
            : (int) config('sms.otp.expire_minutes', 5);

        Cache::put(
            'otp_verified_token:' . $verifiedToken,
            [
                'phone' => $phone,
                'purpose' => $purpose,
            ],
            Carbon::now()->addMinutes($tokenTtlMinutes)
        );

        return response()->json([
            'success' => true,
            'verified' => true,
            'token' => $verifiedToken,
        ]);
    }

    public function resend(Request $request)
    {
        return $this->send($request);
    }

    protected function validateRequest(Request $request): array
    {
        $rules = [
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'purpose' => ['nullable', 'in:register,password_reset,phone_verify'],
        ];

        $purpose = $request->input('purpose', 'register');
        if ($purpose === 'register') {
            $rules = array_merge($rules, PasswordRules::registerRules());
        }

        $validated = $request->validate($rules, PasswordRules::messages());
        $validated['purpose'] = $validated['purpose'] ?? 'register';

        return $validated;
    }

    protected function generateOtpCode(): string
    {
        $length = (int) config('sms.otp.length', 6);
        $min = (int) str_pad('1', $length, '0');
        $max = (int) str_repeat('9', $length);

        return (string) random_int($min, $max);
    }
}
