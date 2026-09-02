<?php

namespace App\Services\CallCenter;

class WelcomeChannelResult
{
    public function __construct(
        public bool $sent,
        public ?string $error = null,
    ) {
    }
}
