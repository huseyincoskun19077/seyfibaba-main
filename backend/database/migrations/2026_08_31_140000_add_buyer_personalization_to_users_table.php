<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'shop_name')) {
                $table->string('shop_name', 150)->nullable()->after('address');
            }
            if (! Schema::hasColumn('users', 'business_type')) {
                $table->string('business_type', 40)->nullable()->after('shop_name');
            }
            if (! Schema::hasColumn('users', 'business_type_other')) {
                $table->string('business_type_other', 120)->nullable()->after('business_type');
            }
            if (! Schema::hasColumn('users', 'business_status')) {
                $table->string('business_status', 40)->nullable()->after('business_type_other');
            }
            if (! Schema::hasColumn('users', 'personalization_completed_at')) {
                $table->timestamp('personalization_completed_at')->nullable()->after('business_status');
            }
            if (! Schema::hasColumn('users', 'personalization_skipped_at')) {
                $table->timestamp('personalization_skipped_at')->nullable()->after('personalization_completed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'shop_name',
                'business_type',
                'business_type_other',
                'business_status',
                'personalization_completed_at',
                'personalization_skipped_at',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
