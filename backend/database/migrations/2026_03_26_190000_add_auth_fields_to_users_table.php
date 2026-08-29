<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->unique()->after('email');
            }

            if (!Schema::hasColumn('users', 'agree_policy')) {
                $table->tinyInteger('agree_policy')->default(0)->after('phone');
            }

            if (!Schema::hasColumn('users', 'verify_token')) {
                $table->string('verify_token')->nullable()->after('password');
            }

            if (!Schema::hasColumn('users', 'status')) {
                $table->tinyInteger('status')->default(0)->after('verify_token');
            }

            if (!Schema::hasColumn('users', 'email_verified')) {
                $table->tinyInteger('email_verified')->default(0)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $dropColumns = [];

            foreach (['phone', 'agree_policy', 'verify_token', 'status', 'email_verified'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
