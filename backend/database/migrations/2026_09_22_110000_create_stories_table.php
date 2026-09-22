<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stories')) {
            Schema::create('stories', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('image')->nullable();
                /** product_feed | link */
                $table->string('type', 32)->default('link');
                /** popular | bestseller | discounted | featured | new_arrival */
                $table->string('feed', 32)->nullable();
                $table->string('link')->nullable();
                $table->string('see_all_url')->nullable();
                $table->unsignedInteger('serial')->default(1);
                $table->boolean('status')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('stories') && DB::table('stories')->count() === 0) {
            $now = now();
            $rows = [
                ['title' => 'Popüler ürünler', 'type' => 'product_feed', 'feed' => 'popular', 'link' => null, 'see_all_url' => '/products?highlight=popular_category', 'serial' => 1],
                ['title' => 'En Çok satılan ürünler', 'type' => 'product_feed', 'feed' => 'bestseller', 'link' => null, 'see_all_url' => '/products?highlight=best_product', 'serial' => 2],
                ['title' => 'İndirimli Ürünler', 'type' => 'product_feed', 'feed' => 'discounted', 'link' => null, 'see_all_url' => '/products?highlight=discounted', 'serial' => 3],
                ['title' => 'Yeni Kuaför Açanlara Özel', 'type' => 'product_feed', 'feed' => 'featured', 'link' => null, 'see_all_url' => '/products?highlight=featured_product', 'serial' => 4],
                ['title' => 'Güzellik Salonu açanlara', 'type' => 'product_feed', 'feed' => 'new_arrival', 'link' => null, 'see_all_url' => '/products?highlight=new_arrival', 'serial' => 5],
                ['title' => 'Günlük Kuaför Alışverişine özel', 'type' => 'product_feed', 'feed' => 'popular', 'link' => null, 'see_all_url' => '/products?highlight=popular_category', 'serial' => 6],
                ['title' => 'Satıcı Ol', 'type' => 'link', 'feed' => null, 'link' => '/satici', 'see_all_url' => null, 'serial' => 7],
                ['title' => 'Uygulamamızı Hemen indir', 'type' => 'link', 'feed' => null, 'link' => '/#indir', 'see_all_url' => null, 'serial' => 8],
                ['title' => 'Kuaför Blogları', 'type' => 'link', 'feed' => null, 'link' => '/blogs', 'see_all_url' => null, 'serial' => 9],
            ];

            foreach ($rows as $row) {
                DB::table('stories')->insert(array_merge($row, [
                    'image' => null,
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};
