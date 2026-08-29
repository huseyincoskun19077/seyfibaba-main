<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public $messageContent;

    public function __construct(string $messageContent)
    {
        $this->messageContent = $messageContent;
    }

    public function build()
    {
        \App\Helpers\MailHelper::setMailConfig();
        return $this->subject('Siparişiniz İptal Edildi — Seyfibaba')
            ->view('emails.generic-notification')
            ->with(['content' => $this->messageContent, 'title' => 'Sipariş İptal']);
    }
}
