<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Siparişiniz Tamamlandı</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #27ae60;">Siparişiniz Tamamlandı!</h2>
        
        <p>Sayın <strong>{{ $user_name }}</strong>,</p>
        
        <p>Siparişinizi teslim aldığınızı onayladınız. Siparişiniz başarıyla tamamlandı.</p>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p><strong>Sipariş No:</strong> {{ $order_number }}</p>
            <p><strong>Sipariş Tarihi:</strong> {{ $order_date }}</p>
            <p><strong>Teslim Tarihi:</strong> {{ $completed_date }}</p>
            <p><strong>Toplam Tutar:</strong> {{ number_format($total_price, 2) }} TL</p>
        </div>
        
        <p>Alışverişiniz için teşekkür ederiz. Size en iyi hizmeti sunmaya devam edeceğiz.</p>
        
        <p>Saygılarımızla,<br>
        <strong>Seyfibaba Pazaryeri</strong></p>
        
        <hr style="border: 1px solid #eee; margin: 20px 0;">
        <p style="color: #777; font-size: 12px;">
            Bu e-posta {{ $order_number }} numaralı siparişin teslim onayıdır.
        </p>
    </div>
</body>
</html>