<?php

namespace App\Support;

class OtpMessageBuilder
{
    public static function build(string $otpCode): string
    {
        return sprintf(
            'Seyfibaba giris kodunuz: %s. Gecerlilik suresi: %d dk.',
            $otpCode,
            (int) config('sms.otp.expire_minutes', 5)
        );
    }

    /** Hızlı satıcı kaydı: giriş yapılana kadar geçerli, süre metni yok. */
    public static function buildFirstLogin(string $otpCode): string
    {
        return sprintf('seyfibaba.com satıcı giriş kodunuz:%s.', $otpCode);
    }

    /** Çağrı merkezi kaydı: kullanıcı adı (telefon) + tek girişlik şifre. */
    public static function buildCallCenterWelcome(string $loginPhoneDigits, string $password): string
    {
        return implode("\n", [
            'Hosgeldiniz!',
            sprintf(
                'Tum islemleriniz icin gecerli Kullanici Adiniz: %s Sifreniz:%s',
                $loginPhoneDigits,
                $password
            ),
            SellerLoginUrl::publicDisplay(),
            'Sifrenizi kimseyle paylasmayiniz.',
        ]);
    }
}
