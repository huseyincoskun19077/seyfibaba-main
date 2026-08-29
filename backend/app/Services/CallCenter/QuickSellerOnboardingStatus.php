<?php

namespace App\Services\CallCenter;

use App\Models\OtpVerification;
use App\Models\User;
use App\Models\Vendor;
use App\Support\PhoneNormalizer;

/**
 * Read-only onboarding visibility for call-center / public-web / admin panels.
 * Does not mutate auth or registration flows.
 */
class QuickSellerOnboardingStatus
{
    /**
     * @return array{
     *     applicable: bool,
     *     sms_sent: bool|null,
     *     sms_sent_at: string|null,
     *     email_sent: bool|null,
     *     email_sent_at: string|null,
     *     email_skipped: bool,
     *     logged_in: bool,
     *     logged_in_at: string|null,
     *     password_changed: bool,
     *     summary: string,
     *     summary_badge: string
     * }
     */
    public static function for(Vendor $vendor): array
    {
        if (! $vendor->isQuickOnboardingRegistration()) {
            return self::empty();
        }

        $user = $vendor->relationLoaded('user') ? $vendor->user : $vendor->user()->first();
        $emailSkipped = ! $user || QuickSellerRegistrationService::isPendingEmail($user->email);

        $smsSent = self::nullableBool($vendor->welcome_sms_sent ?? null);
        $emailSent = $emailSkipped
            ? null
            : self::nullableBool($vendor->welcome_email_sent ?? null);

        $otp = self::findFirstLoginOtp($user);
        $loggedIn = $otp !== null && $otp->verified_at !== null;
        $loggedInAt = $loggedIn ? $otp->verified_at?->format('d.m.Y H:i') : null;

        $passwordChanged = $user
            ? ! (bool) ($user->must_change_password ?? false)
            : false;

        [$summary, $badge] = self::summarize($smsSent, $loggedIn, $passwordChanged);

        return [
            'applicable' => true,
            'sms_sent' => $smsSent,
            'sms_sent_at' => $vendor->welcome_sms_sent_at
                ? (is_string($vendor->welcome_sms_sent_at)
                    ? $vendor->welcome_sms_sent_at
                    : $vendor->welcome_sms_sent_at->format('d.m.Y H:i'))
                : null,
            'email_sent' => $emailSent,
            'email_sent_at' => $vendor->welcome_email_sent_at
                ? (is_string($vendor->welcome_email_sent_at)
                    ? $vendor->welcome_email_sent_at
                    : $vendor->welcome_email_sent_at->format('d.m.Y H:i'))
                : null,
            'email_skipped' => $emailSkipped,
            'logged_in' => $loggedIn,
            'logged_in_at' => $loggedInAt,
            'password_changed' => $passwordChanged,
            'can_resend_sms' => ! $passwordChanged,
            'can_edit_phone' => $vendor->isCallCenterRegistration() && ! $passwordChanged,
            'can_edit_registration' => $vendor->isCallCenterRegistration(),
            'summary' => $summary,
            'summary_badge' => $badge,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected static function summarize(?bool $smsSent, bool $loggedIn, bool $passwordChanged): array
    {
        if ($passwordChanged) {
            return ['Şifre oluşturuldu', 'success'];
        }

        if ($loggedIn) {
            return ['Giriş yaptı, şifre oluşturmadı', 'warning'];
        }

        if ($smsSent === false) {
            return ['SMS gitmedi, sisteme girmedi', 'danger'];
        }

        if ($smsSent === true) {
            return ['SMS gitti, sisteme girmedi', 'warning'];
        }

        return ['Sisteme girmedi', 'secondary'];
    }

    protected static function findFirstLoginOtp(?User $user): ?OtpVerification
    {
        if (! $user || empty($user->phone)) {
            return null;
        }

        $phones = self::phoneVariants($user->phone);

        return OtpVerification::query()
            ->whereIn('phone', $phones)
            ->where('purpose', 'seller_first_login')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return list<string>
     */
    protected static function phoneVariants(string $phone): array
    {
        $variants = [$phone];

        try {
            $e164 = PhoneNormalizer::toE164($phone);
            $digits = PhoneNormalizer::digitsOnly($e164);
            $variants[] = $e164;
            $variants[] = $digits;
            if (strlen($digits) >= 10) {
                $last10 = substr($digits, -10);
                $variants[] = '0'.$last10;
                $variants[] = '+90'.$last10;
                $variants[] = '90'.$last10;
            }
        } catch (\Throwable) {
            // Keep original phone only.
        }

        return array_values(array_unique(array_filter($variants)));
    }

    protected static function nullableBool(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        return (bool) $value;
    }

    /**
     * @return array{
     *     applicable: bool,
     *     sms_sent: null,
     *     sms_sent_at: null,
     *     email_sent: null,
     *     email_sent_at: null,
     *     email_skipped: bool,
     *     logged_in: bool,
     *     logged_in_at: null,
     *     password_changed: bool,
     *     summary: string,
     *     summary_badge: string
     * }
     */
    protected static function empty(): array
    {
        return [
            'applicable' => false,
            'sms_sent' => null,
            'sms_sent_at' => null,
            'email_sent' => null,
            'email_sent_at' => null,
            'email_skipped' => false,
            'logged_in' => false,
            'logged_in_at' => null,
            'password_changed' => false,
            'can_resend_sms' => false,
            'can_edit_phone' => false,
            'can_edit_registration' => false,
            'summary' => '',
            'summary_badge' => 'secondary',
        ];
    }
}
