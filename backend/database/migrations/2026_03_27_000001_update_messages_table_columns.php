<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Add new columns if they don't exist yet
            if (!Schema::hasColumn('messages', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->after('id');
                $table->unsignedBigInteger('seller_id')->after('customer_id');
                $table->string('send_by')->default('customer')->after('message');
                $table->unsignedBigInteger('product_id')->nullable()->after('send_by');
                $table->tinyInteger('customer_read_msg')->default(0)->after('product_id');
                $table->tinyInteger('seller_read_msg')->default(0)->after('customer_read_msg');

                $table->index(['customer_id', 'seller_id']);
                $table->index('product_id');
            }

            // Drop old columns if they exist
            $columnsToDrop = [];
            foreach (['to', 'from', 'read'] as $col) {
                if (Schema::hasColumn('messages', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'customer_id')) {
                $table->dropIndex(['customer_id', 'seller_id']);
                $table->dropIndex(['product_id']);
                $table->dropColumn([
                    'customer_id', 'seller_id', 'send_by',
                    'product_id', 'customer_read_msg', 'seller_read_msg',
                ]);
            }

            if (!Schema::hasColumn('messages', 'to')) {
                $table->integer('to');
                $table->integer('from');
                $table->integer('read')->default(0);
            }
        });
    }
};
