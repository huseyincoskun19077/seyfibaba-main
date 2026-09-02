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

    /**
     * Admin SMTP ayarlarında eksik veya hatalı bir durum varsa Türkçe açıklama döner.
     */
    public static function smtpConfigurationIssue(): ?string
    {
        if (app()->environment('testing')) {
            return null;
        }

        if (! Schema::hasTable('email_configurations')) {
            return 'E-posta ayarları veritabanında bulunamadı. Admin → Email Configuration bölümünü kontrol edin.';
        }

        $emailSetting = EmailConfiguration::query()->first();
        if (! $emailSetting) {
            return 'Admin panelde e-posta (SMTP) ayarları kayıtlı değil. Email Configuration sayfasından kaydedin.';
        }

        if (trim((string) $emailSetting->mail_host) === '') {
            return 'SMTP sunucu adresi (Mail Host) boş. Google Workspace için: smtp.gmail.com';
        }

        if (trim((string) $emailSetting->smtp_username) === '') {
            return 'SMTP kullanıcı adı boş. Genelde gönderen adresinizle aynı olmalı (ör. info@seyfibaba.com).';
        }

        if (trim((string) ($emailSetting->smtp_password ?? '')) === '') {
            return 'SMTP şifresi boş. Google Workspace için normal hesap şifresi değil, uygulama şifresi gerekir.';
        }

        if (trim((string) ($emailSetting->email ?? '')) === '') {
            return 'Gönderen e-posta adresi boş. info@seyfibaba.com gibi geçerli bir adres girin.';
        }

        $port = (int) ($emailSetting->mail_port ?: 0);
        $encryption = strtolower(trim((string) ($emailSetting->mail_encryption ?? '')));

        if ($port === 465 && $encryption === 'tls') {
            return 'Port 465 için şifreleme SSL olmalı (TLS değil).';
        }

        if ($port === 587 && $encryption === 'ssl') {
            return 'Port 587 için şifreleme TLS olmalı (SSL değil).';
        }

        return null;
    }

    public static function humanizeMailException(\Throwable $exception): string
    {
        $message = strtolower($exception->getMessage());

        if (
            str_contains($message, '535')
            || str_contains($message, 'authentication')
            || str_contains($message, 'authenticate')
            || str_contains($message, 'username and password not accepted')
            || str_contains($message, 'invalid credentials')
        ) {
            return 'SMTP kullanıcı adı veya şifre reddedildi. Google Workspace için uygulama şifresi kullandığınızdan emin olun.';
        }

        if (
            str_contains($message, 'connection could not be established')
            || str_contains($message, 'connection refused')
            || str_contains($message, 'getaddrinfo')
            || str_contains($message, 'php_network_getaddresses')
        ) {
            return 'SMTP sunucusuna bağlanılamadı. Mail Host ve port bilgisini kontrol edin (Google: smtp.gmail.com, port 587).';
        }

        if (str_contains($message, 'timed out') || str_contains($message, 'timeout')) {
            return 'SMTP sunucusu yanıt vermedi (zaman aşımı). Sunucu firewall veya port engeli olabilir.';
        }

        if (
            str_contains($message, 'certificate')
            || str_contains($message, 'ssl')
            || str_contains($message, 'starttls')
            || str_contains($message, 'tls')
        ) {
            return 'SMTP TLS/SSL ayarı hatalı. Port 587 → TLS, port 465 → SSL kullanın.';
        }

        if (str_contains($message, 'recipient') || str_contains($message, 'mailbox')) {
            return 'Alıcı e-posta adresi geçersiz veya reddedildi.';
        }

        return 'E-posta sunucusu hata döndü: '.$exception->getMessage();
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
