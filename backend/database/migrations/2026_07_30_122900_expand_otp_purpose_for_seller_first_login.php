<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE otp_verifications
            MODIFY purpose ENUM('register', 'password_reset', 'phone_verify', 'seller_first_login')
            NOT NULL DEFAULT 'register'
        ");

        // Older quick registrations may have been stored with an empty enum value.
        DB::table('otp_verifications')
            ->where('max_attempts', 50)
            ->where(function ($query) {
                $query->where('purpose', '')
                    ->orWhereNull('purpose');
            })
            ->update(['purpose' => 'seller_first_login']);
    }

    public function down(): void
    {
        DB::table('otp_verifications')
            ->where('purpose', 'seller_first_login')
            ->update(['purpose' => 'register']);

        DB::statement("
            ALTER TABLE otp_verifications
            MODIFY purpose ENUM('register', 'password_reset', 'phone_verify')
            NOT NULL DEFAULT 'register'
        ");
    }
};
