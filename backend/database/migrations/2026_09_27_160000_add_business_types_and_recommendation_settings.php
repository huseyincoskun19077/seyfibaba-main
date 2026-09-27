<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADDITIVE — kategori/ürün silmez. Çoklu işletme türü + öneri ayarları + misafir görüntüleme.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'business_types')) {
                $table->json('business_types')->nullable()->after('business_type');
            }
            if (! Schema::hasColumn('users', 'personalization_enabled')) {
                $table->boolean('personalization_enabled')->default(true)->after('personalization_skipped_at');
            }
        });

        if (! Schema::hasTable('recommendation_settings')) {
            Schema::create('recommendation_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedTinyInteger('weight_segment')->default(40);
                $table->unsignedTinyInteger('weight_browse_history')->default(30);
                $table->unsignedTinyInteger('weight_business_type')->default(20);
                $table->unsignedTinyInteger('weight_popularity')->default(10);
                $table->unsignedSmallInteger('history_days')->default(30);
                $table->unsignedTinyInteger('min_views_for_signal')->default(2);
                $table->unsignedTinyInteger('vendor_diversity')->default(3);
                $table->boolean('exclude_viewed_product')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('guest_product_views')) {
            Schema::create('guest_product_views', function (Blueprint $table) {
                $table->id();
                $table->string('guest_key', 64)->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedInteger('view_count')->default(1);
                $table->timestamp('last_viewed_at')->nullable();
                $table->timestamps();
                $table->unique(['guest_key', 'product_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_product_views');
        Schema::dropIfExists('recommendation_settings');
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'personalization_enabled')) {
                $table->dropColumn('personalization_enabled');
            }
            if (Schema::hasColumn('users', 'business_types')) {
                $table->dropColumn('business_types');
            }
        });
    }
};
