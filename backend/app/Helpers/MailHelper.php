<?php

namespace App\Helpers;

use App\Models\EmailConfiguration;
use Illuminate\Support\Facades\Schema;

class MailHelper
{
    public static function setMailConfig(): void
    {
        if (app()->environment('testing')) {
            config(['mail.default' => 'array']);
            config(['mail.from.address' => 'no-reply@shopo.local']);

            return;
        }

        if (app()->environment('local') && ! self::hasAdminSmtpSettings()) {
            config(['mail.default' => 'array']);
            config(['mail.from.address' => 'no-reply@shopo.local']);

            return;
        }

        if (! Schema::hasTable('email_configurations')) {
            self::applyArrayMailer();

            return;
        }

        $emailSetting = EmailConfiguration::query()->first();
        if (! self::isValidSmtpSetting($emailSetting)) {
            self::applyArrayMailer();

            return;
        }

        self::applySmtpMailer($emailSetting);
    }

    public static function isSmtpConfigured(): bool
    {
        if (app()->environment('testing')) {
            return false;
        }

        if (! Schema::hasTable('email_configurations')) {
            return false;
        }

        return self::isValidSmtpSetting(EmailConfiguration::query()->first());
    }

    protected static function hasAdminSmtpSettings(): bool
    {
        if (! Schema::hasTable('email_configurations')) {
            return false;
        }

        return self::isValidSmtpSetting(EmailConfiguration::query()->first());
    }

    protected static function isValidSmtpSetting(?EmailConfiguration $emailSetting): bool
    {
        if (! $emailSetting) {
            return false;
        }

        return trim((string) $emailSetting->mail_host) !== ''
            && trim((string) $emailSetting->smtp_username) !== ''
            && trim((string) ($emailSetting->smtp_password ?? '')) !== '';
    }

    protected static function applyArrayMailer(): void
    {
        config(['mail.default' => 'array']);
        config(['mail.from.address' => 'no-reply@shopo.local']);
    }

    protected static function applySmtpMailer(EmailConfiguration $emailSetting): void
    {
        $port = (int) ($emailSetting->mail_port ?: 587);
        $encryption = trim((string) ($emailSetting->mail_encryption ?: 'tls'));

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp' => [
                'transport' => 'smtp',
                'host' => trim((string) $emailSetting->mail_host),
                'port' => $port > 0 ? $port : 587,
                'encryption' => $encryption !== '' ? $encryption : null,
                'username' => trim((string) $emailSetting->smtp_username),
                'password' => (string) $emailSetting->smtp_password,
                'timeout' => null,
            ],
            'mail.from.address' => trim((string) ($emailSetting->email ?: $emailSetting->smtp_username)),
            'mail.from.name' => config('mail.from.name', 'Seyfibaba'),
        ]);

        if (app()->bound('mail.manager')) {
            app('mail.manager')->purge('smtp');
        }
    }
}
