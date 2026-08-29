<!doctype html>
<html lang="tr">
  <body style="font-family: Arial, Helvetica, sans-serif; line-height:1.5; color:#111;">
    <h2 style="margin:0 0 12px 0;">İkinci el ilanınıza yeni mesaj</h2>
    <p style="margin:0 0 10px 0;">
      İlan: <strong>{{ $listingTitle }}</strong>
    </p>
    <p style="margin:0 0 10px 0;">
      Mesaj:
    </p>
    <div style="border:1px solid #e5e7eb; background:#f9fafb; padding:12px; border-radius:8px;">
      {{ $messageBody }}
    </div>
    <p style="margin:14px 0 0 0;">
      Yanıtlamak için: <a href="{{ $inboxUrl }}">{{ $inboxUrl }}</a>
    </p>
  </body>
</html>

