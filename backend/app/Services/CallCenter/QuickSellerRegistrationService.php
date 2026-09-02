<?php

namespace App\Services\CallCenter;

use App\Helpers\MailHelper;
use App\Mail\CallCenterSellerWelcomeMail;
use App\Models\Admin;
use App\Models\City;
use App\Models\CountryState;
use App\Models\OtpVerification;
use App\Models\User;
use App\Models\Vendor;
use App\Services\SmsServiceInterface;
use App\Support\OtpMessageBuilder;
use App\Support\PhoneNormalizer;
use App\Support\SellerLoginUrl;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class QuickSellerRegistrationService
{
    public const PENDING_EMAIL_DOMAIN = 'pending.seyfibaba.local';

    public const LOGIN_CHANNEL_SMS = 'sms';

    public const LOGIN_CHANNEL_EMAIL = 'email';

    public const LOGIN_CHANNEL_BOTH = 'both';

    public function __construct(
        protected SmsServiceInterface $smsService
    ) {
    }

    public static function isPendingEmail(?string $email): bool
    {
        $email = strtolower(trim((string) $email));

        return $email !== '' && str_ends_with($email, '@'.self::PENDING_EMAIL_DOMAIN);
    }

    /**
     * @param  array{
     *     shop_name: string,
     *     contact_name: string,
     *     phone?: string|null,
     *     email?: string|null,
     *     login_channel?: string,
     *     state_id?: int|null,
     *     city_id?: int|null,
     *     category_id?: int|null,
     *     note?: string|null,
     * }  $data
     */
    public function register(Admin $agent, array $data): QuickRegistrationResult
    {
        if (! $agent->isCallCenterAgent() && ! $agent->isSuperAdmin()) {
            throw new RuntimeException('Bu işlem için yetkiniz yok.');
        }

        return $this->registerWithSource($data, 'call_center', $agent->id);
    }

    /**
     * Web sitesinden herkese açık hızlı satıcı kaydı.
     *
     * @param  array{
     *     shop_name: string,
     *     contact_name: string,
     *     phone: string,
     *     email?: string|null,
     *     state_id?: int|null,
     *     city_id?: int|null,
     *     category_id?: int|null,
     *     note?: string|null,
     * }  $data
     */
    public function registerPublic(array $data): QuickRegistrationResult
    {
        return $this->registerWithSource($data, 'public_web', null);
    }

    /**
     * @param  array{
     *     shop_name: string,
     *     contact_name: string,
     *     phone?: string|null,
     *     email?: string|null,
     *     login_channel?: string,
     *     state_id?: int|null,
     *     city_id?: int|null,
     *     category_id?: int|null,
     *     note?: string|null,
     * }  $data
     */
    protected function registerWithSource(array $data, string $registrationSource, ?int $registeredByAdminId): QuickRegistrationResult
    {
        $loginChannel = $this->resolveLoginChannel($data, $registrationSource);
        $phone = null;
        $phoneDigits = '';

        if ($this->loginChannelUsesSms($loginChannel)) {
            $phone = PhoneNormalizer::toE164((string) ($data['phone'] ?? ''));
            $phoneDigits = PhoneNormalizer::digitsOnly($phone);

            if ($phoneDigits === '' || strlen($phoneDigits) < 10) {
                throw new RuntimeException('Geçerli bir telefon numarası girin.');
            }
        } elseif (trim((string) ($data['phone'] ?? '')) !== '') {
            $phone = PhoneNormalizer::toE164((string) $data['phone']);
            $phoneDigits = PhoneNormalizer::digitsOnly($phone);

            if ($phoneDigits !== '' && strlen($phoneDigits) < 10) {
                throw new RuntimeException('Geçerli bir telefon numarası girin.');
            }
        }

        $existingUser = $this->findExistingUserForRegistration($loginChannel, $phone, $phoneDigits, $data, null);

        if ($existingUser?->seller) {
            throw new RuntimeException($loginChannel === self::LOGIN_CHANNEL_EMAIL
                ? 'Bu e-posta adresine bağlı bir satıcı hesabı zaten var.'
                : 'Bu telefon numarasına bağlı bir satıcı hesabı zaten var.');
        }

        if ($this->loginChannelUsesEmail($loginChannel)) {
            $emailInput = strtolower(trim((string) ($data['email'] ?? '')));
            if ($emailInput === '') {
                throw new RuntimeException('E-posta adresi zorunludur.');
            }

            $email = $this->assertValidEmail($emailInput, $existingUser?->id);
            $hasRealEmail = true;
        } else {
            [$email, $hasRealEmail] = $this->resolveEmail($data['email'] ?? null, $phoneDigits ?: '0', $existingUser);
        }

        $address = $this->buildAddress($data['state_id'] ?? null, $data['city_id'] ?? null);
        $shopName = trim($data['shop_name']);
        $contactName = trim($data['contact_name']);
        $loginUrl = SellerLoginUrl::public();
        $otpIdentifier = $this->firstLoginOtpIdentifier($phone, $email, $loginChannel);

        return DB::transaction(function () use (
            $data,
            $registrationSource,
            $registeredByAdminId,
            $existingUser,
            $phone,
            $email,
            $hasRealEmail,
            $address,
            $shopName,
            $contactName,
            $loginUrl,
            $loginChannel,
            $otpIdentifier
        ) {
            $wasExistingUser = $existingUser !== null;

            if ($wasExistingUser) {
                $user = $existingUser;
                $user->name = $contactName;
                $user->email = $email;
                $user->phone = $phone;
                $user->password = Hash::make(Str::random(32));
                $user->status = 1;
                $user->email_verified = $hasRealEmail ? 1 : 0;
                $user->agree_policy = 1;
                if (Schema::hasColumn('users', 'must_change_password')) {
                    $user->must_change_password = 1;
                }
                $user->save();
            } else {
                $user = new User();
                $user->name = $contactName;
                $user->email = $email;
                $user->phone = $phone;
                $user->password = Hash::make(Str::random(32));
                $user->status = 1;
                $user->email_verified = $hasRealEmail ? 1 : 0;
                $user->agree_policy = 1;
                $user->verify_token = null;
                if (Schema::hasColumn('users', 'must_change_password')) {
                    $user->must_change_password = 1;
                }
                $user->save();
            }

            $vendor = new Vendor();
            $vendor->user_id = $user->id;
            $vendor->shop_name = $shopName;
            $vendor->slug = $this->uniqueShopSlug($shopName);
            $vendor->email = $hasRealEmail ? $email : null;
            $vendor->phone = $phone;
            $vendor->address = $address !== '' ? $address : 'Adres bilgisi sonra tamamlanacak';
            $vendor->greeting_msg = 'Hoş geldiniz — '.$shopName;
            $vendor->open_at = '09:00';
            $vendor->closed_at = '18:00';
            $vendor->seo_title = $shopName;
            $vendor->seo_description = $shopName;
            $vendor->status = 1;
            $vendor->registration_source = $registrationSource;
            $vendor->registered_by_admin_id = $registeredByAdminId;
            $vendor->quick_registration_note = isset($data['note']) ? trim((string) $data['note']) : null;
            $categoryIds = $this->resolveCategoryIds($data);
            $vendor->primary_category_id = $categoryIds[0] ?? null;
            if (Schema::hasColumn('vendors', 'registration_category_ids')) {
                $vendor->registration_category_ids = $categoryIds !== [] ? $categoryIds : null;
            }

            if (Schema::hasColumn('vendors', 'kyc_status')) {
                $vendor->kyc_status = 'not_submitted';
            }

            $vendor->save();

            $otpCode = $this->createFirstLoginOtp($otpIdentifier);
            $smsSent = false;
            $emailSent = false;

            if ($this->loginChannelUsesSms($loginChannel) && $phone) {
                $smsSent = $this->sendWelcomeSms($phone, $email, $otpCode, $loginUrl);
            }

            if ($this->loginChannelUsesEmail($loginChannel) && $hasRealEmail) {
                $emailSent = $this->sendWelcomeEmail(
                    $contactName,
                    $shopName,
                    $email,
                    $loginUrl,
                    $otpCode,
                    $loginChannel,
                    $phone
                );
            }

            $this->persistWelcomeDeliveryStatus(
                $vendor,
                $smsSent,
                $this->loginChannelUsesEmail($loginChannel) ? $emailSent : null
            );

            return new QuickRegistrationResult(
                user: $user,
                vendor: $vendor,
                otpCode: $otpCode,
                smsSent: $smsSent,
                emailSent: $emailSent,
                wasExistingUser: $wasExistingUser,
            );
        });
    }

    /**
     * Yeniden hoş geldin SMS'i gönder (aynı tek girişlik şifre korunur).
     * Satıcı yeni şifresini oluşturduysa engellenir.
     */
    public function resendFirstLoginSms(Vendor $vendor): bool
    {
        if (! $vendor->isQuickOnboardingRegistration()) {
            throw new RuntimeException('Bu işlem sadece ilk giriş SMS kaydı olan satıcılar için geçerlidir.');
        }

        $user = $vendor->user;
        if (! $user) {
            throw new RuntimeException('Satıcı kullanıcısı bulunamadı.');
        }

        if (! (bool) ($user->must_change_password ?? false)) {
            throw new RuntimeException('Satıcı yeni şifresini oluşturmuş. Tek kullanımlık SMS gönderilemez.');
        }

        $phone = PhoneNormalizer::toE164((string) $user->phone);
        $phoneDigits = PhoneNormalizer::digitsOnly($phone);

        if ($phoneDigits === '' || strlen($phoneDigits) < 10) {
            throw new RuntimeException('Geçerli bir telefon numarası bulunamadı.');
        }

        $otp = self::findActiveFirstLoginOtp($user);
        if (! $otp) {
            throw new RuntimeException('Tek kullanımlık giriş kodu bulunamadı.');
        }

        $otpCode = $otp->otp_code;
        $smsSent = $this->sendWelcomeSms($phone, (string) $user->email, $otpCode, SellerLoginUrl::public());
        $this->persistWelcomeDeliveryStatus($vendor, $smsSent, null);

        if (! $smsSent) {
            throw new RuntimeException('SMS gönderilemedi. Teknik ekibe bildirin.');
        }

        return true;
    }

    /**
     * Yeniden hoş geldin e-postası gönder (aynı tek girişlik şifre korunur).
     */
    public function resendFirstLoginEmail(Vendor $vendor): bool
    {
        if (! $vendor->isQuickOnboardingRegistration()) {
            throw new RuntimeException('Bu işlem sadece ilk giriş kaydı olan satıcılar için geçerlidir.');
        }

        $user = $vendor->user;
        if (! $user) {
            throw new RuntimeException('Satıcı kullanıcısı bulunamadı.');
        }

        if (! (bool) ($user->must_change_password ?? false)) {
            throw new RuntimeException('Satıcı yeni şifresini oluşturmuş. Tek kullanımlık e-posta gönderilemez.');
        }

        if (self::isPendingEmail($user->email)) {
            throw new RuntimeException('Satıcıda geçerli bir e-posta adresi bulunamadı.');
        }

        $otp = self::findActiveFirstLoginOtp($user);
        if (! $otp) {
            throw new RuntimeException('Tek kullanımlık giriş kodu bulunamadı.');
        }

        $emailSent = $this->sendWelcomeEmail(
            (string) $user->name,
            (string) $vendor->shop_name,
            (string) $user->email,
            SellerLoginUrl::public(),
            $otp->otp_code,
            $user->phone ? self::LOGIN_CHANNEL_BOTH : self::LOGIN_CHANNEL_EMAIL,
            $user->phone ? (string) $user->phone : null,
        );
        $this->persistWelcomeDeliveryStatus($vendor, false, $emailSent);

        if (! $emailSent) {
            throw new RuntimeException('E-posta gönderilemedi. Teknik ekibe bildirin.');
        }

        return true;
    }

    /**
     * Çağrı merkezi kaydında firma / yetkili / telefon bilgilerini düzenle.
     * Telefon yalnızca satıcı yeni şifresini oluşturmadıysa değiştirilebilir.
     *
     * @param  array{
     *     shop_name: string,
     *     contact_name: string,
     *     phone?: string|null,
     *     send_sms?: bool,
     * }  $data
     */
    public function updateRegistration(Vendor $vendor, array $data): bool
    {
        $user = $this->assertEditableCallCenterRegistration($vendor);

        $shopName = trim((string) ($data['shop_name'] ?? ''));
        $contactName = trim((string) ($data['contact_name'] ?? ''));

        if ($shopName === '') {
            throw new RuntimeException('Firma / dükkan adı zorunludur.');
        }

        if ($contactName === '') {
            throw new RuntimeException('Yetkili adı soyadı zorunludur.');
        }

        $canEditPhone = (bool) ($user->must_change_password ?? false);
        $phoneInput = trim((string) ($data['phone'] ?? ''));
        $sendSms = (bool) ($data['send_sms'] ?? false);

        if ($phoneInput !== '' && ! $canEditPhone) {
            $currentDigits = PhoneNormalizer::digitsOnly((string) $user->phone);
            $newDigits = PhoneNormalizer::digitsOnly(PhoneNormalizer::toE164($phoneInput));
            if ($newDigits !== '' && $newDigits !== $currentDigits) {
                throw new RuntimeException('Satıcı yeni şifresini oluşturmuş. Telefon numarası düzenlenemez.');
            }
        }

        return DB::transaction(function () use ($vendor, $user, $shopName, $contactName, $phoneInput, $canEditPhone, $sendSms) {
            $user->name = $contactName;
            $user->save();

            $vendor->shop_name = $shopName;
            $vendor->seo_title = $shopName;
            $vendor->seo_description = $shopName;
            if (is_string($vendor->greeting_msg) && str_contains($vendor->greeting_msg, '—')) {
                $vendor->greeting_msg = 'Hoş geldiniz — '.$shopName;
            }
            $vendor->save();

            if ($canEditPhone && $phoneInput !== '') {
                $this->applyRegistrationPhoneChange($vendor, $user, $phoneInput, $sendSms);
            }

            return true;
        });
    }

    /**
     * @deprecated updateRegistration kullanın
     */
    public function updateRegistrationPhone(Vendor $vendor, string $newPhoneRaw, bool $sendSms = true): bool
    {
        return $this->updateRegistration($vendor, [
            'shop_name' => (string) $vendor->shop_name,
            'contact_name' => (string) ($vendor->user?->name ?? ''),
            'phone' => $newPhoneRaw,
            'send_sms' => $sendSms,
        ]);
    }

    protected function applyRegistrationPhoneChange(
        Vendor $vendor,
        User $user,
        string $newPhoneRaw,
        bool $sendSms
    ): void {
        $newPhone = PhoneNormalizer::toE164($newPhoneRaw);
        $newPhoneDigits = PhoneNormalizer::digitsOnly($newPhone);

        if ($newPhoneDigits === '' || strlen($newPhoneDigits) < 10) {
            throw new RuntimeException('Geçerli bir telefon numarası girin.');
        }

        $currentPhone = PhoneNormalizer::toE164((string) $user->phone);
        $phoneChanged = PhoneNormalizer::digitsOnly($currentPhone) !== $newPhoneDigits;

        if ($phoneChanged) {
            $existingUser = User::query()
                ->where(function ($query) use ($newPhone, $newPhoneDigits) {
                    $query->where('phone', $newPhone)
                        ->orWhere('phone', $newPhoneDigits)
                        ->orWhere('phone', '+90'.$newPhoneDigits)
                        ->orWhere('phone', '0'.substr($newPhoneDigits, -10));
                })
                ->where('id', '!=', $user->id)
                ->first();

            if ($existingUser?->seller) {
                throw new RuntimeException('Bu telefon numarasına bağlı bir satıcı hesabı zaten var.');
            }
        }

        $otp = QuickSellerRegistrationService::findActiveFirstLoginOtp($user);

        if ($phoneChanged) {
            $user->phone = $newPhone;
            $user->save();

            $vendor->phone = $newPhone;
            $vendor->save();

            if (self::isPendingEmail($user->email)) {
                $user->email = $this->generatePendingEmail(substr($newPhoneDigits, -10));
                $user->save();
            }

            if ($otp) {
                $otp->phone = $newPhone;
                $otp->save();
            }
        }

        if (! $sendSms) {
            return;
        }

        $otpCode = $otp?->otp_code ?? $this->getActiveFirstLoginOtpCode($newPhone);
        $smsSent = $this->sendWelcomeSms($newPhone, (string) $user->email, $otpCode, SellerLoginUrl::public());
        $this->persistWelcomeDeliveryStatus($vendor, $smsSent, null);

        if (! $smsSent) {
            throw new RuntimeException('SMS gönderilemedi. Teknik ekibe bildirin.');
        }
    }

    protected function assertEditableCallCenterRegistration(Vendor $vendor): User
    {
        if (! $vendor->isCallCenterRegistration()) {
            throw new RuntimeException('Bu işlem sadece çağrı merkezi kayıtları için geçerlidir.');
        }

        $user = $vendor->user;
        if (! $user) {
            throw new RuntimeException('Satıcı kullanıcısı bulunamadı.');
        }

        return $user;
    }

    protected function deleteFirstLoginOtpsForPhone(string $phone): void
    {
        $variants = $this->phoneVariants($phone);

        OtpVerification::query()
            ->whereIn('phone', $variants)
            ->where('purpose', 'seller_first_login')
            ->delete();
    }

    protected function sendWelcomeEmail(
        string $contactName,
        string $shopName,
        string $email,
        string $loginUrl,
        string $otpCode,
        string $loginChannel,
        ?string $phone = null,
    ): bool {
        try {
            MailHelper::setMailConfig();

            if (! app()->runningUnitTests() && ! MailHelper::isSmtpConfigured()) {
                Log::warning('Call center quick registration email skipped: SMTP not configured in admin', [
                    'email' => $email,
                ]);

                return false;
            }

            $phoneUsername = $phone ? $this->loginUsernameFromPhone($phone) : null;

            $mailer = app()->runningUnitTests() ? Mail::mailer() : Mail::mailer('smtp');
            $mailer->to($email)->send(new CallCenterSellerWelcomeMail(
                contactName: $contactName,
                shopName: $shopName,
                email: $email,
                loginUrl: $loginUrl,
                otpCode: $otpCode,
                loginChannel: $loginChannel,
                phoneUsername: $phoneUsername,
            ));

            return true;
        } catch (\Throwable $exception) {
            Log::error('Call center quick registration email failed', [
                'email' => $email,
                'error' => $exception->getMessage(),
            ]);

            if (app()->runningUnitTests()) {
                throw $exception;
            }

            return false;
        }
    }

    protected function sendWelcomeSms(string $phone, string $email, string $otpCode, string $loginUrl): bool
    {
        $loginUsername = $this->loginUsernameFromPhone($phone);
        $message = OtpMessageBuilder::buildCallCenterWelcome($loginUsername, $otpCode);

        try {
            return $this->smsService->sendTransactional($phone, $message);
        } catch (\Throwable $exception) {
            Log::error('Call center quick registration SMS failed', [
                'phone' => $phone,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    protected function getActiveFirstLoginOtpCode(string $identifier): string
    {
        $existing = $this->findFirstLoginOtpForIdentifier($identifier);

        if ($existing && $existing->verified_at === null) {
            return $existing->otp_code;
        }

        return $this->createFirstLoginOtp($identifier);
    }

    protected function findFirstLoginOtpForIdentifier(string $identifier): ?OtpVerification
    {
        $keys = str_starts_with($identifier, 'e:')
            ? [$identifier]
            : $this->phoneVariants($identifier);

        return OtpVerification::query()
            ->whereIn('phone', $keys)
            ->where('purpose', 'seller_first_login')
            ->whereNull('verified_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @deprecated use findFirstLoginOtpForIdentifier
     */
    protected function findFirstLoginOtpForPhone(string $phone): ?OtpVerification
    {
        return $this->findFirstLoginOtpForIdentifier($phone);
    }

    public static function findActiveFirstLoginOtp(User $user): ?OtpVerification
    {
        $keys = self::firstLoginOtpLookupKeys($user);

        if ($keys === []) {
            return null;
        }

        return OtpVerification::query()
            ->whereIn('phone', $keys)
            ->where('purpose', 'seller_first_login')
            ->whereNull('verified_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return list<string>
     */
    public static function firstLoginOtpLookupKeys(User $user): array
    {
        $keys = [];

        if ($user->phone) {
            $service = app(self::class);
            $keys = array_merge($keys, $service->phoneVariants((string) $user->phone));
        }

        if ($user->email && ! self::isPendingEmail($user->email)) {
            $keys[] = self::emailOtpIdentifier($user->email);
        }

        return array_values(array_unique(array_filter($keys)));
    }

    public static function emailOtpIdentifier(string $email): string
    {
        $email = strtolower(trim($email));

        return 'e:'.substr(hash('sha256', $email), 0, 17);
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

    protected function loginUsernameFromPhone(string $phone): string
    {
        $digits = PhoneNormalizer::digitsOnly($phone);

        return strlen($digits) >= 10 ? substr($digits, -10) : $digits;
    }

    /**
     * Persist SMS/email delivery flags for admin & call-center visibility.
     * Null emailSent means email was skipped (pending placeholder).
     */
    protected function persistWelcomeDeliveryStatus(Vendor $vendor, bool $smsSent, ?bool $emailSent): void
    {
        $dirty = false;

        if (Schema::hasColumn('vendors', 'welcome_sms_sent')) {
            $vendor->welcome_sms_sent = $smsSent;
            $dirty = true;
        }

        if (Schema::hasColumn('vendors', 'welcome_sms_sent_at')) {
            $vendor->welcome_sms_sent_at = $smsSent ? now() : null;
            $dirty = true;
        }

        if ($emailSent !== null) {
            if (Schema::hasColumn('vendors', 'welcome_email_sent')) {
                $vendor->welcome_email_sent = $emailSent;
                $dirty = true;
            }

            if (Schema::hasColumn('vendors', 'welcome_email_sent_at')) {
                $vendor->welcome_email_sent_at = $emailSent ? now() : null;
                $dirty = true;
            }
        }

        if ($dirty) {
            $vendor->save();
        }
    }

    protected function createFirstLoginOtp(string $identifier): string
    {
        OtpVerification::query()
            ->where('phone', $identifier)
            ->where('purpose', 'seller_first_login')
            ->whereNull('verified_at')
            ->delete();

        $otpCode = $this->generateOtpCode();

        OtpVerification::create([
            'phone' => $identifier,
            'otp_code' => $otpCode,
            'purpose' => 'seller_first_login',
            'attempts' => 0,
            // Giriş yapılana kadar geçerli (normal OTP 5 dk kuralı burada uygulanmaz)
            'max_attempts' => 50,
            'expires_at' => Carbon::now()->addYears(100),
        ]);

        return $otpCode;
    }

    protected function resolveLoginChannel(array $data, string $registrationSource): string
    {
        if ($registrationSource === 'public_web') {
            return self::LOGIN_CHANNEL_SMS;
        }

        $channel = strtolower(trim((string) ($data['login_channel'] ?? self::LOGIN_CHANNEL_SMS)));

        if (! in_array($channel, [self::LOGIN_CHANNEL_SMS, self::LOGIN_CHANNEL_EMAIL, self::LOGIN_CHANNEL_BOTH], true)) {
            throw new RuntimeException('Geçersiz giriş kanalı seçimi.');
        }

        return $channel;
    }

    protected function loginChannelUsesSms(string $loginChannel): bool
    {
        return in_array($loginChannel, [self::LOGIN_CHANNEL_SMS, self::LOGIN_CHANNEL_BOTH], true);
    }

    protected function loginChannelUsesEmail(string $loginChannel): bool
    {
        return in_array($loginChannel, [self::LOGIN_CHANNEL_EMAIL, self::LOGIN_CHANNEL_BOTH], true);
    }

    protected function firstLoginOtpIdentifier(?string $phone, string $email, string $loginChannel): string
    {
        if ($this->loginChannelUsesSms($loginChannel) && $phone) {
            return PhoneNormalizer::toE164($phone);
        }

        if ($this->loginChannelUsesEmail($loginChannel) && ! self::isPendingEmail($email)) {
            return self::emailOtpIdentifier($email);
        }

        throw new RuntimeException('Giriş bilgileri oluşturulamadı.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function findExistingUserForRegistration(
        string $loginChannel,
        ?string $phone,
        string $phoneDigits,
        array $data,
        ?int $ignoreUserId
    ): ?User {
        if ($loginChannel === self::LOGIN_CHANNEL_EMAIL) {
            $email = strtolower(trim((string) ($data['email'] ?? '')));
            if ($email === '') {
                return null;
            }

            $query = User::query()->where('email', $email);
            if ($ignoreUserId !== null) {
                $query->where('id', '!=', $ignoreUserId);
            }

            return $query->first();
        }

        if ($phone === null || $phoneDigits === '') {
            return null;
        }

        $query = User::query()
            ->where(function ($q) use ($phone, $phoneDigits) {
                $q->where('phone', $phone)
                    ->orWhere('phone', $phoneDigits)
                    ->orWhere('phone', '+90'.$phoneDigits)
                    ->orWhere('phone', '0'.substr($phoneDigits, -10));
            });

        if ($ignoreUserId !== null) {
            $query->where('id', '!=', $ignoreUserId);
        }

        return $query->first();
    }

    protected function generateOtpCode(): string
    {
        $length = (int) config('sms.otp.length', 6);
        $min = (int) str_pad('1', $length, '0');
        $max = (int) str_repeat('9', $length);

        return (string) random_int($min, $max);
    }

    /**
     * @return array{0: string, 1: bool} [email, hasRealEmail]
     */
    protected function resolveEmail(?string $email, string $phoneDigits, ?User $existingUser): array
    {
        $email = strtolower(trim((string) $email));

        if ($email === '') {
            if ($existingUser && $existingUser->email && ! self::isPendingEmail($existingUser->email)) {
                return [$existingUser->email, true];
            }

            return [$this->generatePendingEmail($phoneDigits), false];
        }

        return [$this->assertValidEmail($email, $existingUser?->id), true];
    }

    protected function generatePendingEmail(string $phoneDigits): string
    {
        $local = 'satici.'.preg_replace('/\D+/', '', $phoneDigits);
        $email = $local.'@'.self::PENDING_EMAIL_DOMAIN;
        $counter = 1;

        while (User::query()->where('email', $email)->exists()) {
            $email = $local.'.'.$counter.'@'.self::PENDING_EMAIL_DOMAIN;
            $counter++;
        }

        return $email;
    }

    protected function assertValidEmail(string $email, ?int $existingUserId = null): string
    {
        $email = strtolower(trim($email));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Geçerli bir e-posta adresi girin.');
        }

        if (self::isPendingEmail($email)) {
            throw new RuntimeException('Bu e-posta adresi kullanılamaz.');
        }

        $userQuery = User::query()->where('email', $email);
        if ($existingUserId !== null) {
            $userQuery->where('id', '!=', $existingUserId);
        }

        if ($userQuery->exists()) {
            throw new RuntimeException('Bu e-posta adresi zaten kayıtlı.');
        }

        if (Vendor::query()->where('email', $email)->exists()) {
            throw new RuntimeException('Bu e-posta adresi başka bir satıcıda kayıtlı.');
        }

        return $email;
    }

    protected function uniqueShopSlug(string $shopName): string
    {
        $baseSlug = Str::slug($shopName);
        if ($baseSlug === '') {
            $baseSlug = 'magaza';
        }

        $slug = $baseSlug;
        $counter = 1;

        while (Vendor::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * @return list<int>
     */
    protected function resolveCategoryIds(array $data): array
    {
        $ids = [];

        if (! empty($data['category_ids']) && is_array($data['category_ids'])) {
            $ids = array_map(static fn ($id) => (int) $id, $data['category_ids']);
        } elseif (! empty($data['category_id'])) {
            $ids = [(int) $data['category_id']];
        }

        $ids = array_values(array_unique(array_filter($ids, static fn ($id) => $id > 0)));

        return $ids;
    }

    protected function buildAddress(?int $stateId, ?int $cityId): string
    {
        $parts = [];

        if ($cityId) {
            $city = City::find($cityId);
            if ($city) {
                $parts[] = $city->name;
            }
        }

        if ($stateId) {
            $state = CountryState::find($stateId);
            if ($state) {
                $parts[] = $state->name;
            }
        }

        if ($parts !== []) {
            return implode(' / ', $parts);
        }

        return '';
    }
}
