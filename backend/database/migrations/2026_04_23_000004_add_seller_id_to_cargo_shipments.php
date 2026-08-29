<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cargo_shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('cargo_shipments', 'seller_id')) {
                $table->unsignedBigInteger('seller_id')->nullable()->index()->after('order_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cargo_shipments', function (Blueprint $table) {
            if (Schema::hasColumn('cargo_shipments', 'seller_id')) {
                $table->dropColumn('seller_id');
            }
        });
    }
};

