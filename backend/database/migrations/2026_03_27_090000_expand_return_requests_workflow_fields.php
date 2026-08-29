<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('return_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('return_requests', 'description')) {
                $table->text('description')->nullable()->after('details');
            }
            if (!Schema::hasColumn('return_requests', 'seller_note')) {
                $table->text('seller_note')->nullable()->after('vendor_response');
            }
            if (!Schema::hasColumn('return_requests', 'admin_note')) {
                $table->text('admin_note')->nullable()->after('admin_response');
            }
            if (!Schema::hasColumn('return_requests', 'rejected_reason')) {
                $table->text('rejected_reason')->nullable()->after('admin_note');
            }
            if (!Schema::hasColumn('return_requests', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('rejected_reason');
            }
            if (!Schema::hasColumn('return_requests', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('return_requests', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable()->after('rejected_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('return_requests', function (Blueprint $table) {
            $columns = [
                'description',
                'seller_note',
                'admin_note',
                'rejected_reason',
                'approved_at',
                'rejected_at',
                'refunded_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('return_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
