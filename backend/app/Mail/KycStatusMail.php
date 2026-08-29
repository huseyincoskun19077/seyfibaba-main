<?php

namespace App\Mail;

use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class KycStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Vendor $vendor,
        public string $status,
        public ?string $reason = null,
    ) {
    }

    public function build(): self
    {
        $shopName = $this->vendor->shop_name ?: 'Satıcı';
        $isApproved = $this->status === 'approved';

        $subject = $isApproved
            ? 'Seyfibaba — Hesap doğrulamanız onaylandı'
            : 'Seyfibaba — Hesap doğrulamanız reddedildi';

        return $this->subject($subject)
            ->view('emails.kyc_status', [
                'vendor' => $this->vendor,
                'status' => $this->status,
                'reason' => $this->reason,
                'shopName' => $shopName,
                'isApproved' => $isApproved,
            ]);
    }
}
