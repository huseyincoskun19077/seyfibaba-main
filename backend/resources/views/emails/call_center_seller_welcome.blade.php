<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Satıcı Hesabınız Oluşturuldu</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #222; max-width: 600px; margin: 0 auto; padding: 24px;">
    <h2 style="margin-top: 0;">Hoş geldiniz, {{ $contactName }}</h2>

    <p>
        <strong>{{ $shopName }}</strong> için Seyfibaba satıcı hesabınız oluşturuldu.
        Tek kullanımlık giriş şifreniz SMS ile gönderildi.
    </p>

    <table style="width: 100%; border-collapse: collapse; margin: 24px 0;">
        <tr>
            <td style="padding: 8px 0; font-weight: bold; width: 140px;">Giriş adresi</td>
            <td style="padding: 8px 0;"><a href="{{ $loginUrl }}">{{ $loginUrl }}</a></td>
        </tr>
        <tr>
            <td style="padding: 8px 0; font-weight: bold;">E-posta</td>
            <td style="padding: 8px 0;">{{ $email }}</td>
        </tr>
    </table>

    <p style="margin-bottom: 0;">
        Satıcı paneline e-posta adresiniz ve SMS ile gelen tek kullanımlık şifre ile giriş yapabilirsiniz.
        İlk girişte sistem sizi zorunlu olarak yeni şifre oluşturma ekranına yönlendirir.
    </p>
</body>
</html>
