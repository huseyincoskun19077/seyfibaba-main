<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NetgsmService implements SmsServiceInterface
{
    public function send(string $phone, string $message): bool
    {
        $credentials = $this->resolveCredentials();
        $usercode = $credentials['usercode'];
        $password = $credentials['password'];
        $msgheader = $credentials['msgheader'];
        $endpoint = config('sms.providers.netgsm.endpoint') ?? 'https://api.netgsm.com.tr/sms/send/get';
        $transactionalEndpoint = config('sms.providers.netgsm.transactional_endpoint')
            ?? 'https://api.netgsm.com.tr/sms/send/get';

        if (str_contains($endpoint, '/otp') && (str_contains($message, "\n") || mb_strlen($message) > 70)) {
            Log::warning('Netgsm OTP message too long, falling back to transactional endpoint.', [
                'original_endpoint' => $endpoint,
                'message_length' => mb_strlen($message),
            ]);
            $endpoint = $transactionalEndpoint;
        }

        if (!$usercode || !$password || !$msgheader) {
            Log::warning('Netgsm credentials are missing; using local fallback.', [
                'phone' => $phone,
                'environment' => app()->environment(),
            ]);

            return app()->environment('local', 'testing');
        }

        $phone = $this->normalizePhoneForApi($phone);

        try {
            Log::info('Netgsm SMS sending', [
                'phone' => $phone,
                'msgheader' => $msgheader,
                'endpoint' => $endpoint,
            ]);

            $isRestOtpEndpoint = str_contains($endpoint, '/sms/rest/v2/otp');
            $isRestSendEndpoint = str_contains($endpoint, '/sms/rest/v2/send');

            if ($isRestOtpEndpoint || $isRestSendEndpoint) {
                return $this->sendRestV2($endpoint, $usercode, $password, $msgheader, $phone, $message, $isRestOtpEndpoint);
            }

            return $this->sendLegacyGet($endpoint, $usercode, $password, $msgheader, $phone, $message);
        } catch (\Exception $e) {
            Log::error('Netgsm Exception', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    public function sendTransactional(string $phone, string $message): bool
    {
        $credentials = $this->resolveCredentials();
        $usercode = $credentials['usercode'];
        $password = $credentials['password'];
        $msgheader = $credentials['msgheader'];
        $endpoint = config('sms.providers.netgsm.transactional_endpoint')
            ?? 'https://api.netgsm.com.tr/sms/rest/v2/send';

        if (!$usercode || !$password || !$msgheader) {
            Log::warning('Netgsm credentials are missing; using local fallback.', [
                'phone' => $phone,
                'environment' => app()->environment(),
            ]);

            return app()->environment('local', 'testing');
        }

        $phone = $this->normalizePhoneForApi($phone);

        try {
            Log::info('Netgsm transactional SMS sending', [
                'phone' => $phone,
                'msgheader' => $msgheader,
                'endpoint' => $endpoint,
            ]);

            if (str_contains($endpoint, '/sms/rest/v2/send')) {
                return $this->sendRestV2($endpoint, $usercode, $password, $msgheader, $phone, $message, false);
            }

            return $this->sendLegacyGet($endpoint, $usercode, $password, $msgheader, $phone, $message);
        } catch (\Exception $e) {
            Log::error('Netgsm transactional Exception', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * .env öncelikli; boşsa admin panelindeki settings tablosundan okur.
     */
    protected function resolveCredentials(): array
    {
        $usercode = trim((string) (config('sms.providers.netgsm.usercode') ?? ''));
        $password = trim((string) (config('sms.providers.netgsm.password') ?? ''));
        $msgheader = trim((string) (config('sms.providers.netgsm.msgheader') ?? ''));

        if ($usercode !== '' && $password !== '' && $msgheader !== '') {
            return compact('usercode', 'password', 'msgheader');
        }

        $setting = Setting::query()->first();
        if (! $setting) {
            return ['usercode' => '', 'password' => '', 'msgheader' => ''];
        }

        return [
            'usercode' => trim((string) ($setting->netgsm_usercode ?? '')),
            'password' => trim((string) ($setting->netgsm_password ?? '')),
            'msgheader' => trim((string) ($setting->netgsm_msgheader ?? 'SEYFIBABA')),
        ];
    }

    protected function sendRestV2(
        string $endpoint,
        string $usercode,
        string $password,
        string $msgheader,
        string $phone,
        string $message,
        bool $otpFormat
    ): bool {
        if ($otpFormat) {
            $otpNo = $this->formatLocalGsmNumber($phone);

            $response = Http::timeout(15)
                ->acceptJson()
                ->withBasicAuth($usercode, $password)
                ->post($endpoint, [
                    'msgheader' => $msgheader,
                    'msg' => $message,
                    'no' => $otpNo,
                ]);
        } else {
            $response = Http::timeout(15)
                ->acceptJson()
                ->withBasicAuth($usercode, $password)
                ->post($endpoint, [
                    'msgheader' => $msgheader,
                    'encoding' => 'TR',
                    'messages' => [
                        [
                            'msg' => $message,
                            'no' => $this->formatLocalGsmNumber($phone),
                        ],
                    ],
                ]);
        }

        $json = $response->json();
        $code = (string) data_get($json, 'code', '');

        Log::info('Netgsm REST response', [
            'phone' => $phone,
            'response_json' => $json,
            'http_status' => $response->status(),
            'otp_format' => $otpFormat,
        ]);

        if ($response->ok() && $code === '00') {
            return true;
        }

        Log::warning('Netgsm REST error', [
            'phone' => $phone,
            'code' => $code,
            'description' => data_get($json, 'description'),
            'http_status' => $response->status(),
        ]);

        return false;
    }

    protected function sendLegacyGet(
        string $endpoint,
        string $usercode,
        string $password,
        string $msgheader,
        string $phone,
        string $message
    ): bool {
        $response = Http::timeout(15)->get($endpoint, [
            'usercode' => $usercode,
            'password' => $password,
            'gsmno' => $phone,
            'message' => $message,
            'msgheader' => $msgheader,
        ]);

        $body = trim($response->body());

        Log::info('Netgsm API response', [
            'phone' => $phone,
            'response_body' => $body,
            'http_status' => $response->status(),
        ]);

        if (in_array($body, ['00', '01', '02']) || str_starts_with($body, '00')) {
            return true;
        }

        Log::warning('Netgsm API Error', [
            'phone' => $phone,
            'response' => $body,
            'status' => $response->status(),
        ]);

        return false;
    }

    protected function normalizePhoneForApi(string $phone): string
    {
        $hasPlus = str_starts_with(trim($phone), '+');
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if ($hasPlus) {
            // +491723846068 → 491723846068 (already international)
        } elseif (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        } elseif (str_starts_with($phone, '0')) {
            $phone = '90'.substr($phone, 1);
        } elseif (! str_starts_with($phone, '90') && strlen($phone) === 10) {
            $phone = '90'.$phone;
        }

        return $phone;
    }

    protected function formatLocalGsmNumber(string $phone): string
    {
        if (str_starts_with($phone, '90') && strlen($phone) === 12) {
            return substr($phone, 2);
        }

        return $phone;
    }
}
