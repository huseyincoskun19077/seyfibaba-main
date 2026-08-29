<?php

namespace Database\Seeders;

use App\Support\ProductSlug;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GeoAuditCleanupSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $now = now();
            $defaultAdminId = $this->resolvePrimaryAdminId();
            $blogCategoryIds = $this->syncBlogCategories($now);

            if (Schema::hasTable('settings')) {
                $this->updateFiltered('settings', ['id' => 1], [
                    'contact_email' => 'info@seyfibaba.com',
                    'popular_category' => 'Popüler Kategoriler',
                ]);

                $this->syncHomepageSectionTitles($now);
            }

            $this->updateOrInsertFiltered('contact_pages', ['id' => 1], [
                'title' => 'İletişim',
                'description' => "Seyfibaba ekibine ürün tedariği, satıcı başvurusu, sipariş desteği ve iş birliği talepleriniz için ulaşabilirsiniz.\nMesajınızı inceledikten sonra en kısa sürede dönüş sağlanır.",
                'email' => 'info@seyfibaba.com',
                'address' => 'İstiklal Mahallesi, Serdivan, Sakarya',
                'phone' => '0 (543) 501 19 95',
                'map' => 'https://www.google.com/maps/place/40%C2%B045\'47.4%22N+30%C2%B021\'50.0%22E/@40.7631531,30.3613224,17z',
                'updated_at' => $now,
            ]);

            $this->updateFooterContent($now);
            $this->syncFooterLinks($now);
            $this->syncFooterSocialLinks($now);
            $this->syncSeoSettingPageNames($now);
            $this->syncCategoryDescriptions($now);
            $this->syncProductSlugOverrides($now);
            $this->syncProductSlugs($now);

            $this->updateFiltered('products', ['slug' => 'test-urunu-5-tl'], [
                    'status' => 0,
                    'show_homepage' => 0,
                    'is_featured' => 0,
                    'is_top' => 0,
                    'is_best' => 0,
                    'is_flash_deal' => 0,
                    'updated_at' => $now,
                ]);

            $replacementArticles = [
                [
                    'title' => 'Berber Koltuğu Seçim Rehberi: Salonunuz İçin Doğru Model Nasıl Belirlenir?',
                    'slug' => 'berber-koltugu-secim-rehberi',
                    'category_slug' => 'guide',
                    'seo_title' => 'Berber Koltuğu Seçim Rehberi | Seyfibaba Blog',
                    'seo_description' => 'Berber koltuğu seçerken hidrolik sistem, döşeme kalitesi, ölçü ve salon konsepti gibi kriterleri adım adım öğrenin.',
                    'description' => '<p>Berber koltuğu, bir salonun hem görsel kimliğini hem de müşteri deneyimini doğrudan etkileyen temel ekipmanlardan biridir. Yanlış koltuk seçimi; alan kullanımında verimsizlik, bakım maliyeti ve müşteri konforunda düşüş gibi sonuçlara yol açar. Bu nedenle seçim sürecinde yalnızca tasarıma değil, teknik detaylara da dikkat edilmelidir.</p><h2>1. Hidrolik sistem ve taşıma kapasitesi</h2><p>Yoğun kullanılan salonlarda hidrolik pompanın dayanıklılığı kritik önem taşır. Ağırlık taşıma kapasitesi yüksek, sessiz çalışan ve yedek parçası bulunabilen sistemler uzun vadede daha düşük servis maliyeti sağlar.</p><h2>2. Döşeme malzemesi ve temizlik kolaylığı</h2><p>Suni deri kaplamalar hızlı temizlenebilir yapılarıyla öne çıkar. Kimyasal temizlik ürünlerine dayanıklı yüzeyler, özellikle günlük müşteri sirkülasyonu yüksek işletmeler için avantaj yaratır.</p><h2>3. Salon ölçüsüne uygun gövde seçimi</h2><p>Koltuk genişliği, sırt eğimi ve ayak koyma alanı salon planına göre değerlendirilmelidir. Dar metrekareli işletmelerde kompakt modeller hareket alanını korurken, premium hizmet veren salonlarda daha geniş gövdeli modeller tercih edilebilir.</p><h2>4. Tasarım ile marka uyumu</h2><p>Klasik, modern veya endüstriyel dekorasyona sahip salonlarda koltuk tasarımı genel marka algısını güçlendirir. Aynı seri ayna, tezgah ve bekleme koltuğu ile kombin yapmak daha tutarlı bir görünüm sunar.</p><h2>5. Servis, garanti ve yedek parça planı</h2><p>Berber koltuğu gibi ağır ekipmanlarda satın alma anındaki fiyat kadar satış sonrası destek de önemlidir. Hidrolik arızası, döşeme yenileme ihtiyacı veya ayak koyma ünitesi sorunlarında hızlı müdahale sunabilen satıcılar işletmenin servis kesintisi yaşamasını azaltır.</p><h2>6. Satın alma kontrol listesi</h2><p>Karar vermeden önce koltuğun ölçülerini, pompa kapasitesini, döşeme tipini, taşıma koşullarını ve kurulum desteğini aynı tabloda toplamak faydalı olur. Özellikle birden fazla koltuk alınacak projelerde model standardizasyonu, salon içi görünüm birliğini korurken bakım süreçlerini de kolaylaştırır.</p><p>Seyfibaba üzerinden farklı satıcıların berber koltuğu modellerini fiyat, malzeme ve tasarım açısından karşılaştırarak salonunuz için en uygun seçeneği değerlendirebilirsiniz.</p>',
                ],
                [
                    'title' => 'Kuaför Salonu Açmak İçin Gerekli Temel Ekipmanlar',
                    'slug' => 'kuafor-salonu-acmak-icin-gerekli-ekipmanlar',
                    'category_slug' => 'guide',
                    'seo_title' => 'Kuaför Salonu Açmak İçin Gerekli Ekipmanlar | Seyfibaba Blog',
                    'seo_description' => 'Yeni salon açarken ihtiyaç duyulan koltuk, tezgah, yıkama seti, sterilizasyon ve sarf malzeme listesini inceleyin.',
                    'description' => '<p>Yeni bir kuaför salonu açarken dekorasyon kadar ekipman planlaması da bütçe ve operasyon başarısını belirler. Başlangıç aşamasında doğru demirbaşları seçmek, hem hizmet kalitesini yükseltir hem de ilerleyen dönemde ek maliyetlerin önüne geçer.</p><h2>Karşılama ve çalışma alanı</h2><p>Danışma bankosu, bekleme koltukları, kuaför tezgahı ve ayna modülleri ilk yatırım kalemlerinin başında gelir. Çalışma alanı düzenlenirken elektrik priz yerleşimi ve cihaz güvenliği de hesaba katılmalıdır.</p><h2>Yıkama ve bakım ekipmanları</h2><p>Yıkama setleri, saç bakım üniteleri ve havlu dolapları müşteri konforunu doğrudan etkiler. Su tesisatı ile uyumlu ürünler seçmek kurulum sürecini hızlandırır.</p><h2>Cihazlar ve sarf malzemeler</h2><p>Fön makinesi, profesyonel kurutucu, maşa, düzleştirici, makas seti, boya ekipmanları ve sterilizatörler günlük hizmetin temelini oluşturur. Sık kullanılan ürünlerde garanti ve servis desteği mutlaka kontrol edilmelidir.</p><h2>Stok planlama</h2><p>Şampuan, oksidan, boya, havlu, pelerin ve tek kullanımlık ürünler için düzenli tedarik modeli oluşturulmalıdır. Pazaryeri yapısı sayesinde Seyfibaba üzerinde birden fazla tedarikçiden teklif karşılaştırması yapmak mümkündür.</p><h2>Acil ve ikinci faz alımlar</h2><p>Salon açılış bütçesini yönetirken tüm ekipmanları tek seferde almak yerine zorunlu ürünler ile ikinci faz ürünleri ayırmak faydalıdır. Açılış günü gerekli olmayan dekoratif veya destekleyici ekipmanlar daha sonra eklenebilir; böylece nakit akışı daha kontrollü ilerler.</p><h2>Verimli kurulum için planlama sırası</h2><p>İlk olarak hizmet verilecek operasyonların listesi çıkarılmalı, ardından her operasyon için zorunlu ekipmanlar belirlenmelidir. Sonraki adımda elektrik, su ve depolama gereksinimleri netleştirilir; en sonda ise marka ve model karşılaştırmasına geçilir. Bu yöntem eksik ürünle açılış riskini azaltır.</p><p>Salon açılışı öncesinde ihtiyaç listesini kategori bazında hazırlamak, bütçe yönetimini kolaylaştırır ve eksik ekipmanla açılış riskini azaltır.</p>',
                ],
                [
                    'title' => 'Profesyonel Berber Makası Türleri ve Kullanım Alanları',
                    'slug' => 'profesyonel-berber-makasi-turleri',
                    'category_slug' => 'guide',
                    'seo_title' => 'Profesyonel Berber Makası Türleri | Seyfibaba Blog',
                    'seo_description' => 'Düz kesim, inceltme ve dilimleme makasları arasındaki farkları öğrenin; salonunuz için doğru seti seçin.',
                    'description' => '<p>Berber makası seçimi yalnızca keskinlikten ibaret değildir. Kullanım amacı, çelik kalitesi, ergonomi ve el alışkanlığı doğru performans için birlikte değerlendirilmelidir.</p><h2>Düz kesim makası</h2><p>Genel saç kesim işlemlerinde en sık kullanılan modeldir. Kısa ve net çizgiler elde etmek için uygundur.</p><h2>İnceltme makası</h2><p>Saç yoğunluğunu azaltmak, kat geçişlerini yumuşatmak ve hacmi dengelemek amacıyla tercih edilir. Diş yapısı farklılıkları sonuç üzerinde doğrudan etkilidir.</p><h2>Dilimleme makası</h2><p>Daha yaratıcı ve yumuşak geçişli kesimlerde kullanılır. Modern saç tasarımlarında destekleyici ekipman olarak önem kazanır.</p><h2>Ergonomi ve bakım</h2><p>Parmak dayanağı, sap formu ve ağırlık dengesi uzun süreli kullanımda bilek yorgunluğunu belirler. Düzenli yağlama ve doğru saklama koşulları ise kesim hassasiyetinin korunmasına yardımcı olur.</p><h2>Çelik kalitesi neden önemlidir?</h2><p>Paslanmaz çelik kalitesi, keskinliğin korunma süresi ve bilenme aralığı üzerinde doğrudan etkilidir. Daha yoğun kullanılan salonlarda düşük kaliteli çelik, kısa sürede performans kaybına yol açarak maliyeti artırabilir.</p><h2>Set alırken nelere bakılmalı?</h2><p>Düz kesim ve inceltme makasının birlikte alındığı setler, günlük operasyonlarda daha dengeli kullanım sunar. Ancak set kararında yalnızca fiyat değil, makasların denge hissi, sap formu ve servis desteği birlikte değerlendirilmelidir.</p><p>Seyfibaba blogunda yer alan ekipman rehberleri, profesyonellerin ürün seçimini yalnızca fiyat değil kullanım senaryosuna göre yapmasına yardımcı olmayı hedefler.</p>',
                ],
                [
                    'title' => 'Saç Kurutma Makinesi Karşılaştırması: Salon Tipi Modellerde Nelere Dikkat Edilmeli?',
                    'slug' => 'sac-kurutma-makinesi-karsilastirmasi',
                    'category_slug' => 'revenue',
                    'seo_title' => 'Saç Kurutma Makinesi Karşılaştırması | Seyfibaba Blog',
                    'seo_description' => 'Watt gücü, motor tipi, ısı ayarı ve servis desteği açısından profesyonel kurutma makinelerini karşılaştırın.',
                    'description' => '<p>Profesyonel saç kurutma makineleri, ev tipi modellere kıyasla daha uzun kullanım süresi ve daha yüksek performans sunar. Ancak salon için doğru modeli belirlerken sadece watt değeri yeterli değildir.</p><h2>Motor teknolojisi</h2><p>AC motorlu modeller dayanıklılığıyla öne çıkarken, yeni nesil dijital motorlar daha hafif ve sessiz çalışma avantajı sunar.</p><h2>Isı ve hız kademeleri</h2><p>Farklı saç tiplerinde kontrollü sonuç almak için çoklu ısı ve hava akışı seçeneği önemlidir. Soğuk üfleme fonksiyonu şeklin sabitlenmesine yardımcı olur.</p><h2>Aksesuar ve servis</h2><p>Difüzör, dar başlık ve yedek filtre gibi aksesuarlar kullanım esnekliği sağlar. Servis ve garanti ağı güçlü markalar yoğun salon kullanımında daha güvenlidir.</p><h2>Kablo uzunluğu ve günlük kullanım ergonomisi</h2><p>Salonlarda cihazlar sürekli el değiştirir ve gün içinde uzun süre çalışır. Bu nedenle kablo uzunluğu, ağırlık dengesi ve tutuş konforu yalnızca kullanım rahatlığını değil, çalışan yorgunluğunu da etkiler.</p><h2>Satın alma kararı için pratik kıstaslar</h2><p>Kurutma makinesi seçerken motor tipi, ısı kademesi, hava basıncı, garanti süresi ve servis yaygınlığı aynı tabloda değerlendirilmelidir. Özellikle aynı anda birden fazla istasyon çalışan salonlarda yedek cihaz planı da satın alma kararının bir parçası olmalıdır.</p><p>Kategori bazlı karşılaştırma yaparken enerji verimliliği, kablo uzunluğu ve kullanıcı ergonomisini birlikte değerlendirmek doğru yatırım kararını kolaylaştırır.</p>',
                ],
                [
                    'title' => 'Kuaför Salon Mobilyası Planlama Rehberi',
                    'slug' => 'kuafor-salon-mobilyasi-planlama-rehberi',
                    'category_slug' => 'inspiration',
                    'seo_title' => 'Kuaför Salon Mobilyası Planlama Rehberi | Seyfibaba Blog',
                    'seo_description' => 'Kuaför tezgahı, bekleme alanı ve depolama çözümlerini salon akışına uygun şekilde planlamak için ipuçları.',
                    'description' => '<p>Salon mobilyaları yalnızca estetik görünüm sağlamaz; personel akışı, müşteri konforu ve depolama verimliliği üzerinde de belirleyicidir. Başarılı bir planlama için alan kullanımını hizmet akışına göre kurgulamak gerekir.</p><h2>Çalışma istasyonları</h2><p>Ayna ve tezgah aralıkları, personelin rahat hareket edebileceği şekilde bırakılmalıdır. Elektrik erişimi ve cihaz yerleşimi planın parçası olmalıdır.</p><h2>Bekleme alanı</h2><p>Girişe yakın, ferah ve temiz tutulabilen bekleme bölümü ilk izlenimi güçlendirir. Müşteri yoğunluğuna göre koltuk sayısı optimize edilmelidir.</p><h2>Depolama ve düzen</h2><p>Sarf malzeme, boya ve havlu stoklarının görünür karmaşa oluşturmadan saklanacağı dolap çözümleri operasyon hızını artırır.</p><h2>Salon akışını bozmayan yerleşim</h2><p>Mobilya planında yalnızca ürün ölçüleri değil, müşteri dolaşımı, personelin cihazlara erişim hızı ve temizlik ekibinin hareket alanı da hesaba katılmalıdır. Yanlış yerleşim, yoğun saatlerde hizmet süresini uzatabilir.</p><h2>Uzun vadeli yatırım bakışı</h2><p>Mobilya seçiminde modüler yapı, yüzey dayanıklılığı ve parça değiştirilebilirliği gibi detaylar ilk yatırım maliyetinden daha kritik hale gelebilir. Özellikle büyüme planı olan salonlar için genişlemeye uygun modüler çözümler daha sürdürülebilir sonuç verir.</p><p>Seyfibaba üzerinde mobilya, ekipman ve aksesuarları aynı projede bir araya getirerek salon kurulum bütçenizi daha kontrollü yönetebilirsiniz.</p>',
                ],
            ];

            if (!Schema::hasTable('blogs')) {
                $this->updateAboutUsContent($now);
                return;
            }

            $placeholderBlogs = DB::table('blogs')
                ->where(function ($query) {
                    $query->whereRaw('LOWER(title) like ?', ['%lorem%'])
                        ->orWhereRaw('LOWER(title) like ?', ['%iphone 14%'])
                        ->orWhereRaw('LOWER(title) like ?', ['%wordpress plugins%'])
                        ->orWhereRaw('LOWER(title) like ?', ['%newspaper themes%'])
                        ->orWhereRaw('LOWER(description) like ?', ['%lorem ipsum%'])
                        ->orWhereRaw('LOWER(description) like ?', ['%dummy text%']);
                })
                ->orderBy('id')
                ->get()
                ->values();

            foreach ($replacementArticles as $index => $article) {
                $placeholderBlog = $placeholderBlogs->get($index);
                $existingBlog = DB::table('blogs')
                    ->where('slug', $article['slug'])
                    ->first();

                $fallbackCategoryId = $placeholderBlog->blog_category_id ?? reset($blogCategoryIds) ?: 1;
                $blogPayload = [
                    'title' => $article['title'],
                    'slug' => $article['slug'],
                    'blog_category_id' => $blogCategoryIds[$article['category_slug']] ?? $fallbackCategoryId,
                    'seo_title' => $article['seo_title'],
                    'seo_description' => $article['seo_description'],
                    'description' => $article['description'],
                    'status' => 1,
                    'show_homepage' => 1,
                    'updated_at' => $now,
                ];

                if ($placeholderBlog) {
                    DB::table('blogs')
                        ->where('id', $placeholderBlog->id)
                        ->update(array_merge(
                            $blogPayload,
                            $this->preserveBlogMediaFields($placeholderBlog)
                        ));
                    continue;
                }

                if ($existingBlog) {
                    DB::table('blogs')
                        ->where('id', $existingBlog->id)
                        ->update(array_merge(
                            $blogPayload,
                            $this->preserveBlogMediaFields($existingBlog)
                        ));
                    continue;
                }

                if ($defaultAdminId) {
                    DB::table('blogs')->insert(array_merge($blogPayload, [
                        'admin_id' => $defaultAdminId,
                        'image' => 'uploads/website-images/blog-placeholder.png',
                        'views' => 0,
                        'created_at' => $now,
                    ]));
                }
            }

            $remainingPlaceholderIds = $placeholderBlogs
                ->slice(count($replacementArticles))
                ->pluck('id');

            if ($remainingPlaceholderIds->isNotEmpty()) {
                DB::table('blogs')
                    ->whereIn('id', $remainingPlaceholderIds)
                    ->update([
                        'status' => 0,
                        'show_homepage' => 0,
                        'updated_at' => $now,
                    ]);
            }

            $this->updateAboutUsContent($now);
        });
    }

    protected function resolvePrimaryAdminId(): ?int
    {
        if (!Schema::hasTable('admins')) {
            return null;
        }

        return DB::table('admins')->orderBy('id')->value('id');
    }

    protected function syncBlogCategories($now): array
    {
        if (!Schema::hasTable('blog_categories')) {
            return [];
        }

        $categories = [
            'guide' => 'Satın Alma Rehberleri',
            'inspiration' => 'Salon İlhamı',
            'revenue' => 'Operasyon ve Verimlilik',
        ];

        $ids = [];

        foreach ($categories as $slug => $name) {
            $existingId = DB::table('blog_categories')->where('slug', $slug)->value('id');

            if ($existingId) {
                DB::table('blog_categories')
                    ->where('id', $existingId)
                    ->update([
                        'name' => $name,
                        'status' => 1,
                        'updated_at' => $now,
                    ]);
                $ids[$slug] = $existingId;
                continue;
            }

            $legacyId = DB::table('blog_categories')
                ->whereIn('slug', ["{$slug}-"])
                ->value('id');

            if ($legacyId) {
                DB::table('blog_categories')
                    ->where('id', $legacyId)
                    ->update([
                        'name' => $name,
                        'slug' => $slug,
                        'status' => 1,
                        'updated_at' => $now,
                    ]);
                $ids[$slug] = $legacyId;
                continue;
            }

            $ids[$slug] = DB::table('blog_categories')->insertGetId([
                'name' => $name,
                'slug' => $slug,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('blog_categories')
            ->whereIn('slug', ['development', 'latest-news', 'start-up', 'technology'])
            ->update([
                'status' => 0,
                'updated_at' => $now,
            ]);

        return $ids;
    }

    protected function updateFooterContent($now): void
    {
        $payload = [
            'phone' => '0 (543) 501 19 95',
            'email' => 'info@seyfibaba.com',
            'address' => 'İstiklal Mahallesi, Serdivan, Sakarya',
            'updated_at' => $now,
        ];

        $payload['about_us'] = 'Seyfibaba, berber ve kuaför salonlarının ekipman, mobilya ve sarf malzeme ihtiyaçlarını tek noktada buluşturan Türkiye merkezli bir pazaryeridir. Profesyonel satıcıları aynı çatı altında toplayarak salon kurulumundan günlük tedarike kadar hızlı ve güvenli alışveriş deneyimi sunar.';

        $this->updateOrInsertFiltered('footers', ['id' => 1], $payload);
    }

    protected function syncFooterLinks($now): void
    {
        if (!Schema::hasTable('footer_links')) {
            return;
        }

        DB::table('footer_links')
            ->where('id', 16)
            ->orWhere('link', '/blogs')
            ->delete();

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
            $this->updateOrInsertFiltered('footer_links', ['id' => $link['id']], array_merge($link, [
                'updated_at' => $now,
            ]));
        }
    }

    protected function syncFooterSocialLinks($now): void
    {
        if (!Schema::hasTable('footer_social_links')) {
            return;
        }

        $socialLinks = [
            ['id' => 1, 'link' => 'https://facebook.com/seyfibaba', 'icon' => 'fab fa-facebook-f'],
            ['id' => 2, 'link' => 'https://x.com/seyfibaba', 'icon' => 'fab fa-twitter'],
            ['id' => 3, 'link' => 'https://linkedin.com/company/seyfibaba', 'icon' => 'fab fa-linkedin'],
            ['id' => 4, 'link' => 'https://instagram.com/seyfibaba', 'icon' => 'fab fa-instagram'],
            ['id' => 5, 'link' => 'https://youtube.com/@seyfibaba', 'icon' => 'fab fa-youtube'],
        ];

        foreach ($socialLinks as $link) {
            $this->updateOrInsertFiltered('footer_social_links', ['id' => $link['id']], array_merge($link, [
                'updated_at' => $now,
            ]));
        }
    }

    protected function syncProductSlugs($now): void
    {
        if (!Schema::hasTable('products') || !Schema::hasColumns('products', ['id', 'name', 'slug'])) {
            return;
        }

        DB::table('products')
            ->select('id', 'name', 'slug')
            ->orderBy('id')
            ->chunkById(100, function ($products) use ($now) {
                foreach ($products as $product) {
                    $normalizedSlug = ProductSlug::normalize($product->name ?: $product->slug);
                    $currentSlug = trim((string) $product->slug);

                    if ($normalizedSlug === '' || $normalizedSlug === $currentSlug) {
                        continue;
                    }

                    $uniqueSlug = $this->resolveUniqueProductSlug($product->id, $normalizedSlug);

                    if ($uniqueSlug === $currentSlug) {
                        continue;
                    }

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update([
                            'slug' => $uniqueSlug,
                            'updated_at' => $now,
                        ]);
                }
            });
    }

    protected function syncProductSlugOverrides($now): void
    {
        if (!Schema::hasTable('products') || !Schema::hasColumns('products', ['id', 'slug'])) {
            return;
        }

        $slugMap = [
            'bayan-kuafr-tarama-koltuu-profesyonel-kuafr-tarama-' => 'bayan-kuaforu-tarama-koltugu-profesyonel-kuafor-tarama',
            'bayan-kuafr-tarama-koltuu-profesyonel-kuafr-tarama' => 'bayan-kuaforu-tarama-koltugu-profesyonel-kuafor-tarama',
            'ikili-erkek-kuafr-tezgh-profesyonel-berber-alma-tezgh' => 'ikili-erkek-kuafor-tezgahi-profesyonel-berber-calisma-tezgahi',
            'erkek-kuafr-koltuu-profesyonel-berber-koltuu' => 'erkek-kuafor-koltugu-profesyonel-berber-koltugu',
            'fonex-briyantin-saa-parlaklk-ve-ekil-veren-sa-ekillendirici' => 'fonex-briyantin-saca-parlaklik-ve-sekil-veren-sac-sekillendirici',
            'zenix-yz-maskesi-cilt-temizleyici-ve-bakm-maskesi' => 'zenix-yuz-maskesi-cilt-temizleyici-ve-bakim-maskesi',
            'erkek-kuafr-tra-seti-profesyonel-berber-ekipman-paketi' => 'erkek-kuafor-tiras-seti-profesyonel-berber-ekipman-paketi',
            'profesyonel-erkek-kuafr-koltuu' => 'profesyonel-erkek-kuafor-koltugu',
        ];

        foreach ($slugMap as $legacySlug => $targetSlug) {
            $productId = DB::table('products')->where('slug', $legacySlug)->value('id');

            if (!$productId) {
                continue;
            }

            $uniqueSlug = $this->resolveUniqueProductSlug($productId, $targetSlug);

            DB::table('products')
                ->where('id', $productId)
                ->update([
                    'slug' => $uniqueSlug,
                    'updated_at' => $now,
                ]);
        }
    }

    protected function resolveUniqueProductSlug(int $productId, string $baseSlug): string
    {
        $slug = $baseSlug;
        $suffix = 2;

        while (
            DB::table('products')
                ->where('slug', $slug)
                ->where('id', '!=', $productId)
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    protected function syncSeoSettingPageNames($now): void
    {
        if (!Schema::hasTable('seo_settings')) {
            return;
        }

        $pageNames = [
            1 => 'Ana Sayfa',
            2 => 'Hakkımızda',
            3 => 'İletişim',
            5 => 'Mağazalar',
            6 => 'Blog',
            8 => 'İndirimli Ürünler',
            9 => 'Mağaza',
        ];

        foreach ($pageNames as $id => $pageName) {
            $this->updateFiltered('seo_settings', ['id' => $id], [
                'page_name' => $pageName,
                'updated_at' => $now,
            ]);
        }
    }

    protected function syncHomepageSectionTitles($now): void
    {
        if (
            !Schema::hasTable('settings') ||
            !Schema::hasColumn('settings', 'homepage_section_title')
        ) {
            return;
        }

        $setting = DB::table('settings')->where('id', 1)->first(['homepage_section_title']);
        if (!$setting) {
            return;
        }

        $sections = json_decode($setting->homepage_section_title ?? '[]', true);
        if (!is_array($sections)) {
            return;
        }

        $translations = [
            'Trending_Category' => ['default' => 'Öne Çıkan Kategoriler', 'custom' => 'Öne Çıkan Kategoriler'],
            'Popular_Category' => ['default' => 'En çok tercih edilen ürünler', 'custom' => 'En çok tercih edilen ürünler'],
            'Shop_by_Brand' => ['default' => 'Markaya Göre Alışveriş', 'custom' => 'Markaya Göre Alışveriş'],
            'Top_Rated_Products' => ['default' => 'En Yüksek Puanlı Ürünler', 'custom' => 'En Yüksek Puanlı Ürünler'],
            'Best_Seller' => ['default' => 'Çok Satanlar', 'custom' => 'Çok Satanlar'],
            'Featured_Products' => ['default' => 'Öne Çıkan Ürünler', 'custom' => 'Öne Çıkan Ürünler'],
            'New_Arrivals' => ['default' => 'Yeni Gelen Ürünler', 'custom' => 'Yeni Gelen Ürünler'],
            'Best_Products' => ['default' => 'En İyi Ürünler', 'custom' => 'En İyi Ürünler'],
        ];

        $updated = false;

        foreach ($sections as &$section) {
            $key = $section['key'] ?? null;
            if (!$key || !isset($translations[$key])) {
                continue;
            }

            $section['default'] = $translations[$key]['default'];
            $section['custom'] = $translations[$key]['custom'];
            $updated = true;
        }
        unset($section);

        if (!$updated) {
            return;
        }

        $this->updateFiltered('settings', ['id' => 1], [
            'homepage_section_title' => json_encode($sections, JSON_UNESCAPED_UNICODE),
            'updated_at' => $now,
        ]);
    }

    protected function updateAboutUsContent($now): void
    {
        if (!Schema::hasTable('about_us')) {
            return;
        }

        $existingRow = DB::table('about_us')->where('id', 1)->first();

        $payload = $this->filterColumns('about_us', [
            'description' => '<p>Seyfibaba, berber ve kuaför profesyonellerinin ekipman, mobilya ve sarf malzeme ihtiyaçlarını tek çatı altında buluşturmak için geliştirilen Türkiye merkezli bir pazaryeridir. Platform; salon kurmak isteyen girişimciler, aktif işletmeler ve yeni tedarikçi arayan profesyoneller için daha hızlı, daha şeffaf ve daha güvenli bir satın alma deneyimi sunmayı hedefler.</p><p>Berber koltuklarından kuaför tezgahlarına, sterilizasyon ekipmanlarından günlük tüketim malzemelerine kadar geniş ürün grupları; farklı satıcıların tekliflerini karşılaştırarak incelenebilir. Böylece kullanıcılar yalnızca ürün değil, fiyat, stok ve satıcı performansı açısından da daha bilinçli karar verebilir.</p><h2>Neden Seyfibaba?</h2><p>Salon sektöründe doğru ekipmana erişim, hizmet kalitesi kadar operasyon verimliliğini de belirler. Seyfibaba bu ihtiyaca odaklanarak kategori bazlı keşif, satıcı mağazaları, kampanya alanları ve içerik rehberleri ile profesyonellerin ihtiyaçlarını tek bir akışta toplamayı amaçlar.</p><p>Gelişen yapımızla birlikte satıcı çeşitliliğini artırmaya, ürün içeriklerini güçlendirmeye ve sektöre özel rehber içerikler üretmeye devam ediyoruz. Hedefimiz, berber ve kuaför ekosisteminde güvenilir bir dijital tedarik noktası olmaktır.</p>',
            'updated_at' => $now,
        ]);

        if (count($payload) === 0) {
            return;
        }

        if ($existingRow) {
            DB::table('about_us')->where('id', 1)->update($payload);
            return;
        }

        DB::table('about_us')->insert($this->filterColumns('about_us', array_merge($payload, [
            'id' => 1,
            'banner_image' => 'uploads/website-images/about-us.jpg',
            'status' => 1,
            'created_at' => $now,
        ])));
    }

    protected function syncCategoryDescriptions($now): void
    {
        if (
            !Schema::hasTable('categories') ||
            !Schema::hasColumn('categories', 'description')
        ) {
            return;
        }

        $categories = DB::table('categories')
            ->select('id', 'name', 'slug', 'description')
            ->orderBy('id')
            ->get();

        foreach ($categories as $category) {
            if (!empty(trim((string) $category->description))) {
                continue;
            }

            $description = $this->resolveCategoryDescription(
                (string) $category->name,
                (string) $category->slug
            );

            DB::table('categories')
                ->where('id', $category->id)
                ->update([
                    'description' => $description,
                    'updated_at' => $now,
                ]);
        }
    }

    protected function resolveCategoryDescription(string $name, string $slug): string
    {
        $normalized = strtolower($slug . ' ' . $name);

        $presets = [
            'koltuk' => 'Berber koltugu kategorisinde hidrolik sistem, doseme dayanikliligi, olcu uyumu ve gunluk salon temposuna uygun mekanik performans gibi kriterleri birlikte degerlendirebilirsiniz. Bu alanda yer alan modeller; yeni salon kurulumu, mevcut koltuk yenileme ihtiyaci ve premium hizmet alani olusturma senaryolari icin farkli butce ve tasarim seviyelerinde karsilastirma imkani sunar.',
            'tezgah' => 'Kuafor tezgahi ve benzeri salon mobilyalarinda yalnizca gorunum degil; depolama alani, elektrik erisimi, yuzey dayanikliligi ve salon ici yerlesim verimliligi de belirleyicidir. Bu kategori, farkli olcu ve tasarimdaki modelleri ayni sayfada inceleyerek isletmenize uygun cozumu daha hizli secmenize yardimci olur.',
            'mobilya' => 'Salon mobilyalari kategorisi; bekleme alani, calisma istasyonu ve depolama duzenini bir arada dusunen isletmeler icin planlanmistir. Moduler yapi, yuzey kalitesi, temizlenebilirlik ve alan kullanimi gibi kriterler bu grupta urun secimini dogrudan etkiler.',
            'makas' => 'Profesyonel makas kategorisinde duz kesim, inceltme ve yardimci set seceneklerini kullanim amacina gore karsilastirabilirsiniz. Celik kalitesi, ergonomi, denge hissi ve uzun sureli kullanimda performans korunumu, bu urun grubunda satin alma kararinin temelini olusturur.',
            'makine' => 'Tiras makineleri, kurutma makineleri ve diger elektrikli ekipmanlarda motor tipi, guc seviyesi, servis destegi ve gunluk kullanim ergonomisi kritik rol oynar. Bu kategori, salon yogunluguna uygun cihazlari marka ve model bazinda daha bilincli sekilde filtrelemeniz icin hazirlandi.',
            'kurutma' => 'Sac kurutma ekipmanlarinda watt gucu kadar motor yapisi, isi kademeleri, aksesuar uyumu ve calisan ergonomisi de onemlidir. Bu kategorideki urunler, profesyonel salon kullanimi icin dayaniklilik ve performans odakli olarak karsilastirilabilir.',
            'yikama' => 'Yikama setleri ve ilgili ekipmanlarda su tesisati uyumu, seramik govde kalitesi, oturum konforu ve temizlik kolayligi one cikar. Bu kategori, hem yeni kurulum hem de mevcut salon yenileme ihtiyaclari icin farkli segmentte cozumleri bir araya getirir.',
            'steriliz' => 'Sterilizasyon ve hijyen ekipmanlari kategorisi, salonlarda duzenli temizlik akisi ve guvenli hizmet standardi icin kritik urunleri kapsar. Cihaz kapasitesi, kullanim sikligi ve sarf uyumu bu alanda karar verirken dikkat edilmesi gereken temel basliklardir.',
            'havlu' => 'Havlu ve tekstil urunlerinde tekrarli tedarik kolayligi, dayaniklilik ve yogun kullanima uygun yikama performansi belirleyicidir. Bu kategori, gunluk operasyonu kesintisiz surdurmek isteyen salonlar icin pratik urun gruplarini bir araya getirir.',
            'boya' => 'Boya ve yardimci sarf urunleri kategorisinde tekrarli alim, stok surekliligi ve profesyonel kullanim uyumu one cikar. Farkli marka ve ambalaj seceneklerini ayni akista inceleyerek salonunuzun kullanim duzenine uygun urunleri daha hizli belirleyebilirsiniz.',
            'sarf' => 'Sarf malzeme kategorisi, salonlarin gunluk operasyonunda surekli tekrar eden ihtiyaclari daha duzenli yonetmek icin tasarlanmistir. Hizli tuketilen urunlerde stok surekliligi, tedarik hizi ve paket icerigi gibi kriterler satin alma kararinda on plandadir.',
        ];

        foreach ($presets as $keyword => $description) {
            if (str_contains($normalized, $keyword)) {
                return $description;
            }
        }

        return sprintf(
            '%s kategorisinde yer alan urunler; profesyonel salon kullanimi, dayaniklilik, servis kolayligi ve fiyat-performans dengesi acisindan degerlendirilebilir. Bu alan, farkli satici ve marka seceneklerini ayni listeleme yapisinda inceleyerek satin alma surecini hizlandirmaniz ve ihtiyaciniza daha uygun urunu belirlemeniz icin hazirlandi.',
            $name
        );
    }

    protected function preserveBlogMediaFields(object $blog): array
    {
        $payload = [];
        $columns = Schema::getColumnListing('blogs');

        if (in_array('admin_id', $columns, true) && isset($blog->admin_id)) {
            $payload['admin_id'] = $blog->admin_id;
        }

        if (in_array('image', $columns, true) && isset($blog->image)) {
            $payload['image'] = $blog->image ?: 'uploads/website-images/blog-placeholder.png';
        }

        if (in_array('views', $columns, true) && isset($blog->views)) {
            $payload['views'] = $blog->views;
        }

        if (in_array('created_at', $columns, true) && isset($blog->created_at)) {
            $payload['created_at'] = $blog->created_at;
        }

        return $payload;
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
        if (!Schema::hasTable($table)) {
            return [];
        }

        $existingColumns = Schema::getColumnListing($table);

        return array_filter(
            $payload,
            static fn ($value, $column) => in_array($column, $existingColumns, true),
            ARRAY_FILTER_USE_BOTH
        );
    }
}
