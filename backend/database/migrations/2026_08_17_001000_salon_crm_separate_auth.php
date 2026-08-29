<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salon_crm_salons', function (Blueprint $table) {
            if (!Schema::hasColumn('salon_crm_salons', 'owner_name')) {
                $table->string('owner_name', 120)->nullable()->after('name');
            }
            if (!Schema::hasColumn('salon_crm_salons', 'owner_username')) {
                $table->string('owner_username', 80)->nullable()->after('owner_name');
            }
            if (!Schema::hasColumn('salon_crm_salons', 'owner_password')) {
                $table->string('owner_password')->nullable()->after('owner_username');
            }
            if (!Schema::hasColumn('salon_crm_salons', 'api_token')) {
                $table->string('api_token', 80)->nullable()->after('owner_password');
            }
        });

        try {
            Schema::table('salon_crm_salons', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        } catch (\Throwable $e) {
            // foreign may already be dropped
        }

        DB::statement('ALTER TABLE salon_crm_salons MODIFY user_id BIGINT UNSIGNED NULL');

        try {
            Schema::table('salon_crm_salons', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            Schema::table('salon_crm_salons', function (Blueprint $table) {
                $table->unique('owner_username');
            });
        } catch (\Throwable $e) {
            // ignore if exists
        }

        try {
            Schema::table('salon_crm_salons', function (Blueprint $table) {
                $table->unique('api_token');
            });
        } catch (\Throwable $e) {
            // ignore
        }

        Schema::table('salon_crm_staff', function (Blueprint $table) {
            if (!Schema::hasColumn('salon_crm_staff', 'api_token')) {
                $table->string('api_token', 80)->nullable()->after('password');
            }
        });

        try {
            Schema::table('salon_crm_staff', function (Blueprint $table) {
                $table->unique('api_token');
            });
        } catch (\Throwable $e) {
            // ignore
        }

        if (Schema::hasTable('salon_crm_customers')) {
            Schema::table('salon_crm_customers', function (Blueprint $table) {
                if (!Schema::hasColumn('salon_crm_customers', 'password')) {
                    $table->string('password')->nullable()->after('phone');
                }
                if (!Schema::hasColumn('salon_crm_customers', 'api_token')) {
                    $table->string('api_token', 80)->nullable()->after('password');
                }
            });

            try {
                Schema::table('salon_crm_customers', function (Blueprint $table) {
                    $table->unique('api_token');
                });
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    public function down(): void
    {
        Schema::table('salon_crm_salons', function (Blueprint $table) {
            $table->dropUnique(['owner_username']);
            $table->dropUnique(['api_token']);
            $table->dropColumn(['owner_name', 'owner_username', 'owner_password', 'api_token']);
        });

        Schema::table('salon_crm_staff', function (Blueprint $table) {
            $table->dropUnique(['api_token']);
            $table->dropColumn(['api_token']);
        });

        if (Schema::hasTable('salon_crm_customers')) {
            Schema::table('salon_crm_customers', function (Blueprint $table) {
                $table->dropUnique(['api_token']);
                $table->dropColumn(['password', 'api_token']);
            });
        }
    }
};
