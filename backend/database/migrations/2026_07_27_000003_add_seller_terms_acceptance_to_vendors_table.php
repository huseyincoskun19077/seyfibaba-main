<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->timestamp('seller_terms_accepted_at')->nullable()->after('quick_registration_note');
            $table->string('seller_terms_accepted_ip', 45)->nullable()->after('seller_terms_accepted_at');
        });

        DB::table('vendors')
            ->where(function ($query) {
                $query->whereNull('registration_source')
                    ->orWhere('registration_source', '!=', 'call_center');
            })
            ->whereNull('seller_terms_accepted_at')
            ->update(['seller_terms_accepted_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['seller_terms_accepted_at', 'seller_terms_accepted_ip']);
        });
    }
};
