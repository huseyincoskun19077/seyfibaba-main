<?php

use App\Models\AiChatKnowledge;
use App\Models\ContactPage;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $contact = ContactPage::query()->first();
        if ($contact) {
            $contact->whatsapp = '908503035073';
            $contact->save();
        }

        $officialPrompt = <<<'PROMPT'
Sen Seyfibaba (seyfibaba.com) müşteri asistanısın. Yalnızca bu platform hakkında Türkçe, kısa ve doğru cevap ver.

KAPSAM (sadece bunlar):
- Ürün/kategori bilgisi, fiyat, stok (genel), sipariş ve kargo süreci
- Ödeme (Iyzico, havale), iade/cayma, satış sözleşmesi özeti
- Satıcı başvurusu ve mağaza kuralları (kişisel satıcı verisi vermeden)
- İkinci el / kullanılmış ürün ilanları ve platform kuralları
- Resmi Seyfibaba iletişim kanalları (bilgi bankasında verilenler)

KESİNLİKLE YAPMA:
- Kişisel telefon, TC, IBAN, adres, e-posta veya özel numaraları uydurma/paylaşma
- Satıcıların veya çalışanların özel iletişim bilgilerini verme
- Seyfibaba dışı konulara (siyaset, sağlık, genel sohbet, başka siteler) cevap verme
- Bilmediğin bilgiyi uydurma; emin değilsen resmi iletişime yönlendir

Yanıt stili: nazik, net, madde işaretli kısa metin. Gerekirse seyfibaba.com ilgili sayfasına yönlendir.
PROMPT;

        $setting = Setting::query()->first();
        if ($setting) {
            $setting->ai_chat_system_prompt = $officialPrompt;
            $setting->save();
        }

        $entries = [
            [
                'category' => 'genel',
                'question' => 'Seyfibaba ile nasıl iletişime geçebilirim?',
                'answer' => "Resmi Seyfibaba iletişim kanalları:\n- E-posta: info@seyfibaba.com\n- Telefon: 0850 303 5073\n- WhatsApp: 0850 303 5073 (https://wa.me/908503035073)\n- Adres: İstiklal Mahallesi, Serdivan/SAKARYA\n\nBaşka kişisel numara veya özel iletişim bilgisi paylaşılmaz.",
                'sort_order' => 2,
            ],
            [
                'category' => 'sozlesme',
                'question' => 'Satış sözleşmesi / mesafeli satış nedir?',
                'answer' => "Seyfibaba üzerinden verilen siparişler mesafeli satış sözleşmesi kapsamındadır. Alıcı; ürün, fiyat, kargo ve cayma hakkını sipariş öncesi görebilir. Detaylar seyfibaba.com üzerinden ilgili sözleşme/şartlar sayfalarında yayınlanır. Kişisel satıcı telefonu paylaşılmaz; destek için resmi iletişim kanallarını kullanın.",
                'sort_order' => 55,
            ],
            [
                'category' => 'sozlesme',
                'question' => 'Cayma ve iade hakkı nasıl işler?',
                'answer' => "Genel kural: teslimden itibaren 14 gün içinde cayma/iade talebi oluşturulabilir. Ürün kullanılmamış ve orijinal ambalajında olmalıdır. Hatalı/hasarlı ürünlerde süreç satıcı ve platform üzerinden yürütülür. Talep için Hesabım > Siparişlerim bölümünü kullanın.",
                'sort_order' => 56,
            ],
            [
                'category' => 'ikinci_el',
                'question' => 'İkinci el ürünler hakkında bilgi verir misin?',
                'answer' => "Seyfibaba'da ikinci el / kullanılmış ürün ilanları platform kurallarına tabidir. İlanlarda ürün durumu, fiyat ve açıklama yer alır. Alıcı-satıcı yazışması uygulama içi mesajlaşma ile yapılır. Güvenlik için kişisel telefon, IBAN veya adres paylaşımını teşvik etmeyiz; ödemeyi mümkünse platform üzerinden tamamlayın.",
                'sort_order' => 80,
            ],
            [
                'category' => 'ikinci_el',
                'question' => 'İkinci el ilan nasıl verilir?',
                'answer' => "Üye girişi yaptıktan sonra ikinci el / kullanılmış ürün bölümünden ilan oluşturabilirsiniz. Ürün fotoğrafı, açıklama, durum ve fiyat zorunludur. İlanlar platform kurallarına uygun değilse kaldırılabilir. Kişisel iletişim bilgisi ilan metnine yazılmamalıdır.",
                'sort_order' => 81,
            ],
            [
                'category' => 'satici',
                'question' => 'Satıcı bilgileri / satıcının telefonunu verir misin?',
                'answer' => "Hayır. Satıcıların kişisel telefon, TC, IBAN veya özel e-posta bilgilerini paylaşamam. Ürün ve mağaza bilgilerini seyfibaba.com ürün/mağaza sayfalarından görebilirsiniz. Destek için info@seyfibaba.com veya resmi telefon/WhatsApp kanallarını kullanın.",
                'sort_order' => 62,
            ],
            [
                'category' => 'siparis',
                'question' => 'Siparişimi iptal edebilir miyim?',
                'answer' => 'Kargoya verilmemiş siparişler için Hesabım > Siparişlerim üzerinden iptal talebi oluşturabilirsiniz. Kargoya verilmiş siparişlerde resmi destek kanallarını (info@seyfibaba.com, 0850 303 5073 veya WhatsApp) kullanın.',
                'sort_order' => 21,
            ],
            [
                'category' => 'iade',
                'question' => 'İade nasıl yapılır?',
                'answer' => "İade süreci:\n1. Hesabım > Siparişlerim üzerinden iade talebi oluşturun\n2. İade nedenini belirtin\n3. Onay sonrası ürünü kargoya verin\n4. Kontrol sonrası ödeme iadesi yapılır\n\nYardım: info@seyfibaba.com / 0850 303 5073 / WhatsApp",
                'sort_order' => 51,
            ],
        ];

        foreach ($entries as $entry) {
            AiChatKnowledge::updateOrCreate(
                ['question' => $entry['question']],
                array_merge($entry, ['is_active' => true])
            );
        }
    }

    public function down(): void
    {
        // no-op
    }
};
