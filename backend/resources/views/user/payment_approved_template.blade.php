<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Havale Ödemeniz Onaylandı</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #6776ff; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
        .order-info { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .order-info h3 { margin-top: 0; color: #6776ff; }
        .order-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
        .order-row:last-child { border-bottom: none; }
        .label { font-weight: bold; }
        .value { color: #555; }
        .status-approved { color: green; font-weight: bold; }
        .footer { background: #333; color: white; padding: 15px; text-align: center; border-radius: 0 0 8px 8px; font-size: 12px; }
        .btn { display: inline-block; background: #6776ff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>✅ Havale Ödemeniz Onaylandı!</h1>
    </div>
    
    <div class="content">
        <p>Merhaba <strong>{{ $order->user->name ?? 'Değerli Müşterimiz' }}</strong>,</p>
        
        <p>Seyfibaba olarak havale ödemenizi onayladığımızı bildirmekten memnuniyet duyarız. Siparişiniz en kısa sürede işleme alınacaktır.</p>
        
        <div class="order-info">
            <h3>📦 Sipariş Bilgileri</h3>
            <div class="order-row">
                <span class="label">Sipariş No:</span>
                <span class="value">{{ $order->order_id }}</span>
            </div>
            @if($order->discount_amount > 0)
            <div class="order-row">
                <span class="label">Normal Tutar:</span>
                <span class="value" style="text-decoration: line-through; color: #999;">{{ number_format($order->total_amount + $order->discount_amount, 2) }} TL</span>
            </div>
            <div class="order-row">
                <span class="label">Havale İndirimi:</span>
                <span class="value" style="color: green;">-{{ number_format($order->discount_amount, 2) }} TL</span>
            </div>
            @endif
            <div class="order-row">
                <span class="label">Ödenen Tutar:</span>
                <span class="value" style="font-weight: bold; color: #6776ff;">{{ number_format($order->total_amount, 2) }} TL</span>
            </div>
            <div class="order-row">
                <span class="label">Ödeme Yöntemi:</span>
                <span class="value">Banka Havalesi</span>
            </div>
            <div class="order-row">
                <span class="label">Ödeme Durumu:</span>
                <span class="status-approved">✅ Onaylandı</span>
            </div>
            <div class="order-row">
                <span class="label">Onay Tarihi:</span>
                <span class="value">{{ $order->payment_approval_date }}</span>
            </div>
        </div>
        
        <p>Siparişinizin durumunu hesabınızdan takip edebilirsiniz.</p>
        
        <p>Sorularınız için bizimle iletişime geçebilirsiniz.</p>
        
        <p>Saygılarımızla,<br><strong>Seyfibaba Ekibi</strong></p>
    </div>
    
    <div class="footer">
        <p>© {{ date('Y') }} Seyfibaba - Tüm hakları saklıdır.</p>
    </div>
</body>
</html>