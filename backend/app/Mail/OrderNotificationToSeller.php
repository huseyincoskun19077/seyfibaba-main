<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class OrderNotificationToSeller extends Mailable
{
    use Queueable, SerializesModels;

    public $messageContent;
    public $subject;

    public function __construct($messageContent, $subject = 'Yeni Sipariş')
    {
        $this->messageContent = $messageContent;
        $this->subject = $subject;
    }

    public function build()
    {
        \App\Helpers\MailHelper::setMailConfig();
        
        return $this->subject($this->subject)
            ->view('emails.generic-notification')
            ->with(['content' => $this->messageContent, 'title' => 'Yeni Sipariş Bildirimi']);
    }
}
