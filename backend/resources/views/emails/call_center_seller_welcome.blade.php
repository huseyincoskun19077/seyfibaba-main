<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Satıcı Hesabınız Oluşturuldu</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #222; max-width: 600px; margin: 0 auto; padding: 24px;">
    @php
        $showEmailCredentials = in_array($loginChannel, ['email', 'both'], true);
        $showSmsCredentials = in_array($loginChannel, ['sms', 'both'], true) && ! empty($phoneUsername);
    @endphp

    <h2 style="margin-top: 0;">Hoş geldiniz, {{ $contactName }}</h2>

    <p>
        <strong>{{ $shopName }}</strong> için Seyfibaba satıcı hesabınız oluşturuldu.
        @if($showEmailCredentials && $loginChannel === 'email')
            Giriş bilgileriniz aşağıdadır.
        @elseif($showEmailCredentials && $showSmsCredentials)
            Giriş bilgileriniz e-posta ile de iletilmiştir; aynı tek kullanımlık şifre SMS ile de gönderilmiştir.
        @else
            Hesap bilgileriniz aşağıdadır.
        @endif
    </p>

    <table style="width: 100%; border-collapse: collapse; margin: 24px 0;">
        <tr>
            <td style="padding: 8px 0; font-weight: bold; width: 140px;">Giriş adresi</td>
            <td style="padding: 8px 0;"><a href="{{ $loginUrl }}">{{ $loginUrl }}</a></td>
        </tr>
        @if($showEmailCredentials)
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Kullanıcı adı</td>
                <td style="padding: 8px 0;">{{ $email }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">Tek kullanımlık şifre</td>
                <td style="padding: 8px 0;"><strong>{{ $otpCode }}</strong></td>
            </tr>
        @endif
        @if($showSmsCredentials)
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">SMS kullanıcı adı</td>
                <td style="padding: 8px 0;">{{ $phoneUsername }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">SMS şifresi</td>
                <td style="padding: 8px 0;"><strong>{{ $otpCode }}</strong></td>
            </tr>
        @endif
    </table>

    <p style="margin-bottom: 0;">
        @if($showEmailCredentials && $showSmsCredentials)
            Satıcı paneline e-posta adresiniz veya telefon numaranız ile, yukarıdaki tek kullanımlık şifre ile giriş yapabilirsiniz.
        @elseif($showEmailCredentials)
            Satıcı paneline e-posta adresiniz ve yukarıdaki tek kullanımlık şifre ile giriş yapabilirsiniz.
        @else
            Satıcı paneline telefon numaranız ve SMS ile gelen tek kullanımlık şifre ile giriş yapabilirsiniz.
        @endif
        İlk girişte sistem sizi zorunlu olarak yeni şifre oluşturma ekranına yönlendirir.
    </p>
</body>
</html>
