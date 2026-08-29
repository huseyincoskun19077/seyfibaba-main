<?php

namespace Database\Seeders;

use App\Models\AiChatKnowledge;
use Illuminate\Database\Seeder;

class AiChatKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        $entries = [
            // Genel
            ['category' => 'genel', 'question' => 'Seyfibaba nedir?', 'answer' => 'Seyfibaba, Türkiye genelinde kuaför, berber ve güzellik profesyonellerine yönelik ekipman, demirbaş ve sarf malzemeleri satan bir online pazaryeri platformudur. Satıcılar mağaza açabilir, alıcılar güvenle alışveriş yapabilir.', 'sort_order' => 1],
            ['category' => 'genel', 'question' => 'Seyfibaba ile nasıl iletişime geçebilirim?', 'answer' => "Resmi Seyfibaba iletişim kanalları:\n- Email: info@seyfibaba.com\n- Telefon: 0850 303 5073\n- WhatsApp: 0850 303 5073 (https://wa.me/908503035073)\n- Adres: İstiklal Mahallesi, Serdivan/SAKARYA\n\nKişisel veya satıcı özel numarası paylaşılmaz.", 'sort_order' => 2],
            ['category' => 'genel', 'question' => 'Çalışma saatleriniz nedir?', 'answer' => 'Müşteri hizmetlerimiz hafta içi 09:00-18:00 saatleri arasında hizmet vermektedir. Online sipariş 7/24 verilebilir.', 'sort_order' => 3],

            // Ürünler
            ['category' => 'urun', 'question' => 'Hangi ürünleri satıyorsunuz?', 'answer' => "Kuaför ve berber ekipmanları başta olmak üzere geniş bir ürün yelpazesi sunuyoruz:\n- Kuaför koltukları ve bekleme koltukları\n- Lavabo üniteleri ve tezgahlar\n- Makas, tarak, fırça setleri\n- Fön makinesi, düzleştirici, saç maşası\n- Saç boyası, oksidan, şampuan\n- Briyantin, jöle, saç bakım ürünleri\n- Tek kullanımlık malzemeler (havlu, önlük, eldiven)", 'sort_order' => 10],
            ['category' => 'urun', 'question' => 'Ürünler orijinal mi?', 'answer' => 'Evet, platformumuzdaki tüm satıcılar orijinal ve kaliteli ürünler sunmaktadır. Her satıcı kendi ürünlerinden sorumludur ve Seyfibaba güvencesiyle satış yapmaktadır.', 'sort_order' => 11],
            ['category' => 'urun', 'question' => 'Toptan alım yapabilir miyim?', 'answer' => 'Evet, birçok satıcımız toptan satış yapmaktadır. Ürün sayfalarında toptan fiyat bilgisi bulunabilir. Özel toptan talepler için satıcıyla doğrudan iletişime geçebilirsiniz.', 'sort_order' => 12],

            // Sipariş
            ['category' => 'siparis', 'question' => 'Siparişimi nasıl takip edebilirim?', 'answer' => "Siparişinizi takip etmek için:\n1. seyfibaba.com'a giriş yapın\n2. Profilinizdeki 'Siparişlerim' bölümüne gidin\n3. İlgili siparişin detayına tıklayarak kargo takip numarasını görebilirsiniz", 'sort_order' => 20],
            ['category' => 'siparis', 'question' => 'Siparişimi iptal edebilir miyim?', 'answer' => 'Kargoya verilmemiş siparişler için iptal talebi oluşturabilirsiniz. Kargoya verilmiş siparişler için resmi destek: info@seyfibaba.com, 0850 303 5073 veya WhatsApp wa.me/908503035073', 'sort_order' => 21],
            ['category' => 'siparis', 'question' => 'Minimum sipariş tutarı var mı?', 'answer' => 'Minimum sipariş tutarı satıcıya göre değişiklik gösterebilir. Genel olarak herhangi bir minimum tutar zorunluluğu bulunmamaktadır.', 'sort_order' => 22],

            // Ödeme
            ['category' => 'odeme', 'question' => 'Hangi ödeme yöntemlerini kabul ediyorsunuz?', 'answer' => "Şu ödeme yöntemlerini kabul ediyoruz:\n- Kredi kartı / Banka kartı (Iyzico güvencesiyle)\n- Banka havalesi / EFT\n- Kapıda ödeme (seçili satıcılarda)", 'sort_order' => 30],
            ['category' => 'odeme', 'question' => 'Ödeme güvenli mi?', 'answer' => 'Evet, tüm online ödemeler Iyzico altyapısı ile 256-bit SSL şifreleme kullanılarak güvenli bir şekilde gerçekleştirilmektedir. Kart bilgileriniz kesinlikle sistemimizde saklanmaz.', 'sort_order' => 31],
            ['category' => 'odeme', 'question' => 'Taksit imkanı var mı?', 'answer' => 'Evet, kredi kartı ile ödeme yapıldığında taksit seçenekleri sunulmaktadır. Taksit sayısı bankanıza ve kart tipinize göre değişiklik gösterebilir.', 'sort_order' => 32],

            // Kargo
            ['category' => 'kargo', 'question' => 'Kargo ücreti ne kadar?', 'answer' => 'Kargo ücreti satıcıya ve ürün ağırlığına göre değişmektedir. 500₺ ve üzeri alışverişlerde ücretsiz kargo kampanyamız mevcuttur.', 'sort_order' => 40],
            ['category' => 'kargo', 'question' => 'Kargo ne kadar sürede gelir?', 'answer' => 'Siparişler genellikle 1-3 iş günü içinde kargoya verilir. Teslimat süresi bulunduğunuz şehre göre 2-5 iş günü arasında değişmektedir.', 'sort_order' => 41],
            ['category' => 'kargo', 'question' => 'Hangi kargo firması ile gönderim yapılıyor?', 'answer' => 'Satıcılarımız Yurtiçi Kargo, Aras Kargo, MNG Kargo, Sürat Kargo gibi anlaşmalı kargo firmalarıyla gönderim yapmaktadır. Kargo firması satıcıya göre değişebilir.', 'sort_order' => 42],

            // İade
            ['category' => 'iade', 'question' => 'İade koşulları nelerdir?', 'answer' => "İade koşullarımız:\n- Teslim tarihinden itibaren 14 gün içinde iade hakkı\n- Ürün kullanılmamış ve orijinal ambalajında olmalı\n- İade kargo ücreti alıcıya aittir (hatalı/hasarlı ürün hariç)\n- İade talebi onaylandıktan sonra 3-5 iş günü içinde ödeme iadesi yapılır", 'sort_order' => 50],
            ['category' => 'iade', 'question' => 'İade nasıl yapılır?', 'answer' => "İade süreci:\n1. Profilinizdeki 'Siparişlerim' bölümünden iade talebi oluşturun\n2. İade nedenini belirtin\n3. Satıcı onayından sonra ürünü kargoya verin\n4. Ürün kontrol edildikten sonra ödeme iadesi yapılır\n\nYardım: info@seyfibaba.com / 0850 303 5073 / WhatsApp", 'sort_order' => 51],

            // Satış sözleşmesi
            ['category' => 'sozlesme', 'question' => 'Satış sözleşmesi / mesafeli satış nedir?', 'answer' => 'Seyfibaba siparişleri mesafeli satış sözleşmesi kapsamındadır. Ürün, fiyat, kargo ve cayma hakkı sipariş öncesi gösterilir. Detaylar seyfibaba.com sözleşme/şartlar sayfalarındadır. Kişisel satıcı telefonu paylaşılmaz.', 'sort_order' => 55],
            ['category' => 'sozlesme', 'question' => 'Cayma ve iade hakkı nasıl işler?', 'answer' => 'Genel kural: teslimden itibaren 14 gün içinde cayma/iade talebi. Ürün kullanılmamış ve orijinal ambalajında olmalı. Talep için Hesabım > Siparişlerim kullanın.', 'sort_order' => 56],

            // Satıcı
            ['category' => 'satici', 'question' => 'Seyfibaba\'da nasıl satıcı olurum?', 'answer' => "Satıcı olmak için:\n1. seyfibaba.com/become-seller sayfasından başvuru yapın\n2. Gerekli bilgileri doldurun (firma bilgileri, iletişim)\n3. Başvurunuz incelendikten sonra mağazanız aktif edilir\n4. Ürünlerinizi ekleyip satışa başlayabilirsiniz", 'sort_order' => 60],
            ['category' => 'satici', 'question' => 'Satıcı komisyon oranı nedir?', 'answer' => 'Komisyon oranları kategori ve ürün tipine göre değişmektedir. Detaylı bilgi için satıcı başvuru sayfamızı ziyaret edebilir veya info@seyfibaba.com adresine mail atabilirsiniz.', 'sort_order' => 61],
            ['category' => 'satici', 'question' => 'Satıcı bilgileri / satıcının telefonunu verir misin?', 'answer' => 'Hayır. Satıcıların kişisel telefon, TC, IBAN veya özel e-posta bilgilerini paylaşamam. Ürün/mağaza bilgilerini seyfibaba.com üzerinden görün. Destek için resmi iletişim kanallarını kullanın.', 'sort_order' => 62],

            // İkinci el
            ['category' => 'ikinci_el', 'question' => 'İkinci el ürünler hakkında bilgi verir misin?', 'answer' => "Seyfibaba'da ikinci el / kullanılmış ürün ilanları platform kurallarına tabidir. Alıcı-satıcı yazışması uygulama içi mesajlaşma ile yapılır. Kişisel telefon/IBAN paylaşımı önerilmez; mümkünse ödemeyi platform üzerinden tamamlayın.", 'sort_order' => 80],
            ['category' => 'ikinci_el', 'question' => 'İkinci el ilan nasıl verilir?', 'answer' => 'Üye girişi sonrası ikinci el bölümünden ilan oluşturabilirsiniz. Fotoğraf, açıklama, durum ve fiyat zorunludur. Kişisel iletişim bilgisi ilan metnine yazılmamalıdır.', 'sort_order' => 81],

            // Hesap
            ['category' => 'hesap', 'question' => 'Nasıl üye olurum?', 'answer' => "seyfibaba.com/signup sayfasından ücretsiz üye olabilirsiniz. Kayıt için ad, soyad, e-posta ve telefon numarası bilgileriniz gereklidir. Telefon doğrulaması ile kayıt tamamlanır.", 'sort_order' => 70],
            ['category' => 'hesap', 'question' => 'Şifremi unuttum, ne yapmalıyım?', 'answer' => "Şifrenizi sıfırlamak için:\n1. seyfibaba.com/forgot-password sayfasına gidin\n2. Kayıtlı telefon numaranızı girin\n3. SMS ile gelen doğrulama kodunu girin\n4. Yeni şifrenizi belirleyin", 'sort_order' => 71],
        ];

        foreach ($entries as $entry) {
            AiChatKnowledge::updateOrCreate(
                ['question' => $entry['question']],
                array_merge($entry, ['is_active' => true])
            );
        }
    }
}
