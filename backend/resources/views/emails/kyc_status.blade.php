<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Hesap Doğrulama</title>
</head>
<body style="font-family: Arial, sans-serif; color: #222; line-height: 1.6;">
    <p>Merhaba {{ $shopName }},</p>

    @if($isApproved)
        <p><strong>Hesap doğrulamanız (KYC) onaylandı.</strong></p>
        <p>Artık satıcı panelinden ürün ekleyebilir ve satış yapabilirsiniz.</p>
        <p><a href="{{ \App\Support\SellerLoginUrl::public() }}">Satıcı paneline giriş yap</a></p>
    @else
        <p><strong>Hesap doğrulamanız (KYC) reddedildi.</strong></p>
        @if(!empty($reason))
            <p><strong>Red gerekçesi:</strong> {{ $reason }}</p>
        @endif
        <p>Lütfen belgelerinizi kontrol edip tekrar yükleyin.</p>
        <p><a href="{{ url('/seller/kyc') }}">Doğrulama sayfasına git</a></p>
    @endif

    <p style="margin-top: 24px; color: #666; font-size: 13px;">Seyfibaba Satıcı Destek</p>
</body>
</html>
