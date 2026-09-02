<?php

namespace App\Mail;

use App\Services\CallCenter\QuickSellerRegistrationService;
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
        public string $otpCode,
        public string $loginChannel,
        public ?string $phoneUsername = null,
    ) {
    }

    public function build()
    {
        return $this
            ->subject('Seyfibaba Satıcı Hesabınız Oluşturuldu')
            ->view('emails.call_center_seller_welcome');
    }

    public function includesSmsCredentials(): bool
    {
        return in_array($this->loginChannel, [
            QuickSellerRegistrationService::LOGIN_CHANNEL_SMS,
            QuickSellerRegistrationService::LOGIN_CHANNEL_BOTH,
        ], true) && $this->phoneUsername;
    }

    public function includesEmailCredentials(): bool
    {
        return in_array($this->loginChannel, [
            QuickSellerRegistrationService::LOGIN_CHANNEL_EMAIL,
            QuickSellerRegistrationService::LOGIN_CHANNEL_BOTH,
        ], true);
    }
}
