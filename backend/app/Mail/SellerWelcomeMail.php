<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SellerWelcomeMail extends Mailable
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
        return $this->subject('Seyfibaba\'ya Hoş Geldiniz!')
            ->view('emails.generic-notification')
            ->with(['content' => $this->messageContent, 'title' => 'Hoş Geldiniz']);
    }
}
