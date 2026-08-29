<?php

namespace App\Services\CallCenter;

use App\Models\OtpVerification;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Models\Vendor;
use App\Services\SmsServiceInterface;
use App\Support\PhoneNormalizer;
use App\Support\SellerLoginUrl;
use App\Support\SmsTemplateRenderer;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CallCenterSellerReminderService
{
    public const SLUG_LOGIN = 'seller_reminder_login';
    public const SLUG_KYC = 'seller_reminder_kyc';
    public const SLUG_PRODUCT = 'seller_reminder_product';

    public function __construct(
        protected SmsServiceInterface $smsService
    ) {
    }

    /**
     * @return list<array{slug: string, label: string, hint: string}>
     */
    public function availableReminders(Vendor $vendor): array
    {
        if (! $vendor->isCallCenterRegistration()) {
            return [];
        }

        $user = $vendor->relationLoaded('user') ? $vendor->user : $vendor->user()->first();
        if (! $user) {
            return [];
        }

        $onboarding = QuickSellerOnboardingStatus::for($vendor);
        $kycStatus = (string) ($vendor->kyc_status ?? 'not_submitted');
        $productCount = (int) ($vendor->products_count ?? $vendor->products()->count());
        $options = [];

        if (! ($onboarding['password_changed'] ?? false)) {
            $options[] = [
                'slug' => self::SLUG_LOGIN,
                'label' => 'Giriş / şifre hatırlat',
                'hint' => $this->loginReminderHint($onboarding),
            ];
        }

        if (($onboarding['password_changed'] ?? false) && $kycStatus !== 'approved') {
            $options[] = [
                'slug' => self::SLUG_KYC,
                'label' => 'KYC hatırlat',
                'hint' => 'Vergi levhası henüz onaylanmadı.',
            ];
        }

        if (($onboarding['password_changed'] ?? false) && $kycStatus === 'approved' && $productCount === 0) {
            $options[] = [
                'slug' => self::SLUG_PRODUCT,
                'label' => 'Ürün yükleme hatırlat',
                'hint' => 'Onaylı satıcı; henüz ürün yok.',
            ];
        }

        return $options;
    }

    public function send(Vendor $vendor, string $slug): bool
    {
        if (! $vendor->isCallCenterRegistration()) {
            throw new RuntimeException('Bu işlem sadece çağrı merkezi kayıtları için geçerlidir.');
        }

        $available = collect($this->availableReminders($vendor))->pluck('slug')->all();
        if (! in_array($slug, $available, true)) {
            throw new RuntimeException('Bu satıcı için seçilen hatırlatma SMS\'i uygun değil.');
        }

        $user = $vendor->user;
        if (! $user || empty($user->phone)) {
            throw new RuntimeException('Satıcı telefon numarası bulunamadı.');
        }

        $template = SmsTemplate::query()->where('slug', $slug)->first();
        if (! $template || trim((string) $template->description) === '') {
            throw new RuntimeException('SMS şablonu bulunamadı. Yönetici panelinden şablonu kontrol edin.');
        }

        $message = SmsTemplateRenderer::render(
            (string) $template->description,
            $this->buildVariables($vendor, $user, $slug)
        );

        if ($message === '') {
            throw new RuntimeException('SMS metni boş. Şablonu kontrol edin.');
        }

        $phone = PhoneNormalizer::toE164((string) $user->phone);

        try {
            $sent = $this->smsService->sendTransactional($phone, $message);
        } catch (\Throwable $exception) {
            Log::error('Call center reminder SMS failed', [
                'vendor_id' => $vendor->id,
                'slug' => $slug,
                'error' => $exception->getMessage(),
            ]);

            throw new RuntimeException('SMS gönderilemedi. Teknik ekibe bildirin.');
        }

        if (! $sent) {
            throw new RuntimeException('SMS gönderilemedi. Teknik ekibe bildirin.');
        }

        return true;
    }

    /**
     * @return array<string, string>
     */
    protected function buildVariables(Vendor $vendor, User $user, string $slug): array
    {
        $phoneDigits = PhoneNormalizer::digitsOnly((string) $user->phone);
        $loginPhone = strlen($phoneDigits) >= 10 ? substr($phoneDigits, -10) : $phoneDigits;

        $variables = [
            'contact_name' => trim((string) $user->name) ?: 'Satici',
            'shop_name' => trim((string) $vendor->shop_name) ?: 'Magaza',
            'login_url' => SellerLoginUrl::publicDisplay(),
            'login_phone' => $loginPhone,
            'password' => '',
        ];

        if ($slug === self::SLUG_LOGIN) {
            $variables['password'] = $this->resolveFirstLoginPassword($user) ?? 'cagri merkezinden isteyin';
        }

        return $variables;
    }

    protected function resolveFirstLoginPassword(User $user): ?string
    {
        if ((bool) ($user->must_change_password ?? false)) {
            $phones = $this->phoneVariants((string) $user->phone);
            $otp = OtpVerification::query()
                ->whereIn('phone', $phones)
                ->where('purpose', 'seller_first_login')
                ->whereNull('verified_at')
                ->orderByDesc('id')
                ->first();

            if ($otp) {
                return $otp->otp_code;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    protected function phoneVariants(string $phone): array
    {
        $digits = PhoneNormalizer::digitsOnly($phone);
        $last10 = strlen($digits) >= 10 ? substr($digits, -10) : $digits;

        return array_values(array_unique(array_filter([
            $phone,
            PhoneNormalizer::toE164($phone),
            $digits,
            $last10,
            '0'.$last10,
            '+90'.$last10,
            '90'.$last10,
        ])));
    }

    /**
     * @param  array<string, mixed>  $onboarding
     */
    protected function loginReminderHint(array $onboarding): string
    {
        if (($onboarding['sms_sent'] ?? null) === false) {
            return 'Hoş geldin SMS\'i gitmemiş görünüyor.';
        }

        if (! ($onboarding['logged_in'] ?? false)) {
            return 'Henüz sisteme giriş yapılmamış.';
        }

        return 'Giriş yapılmış; yeni şifre oluşturulmamış.';
    }
}
