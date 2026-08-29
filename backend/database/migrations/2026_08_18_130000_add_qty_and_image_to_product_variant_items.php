<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variant_items', function (Blueprint $table) {
            if (! Schema::hasColumn('product_variant_items', 'qty')) {
                $table->unsignedInteger('qty')->default(0)->after('price');
            }
            if (! Schema::hasColumn('product_variant_items', 'image')) {
                $table->string('image')->nullable()->after('qty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_variant_items', function (Blueprint $table) {
            if (Schema::hasColumn('product_variant_items', 'image')) {
                $table->dropColumn('image');
            }
            if (Schema::hasColumn('product_variant_items', 'qty')) {
                $table->dropColumn('qty');
            }
        });
    }
};
