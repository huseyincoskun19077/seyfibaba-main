<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CallCenterSellerWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $contactName,
        public string $shopName,
        public string $email,
        public string $loginUrl,
    ) {
    }

    public function build()
    {
        return $this
            ->subject('Seyfibaba Satıcı Hesabınız Oluşturuldu')
            ->view('emails.call_center_seller_welcome');
    }
}
