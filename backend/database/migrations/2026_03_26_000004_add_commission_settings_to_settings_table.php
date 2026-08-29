<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'phone_number_required')) {
                $table->integer('phone_number_required')->default(1);
            }
            if (!Schema::hasColumn('settings', 'default_phone_code')) {
                $table->string('default_phone_code')->default('90');
            }
            if (!Schema::hasColumn('settings', 'default_commission_rate')) {
                $table->decimal('default_commission_rate', 5, 2)->default(0.00);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['phone_number_required', 'default_phone_code', 'default_commission_rate']);
        });
    }
};
