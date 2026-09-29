<?php

namespace App\Support;

class OtpMessageBuilder
{
    public static function build(string $otpCode): string
    {
        return sprintf(
            'Kuaför Tedarik giris kodunuz: %s. Gecerlilik suresi: %d dk.',
            $otpCode,
            (int) config('sms.otp.expire_minutes', 5)
        );
    }

    /** Hızlı satıcı kaydı: giriş yapılana kadar geçerli, süre metni yok. */
    public static function buildFirstLogin(string $otpCode): string
    {
        return sprintf('kuafortedarik.com satıcı giriş kodunuz:%s.', $otpCode);
    }

    /** Çağrı merkezi kaydı: kullanıcı adı (telefon) + tek girişlik şifre. */
    public static function buildCallCenterWelcome(string $loginPhoneDigits, string $password): string
    {
        return implode("\n", [
            'Kuaför Tedarik satici paneli.',
            'Kullanici adiniz: '.$loginPhoneDigits,
            'Giris: '.SellerLoginUrl::publicDisplay(),
            'Sifrenizi kimseyle paylasmayiniz.',
            'Tek kullanimlik sifreniz: '.$password,
        ]);
    }
}
