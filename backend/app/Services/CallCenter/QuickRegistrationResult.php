<?php

namespace App\Services\CallCenter;

use App\Models\User;
use App\Models\Vendor;

class QuickRegistrationResult
{
    public function __construct(
        public User $user,
        public Vendor $vendor,
        public string $otpCode,
        public bool $smsSent,
        public bool $emailSent,
        public bool $wasExistingUser,
    ) {
    }
}
