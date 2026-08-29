<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KuaforSectorBlogSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('blogs') || ! Schema::hasTable('blog_categories')) {
            $this->command?->warn('Blog tabloları bulunamadı, seeder atlandı.');

            return;
        }

        $data = require __DIR__.'/sources/blogs/kuafor_sector.php';
        $now = now();
        $adminId = DB::table('admins')->orderBy('id')->value('id') ?? 1;

        $images = [
            'uploads/custom-images/erkek-berber-koltugu-2025-12-22-01-51-49-4335.png',
            'uploads/custom-images/profesyonel-sac-makasi-2026-03-26-09-21-54-9130.jpg',
            'uploads/custom-images/ikili-erkek-kuafor-tezgahi-profesyonel-berber-calisma-tezgahi-1773652911-1645.jpg',
        ];

        $categoryIds = [];
        foreach ($data['categories'] as $category) {
            $existingId = DB::table('blog_categories')->where('slug', $category['slug'])->value('id');

            if ($existingId) {
                DB::table('blog_categories')->where('id', $existingId)->update([
                    'name' => $category['name'],
                    'status' => 1,
                    'updated_at' => $now,
                ]);
                $categoryIds[$category['slug']] = (int) $existingId;
                continue;
            }

            $categoryIds[$category['slug']] = (int) DB::table('blog_categories')->insertGetId([
                'name' => $category['name'],
                'slug' => $category['slug'],
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $created = 0;
        $updated = 0;
        $index = 0;

        foreach ($data['posts'] as $post) {
            $categoryId = $categoryIds[$post['category']] ?? reset($categoryIds);
            $image = $images[$index % count($images)];
            $index++;

            $payload = [
                'title' => $post['title'],
                'blog_category_id' => $categoryId,
                'description' => $post['description'],
                'seo_title' => $post['seo_title'],
                'seo_description' => $post['seo_description'],
                'status' => 1,
                'show_homepage' => 0,
                'updated_at' => $now,
            ];

            $existing = DB::table('blogs')->where('slug', $post['slug'])->first();

            if ($existing) {
                DB::table('blogs')->where('id', $existing->id)->update($payload);
                $updated++;
                continue;
            }

            DB::table('blogs')->insert(array_merge($payload, [
                'admin_id' => $adminId,
                'slug' => $post['slug'],
                'image' => $image,
                'views' => 0,
                'created_at' => $now,
            ]));
            $created++;
        }

        $this->command?->info(sprintf(
            'Kuaför sektör blogları: %d kategori, %d yeni yazı, %d güncellendi.',
            count($categoryIds),
            $created,
            $updated
        ));
    }
}
