<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Seyfibaba' }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f9f9f9; margin: 0; padding: 0;">
    <div style="max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
        <div style="background: #1a1a1a; padding: 20px 24px;">
            <h1 style="color: #FFD700; font-size: 18px; margin: 0;">Seyfibaba</h1>
        </div>
        <div style="padding: 24px;">
            @if(!empty($title))
                <h2 style="color: #2c3e50; font-size: 16px; margin: 0 0 16px;">{{ $title }}</h2>
            @endif
            <div style="white-space: pre-line; font-size: 14px;">{{ $content }}</div>
        </div>
        <div style="padding: 16px 24px; border-top: 1px solid #eee; text-align: center;">
            <p style="color: #999; font-size: 11px; margin: 0;">Seyfibaba Pazaryeri — seyfibaba.com</p>
        </div>
    </div>
</body>
</html>
