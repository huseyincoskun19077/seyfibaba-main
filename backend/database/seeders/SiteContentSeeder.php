<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SiteContentSeeder extends Seeder
{
    /**
     * Seed site content from VPS production data.
     * Source: database/data/vps-content-dump.sql
     */
    public function run(): void
    {
        // Footer
        $this->updateOrInsertFiltered('footers', ['id' => 1], [
            'about_us' => 'Kuaförler ve güzellik profesyonelleri için ihtiyaç duyulan tüm kuaför malzemelerini güvenilir satıcılarla buluşturan bir pazaryeri platformudur. Profesyonel ekipmanlardan sarf malzemelerine kadar geniş ürün yelpazemizle, sektöre hızlı, güvenli ve kolay alışveriş deneyimi sunuyoruz.',
            'phone' => '0850 303 5073',
            'email' => 'info@seyfibaba.com',
            'address' => 'istiklal mahallesi Serdivan/SAKARYA',
            'first_column' => 'Kurumsal',
            'second_column' => 'Ürünler',
            'third_column' => 'Satıcı',
            'copyright' => '© 2026 Seyfibaba. Tüm hakları saklıdır.',
        ]);

        // Footer Links
        $footerLinks = [
            ['id' => 1, 'column' => '1', 'link' => '/about', 'title' => 'Hakkımızda'],
            ['id' => 2, 'column' => '1', 'link' => '/terms-condition', 'title' => 'Kullanım Koşulları'],
            ['id' => 7, 'column' => '3', 'link' => '/about', 'title' => 'Hakkımızda'],
            ['id' => 8, 'column' => '3', 'link' => '/products?highlight=popular_category', 'title' => 'Popüler Kategoriler'],
            ['id' => 9, 'column' => '3', 'link' => '/contact', 'title' => 'İletişim'],
            ['id' => 10, 'column' => '3', 'link' => '/privacy-policy', 'title' => 'Gizlilik Politikası'],
            ['id' => 11, 'column' => '1', 'link' => '/mesafeli-satis-sozlemesi', 'title' => 'Mesafeli Satış Sözleşmesi'],
            ['id' => 12, 'column' => '1', 'link' => '/teslimat-ve-iade', 'title' => 'Teslimat ve İade Şartları'],
            ['id' => 13, 'column' => '1', 'link' => '/gizlilik-sozlesmesi', 'title' => 'Gizlilik Sözleşmesi'],
            ['id' => 14, 'column' => '2', 'link' => '/products?highlight=best_product', 'title' => 'En İyi Ürünler'],
            ['id' => 15, 'column' => '1', 'link' => '/faq', 'title' => 'Destek'],
            ['id' => 17, 'column' => '1', 'link' => '/contact', 'title' => 'İletişim'],
            ['id' => 18, 'column' => '3', 'link' => '/become-seller', 'title' => 'Satıcı Olun'],
        ];

        foreach ($footerLinks as $link) {
            $this->updateOrInsertFiltered('footer_links', ['id' => $link['id']], $link);
        }

        // Footer Social Links
        $socialLinks = [
            ['id' => 1, 'link' => 'https://www.facebook.com/', 'icon' => 'fab fa-facebook-f'],
            ['id' => 2, 'link' => 'https://twitter.com/', 'icon' => 'fab fa-twitter'],
            ['id' => 3, 'link' => 'https://linkedin.com/', 'icon' => 'fab fa-linkedin'],
        ];

        foreach ($socialLinks as $sl) {
            $this->updateOrInsertFiltered('footer_social_links', ['id' => $sl['id']], $sl);
        }

        // Services
        $services = [
            ['id' => 1, 'title' => 'Hızlı Teslimat', 'icon' => 'fas fa-shipping-fast', 'description' => 'Türkiye Geneli Hızlı Kargo', 'status' => 1],
            ['id' => 2, 'title' => 'Müşteri Memnuniyeti', 'icon' => 'fas fa-chevron-circle-left', 'description' => 'Memnuniyet Odaklı İade Politikası', 'status' => 1],
            ['id' => 3, 'title' => 'Güvenli Alışveriş', 'icon' => 'fab fa-cc-amazon-pay', 'description' => 'Güvenli Kart Ödemeleri', 'status' => 0],
            ['id' => 4, 'title' => 'Profesyonel Kalite', 'icon' => 'fas fa-check-circle', 'description' => 'Salonlara Özel Seçili Ürünler', 'status' => 1],
        ];

        foreach ($services as $s) {
            $this->updateOrInsertFiltered('services', ['id' => $s['id']], $s);
        }

        // SEO Settings
        $seoSettings = [
            ['id' => 1, 'page_name' => 'Home Page', 'seo_title' => 'Berber & Kuaför Malzemeleri – Profesyoneller İçin Alışveriş', 'seo_description' => 'berber malzemeleri,kuaför malzemeleri,berber koltuğu,kuaför ekipmanları,salon ekipmanları,berber alışveriş sitesi'],
            ['id' => 2, 'page_name' => 'About Us', 'seo_title' => 'Hakkımızda | Seyfibaba – Berber & Kuaför Malzemeleri', 'seo_description' => 'Seyfibaba.com, berber ve kuaför profesyonelleri için kaliteli ekipman ve salon malzemeleri sunar. Salonunuz için güvenli ve kolay alışveriş.'],
            ['id' => 3, 'page_name' => 'Contact Us', 'seo_title' => 'İletişim | Seyfibaba – Berber & Kuaför Malzemeleri', 'seo_description' => 'Seyfibaba.com iletişim sayfası. Berber ve kuaför malzemeleriyle ilgili soru, destek ve talepleriniz için bizimle iletişime geçin.'],
            ['id' => 5, 'page_name' => 'Seller Page', 'seo_title' => 'Mağazalarımız | Seyfibaba – Berber & Kuaför Malzemeleri', 'seo_description' => 'Seyfibaba.com\'da yer alan mağazaları keşfedin. Berber ve kuaför salonları için profesyonel, güvenilir ve kaliteli ürün seçenekleri.'],
            ['id' => 6, 'page_name' => 'Blog', 'seo_title' => 'Blog | Seyfibaba – Berber & Kuaför Rehberi', 'seo_description' => 'Berber ve kuaförler için ekipman seçimi, salon ipuçları ve profesyonel öneriler. Seyfibaba Blog ile işinizi geliştirin.'],
            ['id' => 8, 'page_name' => 'Flash Deal', 'seo_title' => 'İndirimli Ürünler | Seyfibaba – Berber & Kuaför Malzemeleri', 'seo_description' => 'Sınırlı süreli İndirimli Ürünler! Berber ve kuaför malzemelerinde özel indirimleri Seyfibaba.com\'da kaçırmayın.'],
            ['id' => 9, 'page_name' => 'Shop Page', 'seo_title' => 'Mağaza | Berber & Kuaför Malzemeleri – Seyfibaba', 'seo_description' => 'Berber ve kuaför salonları için profesyonel ekipmanlar, demirbaşlar ve sarf ürünleri. Tüm ürünleri Seyfibaba.com mağazasında keşfedin.'],
        ];

        foreach ($seoSettings as $seo) {
            $this->updateOrInsertFiltered('seo_settings', ['id' => $seo['id']], $seo);
        }

        // Contact Page
        $this->updateOrInsertFiltered('contact_pages', ['id' => 1], [
            'title' => 'İletişim',
            'description' => "Aşağıdaki iletişim formunu doldurarak ya da bize yazarak bizimle iletişime geçebilirsiniz.\nTalebinize en kısa sürede dönüş sağlanacaktır.",
            'email' => 'info@seyfibaba.com',
            'address' => 'istiklal mahallesi Serdivan/SAKARYA',
            'phone' => '0850 303 5073',
            'map' => 'https://www.google.com/maps/place/40%C2%B045\'47.4%22N+30%C2%B021\'50.0%22E/@40.7631531,30.3613224,17z',
        ]);

        // Flash Sale
        $this->updateOrInsertFiltered('flash_sales', ['id' => 1], [
            'title' => 'İndirimli Ürünler',
            'offer' => 20,
            'status' => 1,
        ]);

        // Homepage Section Titles (Turkish)
        $sectionTitles = json_encode([
            ['key' => 'Trending_Category', 'default' => 'Trending Category', 'custom' => 'Öne Çıkan Kategoriler'],
            ['key' => 'Popular_Category', 'default' => 'Popular Category', 'custom' => 'En Çok Tercih Edilen Kategoriler'],
            ['key' => 'Shop_by_Brand', 'default' => 'Shop by Brand', 'custom' => 'Markaya Göre Alışveriş'],
            ['key' => 'Top_Rated_Products', 'default' => 'Top Rated Products', 'custom' => 'En Yüksek Puanlı Ürünler'],
            ['key' => 'Best_Seller', 'default' => 'Best Seller', 'custom' => 'Çok Satanlar'],
            ['key' => 'Featured_Products', 'default' => 'Featured Products', 'custom' => 'Öne Çıkan Ürünler'],
            ['key' => 'New_Arrivals', 'default' => 'New Arrivals', 'custom' => 'Yeni Gelen Ürünler'],
            ['key' => 'Best_Products', 'default' => 'Best Products', 'custom' => 'En İyi Ürünler'],
        ], JSON_UNESCAPED_UNICODE);

        $this->updateFiltered('settings', ['id' => 1], [
            'homepage_section_title' => $sectionTitles,
            'sidebar_lg_header' => 'Seyfibaba',
            'sidebar_sm_header' => 'SB',
            'contact_email' => 'info@seyfibaba.com',
            'timezone' => 'Europe/Istanbul',
            'text_direction' => 'ltr',
            'popular_category' => 'Popular Category',
            'popular_category_product_qty' => 9,
            'frontend_url' => 'http://localhost:3000/',
            'favicon' => 'uploads/website-images/favicon.png',
            'logo' => 'uploads/website-images/logo-2025-12-18-04-53-36-7704.png',
        ]);
    }

    protected function updateOrInsertFiltered(string $table, array $attributes, array $values): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $payload = $this->filterColumns($table, array_merge($attributes, $values));

        if (count($payload) === 0) {
            return;
        }

        DB::table($table)->updateOrInsert($attributes, $payload);
    }

    protected function updateFiltered(string $table, array $where, array $values): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $payload = $this->filterColumns($table, $values);

        if (count($payload) === 0) {
            return;
        }

        DB::table($table)->where($where)->update($payload);
    }

    protected function filterColumns(string $table, array $payload): array
    {
        $existingColumns = Schema::getColumnListing($table);

        return array_filter(
            $payload,
            static fn ($value, $column) => in_array($column, $existingColumns, true),
            ARRAY_FILTER_USE_BOTH
        );
    }
}
