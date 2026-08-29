<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SecondHandFirstMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $listingTitle;
    public string $messageBody;
    public string $inboxUrl;

    public function __construct(string $listingTitle, string $messageBody, string $inboxUrl)
    {
        $this->listingTitle = $listingTitle;
        $this->messageBody = $messageBody;
        $this->inboxUrl = $inboxUrl;
    }

    public function build()
    {
        return $this->subject('İkinci el ilanınıza yeni mesaj')
            ->view('emails.second_hand_first_message')
            ->with([
                'listingTitle' => $this->listingTitle,
                'messageBody' => $this->messageBody,
                'inboxUrl' => $this->inboxUrl,
            ]);
    }
}

