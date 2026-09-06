<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('return_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('return_requests', 'return_address')) {
                $table->text('return_address')->nullable()->after('refund_method');
            }
            if (! Schema::hasColumn('return_requests', 'return_shipping_payer')) {
                $table->string('return_shipping_payer', 20)->nullable()->after('return_address');
            }
            if (! Schema::hasColumn('return_requests', 'return_carrier_name')) {
                $table->string('return_carrier_name', 100)->nullable()->after('return_shipping_payer');
            }
            if (! Schema::hasColumn('return_requests', 'return_cargo_code')) {
                $table->string('return_cargo_code', 120)->nullable()->after('return_carrier_name');
            }
            if (! Schema::hasColumn('return_requests', 'return_shipping_instructions')) {
                $table->text('return_shipping_instructions')->nullable()->after('return_cargo_code');
            }
            if (! Schema::hasColumn('return_requests', 'buyer_return_carrier')) {
                $table->string('buyer_return_carrier', 100)->nullable()->after('return_shipping_instructions');
            }
            if (! Schema::hasColumn('return_requests', 'buyer_return_tracking_number')) {
                $table->string('buyer_return_tracking_number', 120)->nullable()->after('buyer_return_carrier');
            }
            if (! Schema::hasColumn('return_requests', 'buyer_return_tracking_url')) {
                $table->string('buyer_return_tracking_url', 500)->nullable()->after('buyer_return_tracking_number');
            }
            if (! Schema::hasColumn('return_requests', 'buyer_shipped_at')) {
                $table->timestamp('buyer_shipped_at')->nullable()->after('buyer_return_tracking_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('return_requests', function (Blueprint $table) {
            foreach ([
                'buyer_shipped_at',
                'buyer_return_tracking_url',
                'buyer_return_tracking_number',
                'buyer_return_carrier',
                'return_shipping_instructions',
                'return_cargo_code',
                'return_carrier_name',
                'return_shipping_payer',
                'return_address',
            ] as $column) {
                if (Schema::hasColumn('return_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
