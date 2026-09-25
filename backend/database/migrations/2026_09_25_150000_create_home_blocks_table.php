<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('home_blocks')) {
            return;
        }

        Schema::create('home_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('type', 40); // campaign, product_feed, all_products, category_grid, flash_sale, brands
            $table->string('feed', 40)->nullable(); // popular, discounted, featured, new, best, weekend, custom
            $table->string('image')->nullable(); // campaign görseli
            $table->string('link', 500)->nullable();
            $table->string('mobile_link', 500)->nullable();
            $table->string('see_all_url', 500)->nullable();
            $table->text('product_ids')->nullable(); // JSON
            $table->text('category_ids')->nullable(); // JSON
            $table->unsignedTinyInteger('limit_count')->default(12);
            $table->unsignedInteger('serial')->default(1)->index();
            $table->boolean('status')->default(true);
            $table->boolean('show_on_web')->default(true);
            $table->boolean('show_on_mobile')->default(true);
            $table->timestamps();
        });

        $now = now();
        $seed = [
            [
                'title' => 'Kampanyalar',
                'type' => 'campaign',
                'feed' => null,
                'serial' => 1,
                'limit_count' => 12,
            ],
            [
                'title' => 'Tüm Ürünler',
                'type' => 'all_products',
                'feed' => null,
                'serial' => 2,
                'limit_count' => 12,
            ],
            [
                'title' => 'Hafta Sonuna Özel',
                'type' => 'product_feed',
                'feed' => 'weekend',
                'serial' => 3,
                'limit_count' => 12,
            ],
            [
                'title' => 'İndirimli Ürünler',
                'type' => 'product_feed',
                'feed' => 'discounted',
                'serial' => 4,
                'limit_count' => 12,
            ],
            [
                'title' => 'Kategoriler',
                'type' => 'category_grid',
                'feed' => null,
                'serial' => 5,
                'limit_count' => 15,
            ],
            [
                'title' => 'Flaş Ürünler',
                'type' => 'flash_sale',
                'feed' => null,
                'serial' => 6,
                'limit_count' => 12,
            ],
            [
                'title' => 'Yeni Gelenler',
                'type' => 'product_feed',
                'feed' => 'new',
                'serial' => 7,
                'limit_count' => 12,
            ],
            [
                'title' => 'Öne Çıkan Ürünler',
                'type' => 'product_feed',
                'feed' => 'featured',
                'serial' => 8,
                'limit_count' => 12,
            ],
            [
                'title' => 'En İyi Ürünler',
                'type' => 'product_feed',
                'feed' => 'best',
                'serial' => 9,
                'limit_count' => 12,
            ],
        ];

        foreach ($seed as $row) {
            DB::table('home_blocks')->insert([
                'title' => $row['title'],
                'type' => $row['type'],
                'feed' => $row['feed'],
                'image' => null,
                'link' => null,
                'mobile_link' => null,
                'see_all_url' => null,
                'product_ids' => json_encode([]),
                'category_ids' => json_encode([]),
                'limit_count' => $row['limit_count'],
                'serial' => $row['serial'],
                'status' => true,
                'show_on_web' => true,
                'show_on_mobile' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('home_blocks');
    }
};
