<?php

namespace App\Services;

interface SmsServiceInterface
{
    public function send(string $phone, string $message): bool;

    /** Çağrı merkezi hoş geldin SMS gibi OTP dışı metinler için. */
    public function sendTransactional(string $phone, string $message): bool;
}
