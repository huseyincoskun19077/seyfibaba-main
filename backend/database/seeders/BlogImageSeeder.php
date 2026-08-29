<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BlogImageSeeder extends Seeder
{
    /**
     * Blog yazılarına konuya uygun mevcut görselleri atar.
     * Ayrıca tekrarlayan blog'u ve gereksiz kategorileri temizler.
     */
    public function run(): void
    {
        // Tekrarlayan blog'u sil (id:7, id:2 ile aynı başlık)
        DB::table('blogs')->where('id', 7)->delete();
        $this->command->info('Tekrarlayan blog (id:7) silindi.');

        // Blog → resim eşleştirmesi (konuya uygun)
        $blogImages = [
            // Berber Koltuğu Seçim Rehberi → berber koltuğu görseli
            2 => 'uploads/custom-images/erkek-berber-koltugu-2025-12-22-01-51-49-4335.png',
            // Kuaför Salonu Açmak İçin Gerekli Ekipmanlar → makas görseli (eski ekipman png sunucuda yok)
            3 => 'uploads/custom-images/profesyonel-sac-makasi-2026-03-26-09-21-54-9130.jpg',
            // Profesyonel Berber Makası Türleri → makas görseli
            4 => 'uploads/custom-images/profesyonel-sac-makasi-2026-03-26-09-21-54-9130.jpg',
            // Saç Kurutma Makinesi Karşılaştırması → tezgah görseli (eski malzemeler png sunucuda yok)
            5 => 'uploads/custom-images/ikili-erkek-kuafor-tezgahi-profesyonel-berber-calisma-tezgahi-1773652911-1645.jpg',
            // Kuaför Salon Mobilyası Planlama Rehberi → tezgah/mobilya görseli
            6 => 'uploads/custom-images/ikili-erkek-kuafor-tezgahi-profesyonel-berber-calisma-tezgahi-1773652911-1645.jpg',
        ];

        foreach ($blogImages as $blogId => $imagePath) {
            DB::table('blogs')->where('id', $blogId)->update([
                'image' => $imagePath,
            ]);
        }
        $this->command->info(count($blogImages) . ' blog görseli güncellendi.');

        // Gereksiz İngilizce kategorileri temizle
        DB::table('blog_categories')
            ->whereIn('slug', ['development', 'latest-news', 'start-up', 'technology'])
            ->delete();
        $this->command->info('Gereksiz İngilizce blog kategorileri silindi.');

        // Kalan kategorileri aktif yap
        DB::table('blog_categories')
            ->whereIn('slug', ['guide', 'inspiration', 'revenue'])
            ->update(['status' => 1]);
        $this->command->info('Blog kategorileri aktifleştirildi.');
    }
}
