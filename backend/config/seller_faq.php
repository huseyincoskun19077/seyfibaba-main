<?php

return [
    'intro' => 'Kuaför, berber ve güzellik sektörüne satışta merak edilenleri yanıtladık. Şeffaf, sade ve satıcı dostu.',

    'sections' => [
        [
            'title' => 'Ödeme koşulları ve hakediş',
            'icon' => 'fa-wallet',
            'items' => [
                [
                    'q' => 'Satış yaptığımda param nereye düşer, ne zaman hesabıma geçer?',
                    'a' => 'Müşteri ödemesi <strong>Iyzico pazaryeri güvenli havuzuna</strong> düşer. Teslimat ve iade süreci sorunsuz tamamlanınca <strong>%10 platform komisyonu</strong> kesilir; kalan tutar doğrulanmış <strong>IBAN</strong> hesabınıza aktarılır. Hakediş onayı, paranın anında bankaya geçtiği anlamına gelmez; Iyzico takvimine bağlıdır.',
                ],
                [
                    'q' => 'Hakedişimi manuel mi çekmem gerekiyor?',
                    'a' => 'Hayır. Sipariş koşulları sağlandığında ödeme Iyzico tarafından <strong>otomatik olarak IBAN\'ınıza</strong> yönlendirilir. Ayrıca “para çek” talebi oluşturmanız gerekmez. IBAN ve KYC bilgilerinizin güncel olduğundan emin olun.',
                ],
                [
                    'q' => 'Kuaför Tedarik müşterinin kartından çekilen parayı elinde tutuyor mu?',
                    'a' => 'Hayır. Kredi kartı ödemeleri <strong>Iyzico altyapısında</strong> işlenir. Kart bilgisi Kuaför Tedarik\'da tutulmaz.',
                ],
                [
                    'q' => 'Hakediş için ne tamamlanmış olmalı?',
                    'a' => 'KYC doğrulama, geçerli IBAN ve Iyzico alt üye işyeri kaydı tamamlanmış olmalıdır. Eksik belgelerde aktarım gecikebilir.',
                ],
            ],
        ],
        [
            'title' => 'Kargo ve teslimat',
            'icon' => 'fa-truck',
            'items' => [
                [
                    'q' => 'Müşteriye yansıyan kargo ücreti nasıl belirlenir?',
                    'a' => 'Satıcı panelindeki <strong>Kargo Ücretleri</strong> ekranından kendi kademelerinizi tanımlarsınız (ör. 0–499 ₺ → X ₺ kargo, 1000 ₺ üzeri → ücretsiz). Müşteri sepetinde sizin ürünlerinizin alt toplamına göre kademe uygulanır; <strong>her satıcının kargosu ayrı</strong> hesaplanır.',
                ],
                [
                    'q' => 'Kargoyu kim hazırlar ve gönderir?',
                    'a' => 'Kuaför Tedarik ürünü depolamaz. <strong>Paketleme, etiketleme ve kargoya verme size aittir.</strong> Anlaşmalı kargo firmanızla (Yurtiçi, MNG, Aras, Sürat vb.) gönderip takip numarasını satıcı paneline girersiniz.',
                ],
                [
                    'q' => 'Müşteri kargo ücretini öder mi?',
                    'a' => 'Evet — panelde tanımladığınız kademe ücreti sepete yansır ve müşteri öder. Ücretsiz kargo kademesinde müşteri 0 ₺ görür. Fiili kurye faturanız kendi anlaşmanıza göredir; kademe ücreti ile kurye maliyeti arasındaki farkı siz yönetirsiniz.',
                ],
                [
                    'q' => 'Belirli bir kargo firması kullanmak zorunda mıyım?',
                    'a' => 'Hayır. Zorunlu bir kargo firması veya platform entegrasyonu dayatılmaz. Kendi anlaşmanızı kullanırsınız; maliyet ve süreç size aittir.',
                ],
                [
                    'q' => 'Takip numarasını nereye yazacağım?',
                    'a' => 'Sipariş detayında <strong>Manuel Kargo</strong> / kargoya verildi adımıyla takip numarası ve kargo firmasını girersiniz. Numara girilmeden müşteri takip edemez; hakediş sürecinde de gecikme olabilir.',
                ],
                [
                    'q' => 'Ücretsiz kargo nasıl yapılır?',
                    'a' => 'Kargo Ücretleri kademelerinde ilgili aralığın ücretini <strong>0 ₺</strong> yapın. Sepette müşteri bu satıcı için ücretsiz kargo görür. Fiili kurye bedeli yine sizin anlaşmanıza göre sizde kalır.',
                ],
                [
                    'q' => 'Birden fazla satıcılı sepette kargo nasıl işler?',
                    'a' => 'Her satıcının ürünleri kendi kademesine göre ayrı hesaplanır. Toplam kargo, satıcı kargolarının toplamıdır. Müşteri sepetinde satıcı satıcı görür.',
                ],
                [
                    'q' => 'Sipariş geldikten sonra süreç nedir?',
                    'a' => '1) Bildirim gelir, stok kontrolü yaparsınız.<br>2) Siparişi onaylayıp hazırlarsınız.<br>3) Paketleyip kargoya verirsiniz.<br>4) Takip numarasını panele işlersiniz.<br>5) Teslimat / alıcı onayı sonrası hakediş hesabına yansır.',
                ],
                [
                    'q' => 'Kargo gecikirse veya kaybolursa ne olur?',
                    'a' => 'Gönderi sorumluluğu satıcıya aittir; kargo firmasıyla siz iletişime geçersiniz. Müşteri şikâyetinde panelle veya <strong>0850 303 5073</strong> / <strong>info@kuafortedarik.com</strong> üzerinden destek alınır.',
                ],
            ],
        ],
        [
            'title' => 'İade süreçleri',
            'icon' => 'fa-undo',
            'items' => [
                [
                    'q' => 'İade talebi gelirse ne olur?',
                    'a' => 'Talepler <strong>İade Talepleri</strong> bölümüne düşer. Kabul veya gerekçeli ret verirsiniz. Kabulde alıcı ürünü iade adresinize gönderir; onaylanan iadede tutar müşteriye iade edilir ve hakedişinizden düşülür.',
                ],
                [
                    'q' => 'İade kargosunu kim öder?',
                    'a' => 'İade onayında iade kargo ücretinin kimde kalacağı (alıcı / satıcı) paneldeki seçime ve politika kurallarına göre işlenir. Onay ekranında seçenekleri kontrol edin.',
                ],
                [
                    'q' => 'Haksız iade talebine ne yapabilirim?',
                    'a' => 'Gerekçeyi ve kanıtları inceleyerek talebi reddedebilirsiniz. Ret kararınızda kısa ve net bir açıklama yazın. Anlaşmazlıkta <strong>Admin\'e Mesaj</strong> veya <strong>0850 303 5073</strong> devreye girer.',
                ],
            ],
        ],
        [
            'title' => 'Komisyon ve ücretler',
            'icon' => 'fa-percent',
            'items' => [
                [
                    'q' => 'Hangi kesintiler uygulanır?',
                    'a' => 'Kuaför Tedarik platform komisyonu sabit <strong>%10</strong>\'dur. Aylık abonelik veya gizli listeleme ücreti yoktur. Ödeme Iyzico üzerinden yürür; kalan tutar doğrulanmış IBAN\'ınıza aktarılır.',
                ],
                [
                    'q' => 'Kargo ücreti komisyona girer mi?',
                    'a' => 'Komisyon ürün satış tutarı üzerinden hesaplanır. Kargo kademelerinden müşteriye yansıyan kargo bedeli ayrı satırdır; kargo maliyetinizi kendi kurye anlaşmanıza göre yönetirsiniz.',
                ],
                [
                    'q' => 'Komisyon ne zaman kesilir?',
                    'a' => 'Sipariş tamamlanıp iade süresi sorunsuz geçtikten sonra hakediş hesaplanırken otomatik düşülür.',
                ],
                [
                    'q' => 'IBAN bilgim neden zorunlu?',
                    'a' => 'Hakedişiniz yalnızca doğrulanmış <strong>IBAN</strong> hesabınıza gönderilir. IBAN, kimlik/KYC bilgilerinizle uyumlu olmalıdır.',
                ],
            ],
        ],
        [
            'title' => 'Ürün ve mağaza görünürlüğü',
            'icon' => 'fa-store',
            'items' => [
                [
                    'q' => 'Satıcı adım ürün sayfasında nasıl görünür?',
                    'a' => 'Ürün detayında küçük bir yasal bilgilendirme satırıyla mağaza adı yer alır; müşteri mağaza sayfasına gidebilir. Telefon numarası gösterilmez.',
                ],
                [
                    'q' => 'Mağaza sayfam ve profil fotoğrafım nerede?',
                    'a' => 'Satıcı panelinde <strong>Mağaza Profili</strong>\'nden logo (profil fotoğrafı), banner ve iletişim bilgilerini güncellersiniz. Müşteri mağaza vitrininde logoyu solda görür; telefon numarası vitrinde yayınlanmaz.',
                ],
            ],
        ],
        [
            'title' => 'Entegrasyon, AI asistan ve panel',
            'icon' => 'fa-plug',
            'items' => [
                [
                    'q' => 'Harici ERP / stok programı entegrasyonum var mı?',
                    'a' => 'Şu an harici ERP veya muhasebe yazılımına doğrudan API entegrasyonu sunmuyoruz. Stok ve ürünlerinizi satıcı panelinden, <strong>Excel toplu yükleme</strong> veya <strong>Hızlı Ürün Ekle</strong> ile yönetebilirsiniz.',
                ],
                [
                    'q' => 'Hangi sistemler zaten entegre?',
                    'a' => 'Platform içinde hazır çalışan entegrasyonlar:<br>• <strong>Iyzico</strong> — güvenli ödeme ve otomatik hakediş<br>• <strong>Kademeli kargo ücretleri</strong> — panelden kendi kademelerinizi tanımlama<br>• <strong>Yapay zeka asistan</strong> — fiyat/stok güncelleme, içerik üretimi<br>• <strong>Excel toplu import</strong> — yüzlerce ürün tek seferde<br>• <strong>FCM bildirimleri</strong> — sipariş ve mesaj uyarıları (mobil)',
                ],
                [
                    'q' => 'AI asistan ne işe yarar?',
                    'a' => 'Panelde Türkçe yazışarak <strong>fiyat güncelleyebilir</strong>, stok değiştirebilir, ürün açıklaması düzenletebilir ve süreç hakkında soru sorabilirsiniz. Ayrı program kurmanız gerekmez.',
                ],
            ],
        ],
        [
            'title' => 'Neden Kuaför Tedarik?',
            'icon' => 'fa-heart',
            'items' => [
                [
                    'q' => 'Neden Kuaför Tedarik\'da satış yapmalıyım?',
                    'a' => 'Yalnızca <strong>berber, kuaför ve güzellik salonu</strong> sektörüne odaklanırız. Iyzico güvencesi, şeffaf %10 komisyon ve sektöre özel alıcı kitlesi sunarız.',
                ],
                [
                    'q' => 'Satıcı olmak için neler gerekiyor?',
                    'a' => 'KYC doğrulama, Iyzico alt üye işyeri kaydı (TC kimlik zorunlu), vergi levhası doğrulaması ve geçerli IBAN. Aylık abonelik ücreti yoktur; yalnızca satışta %10 platform komisyonu uygulanır.',
                ],
                [
                    'q' => 'Iyzico neden TC kimlik numarası istiyor?',
                    'a' => 'Iyzico alt üye işyeri kaydı, yasal ödeme altyapısı gereği <strong>TC kimlik numaranızı</strong> ister. Bu bilgi yalnızca ödeme kuruluşu kaydı ve hakediş güvenliği için kullanılır.',
                ],
                [
                    'q' => 'Vergi levhası neden isteniyor?',
                    'a' => 'Vergi levhası, sektöre özel satış yaptığınızı teyit etmek içindir. Belgeleriniz yalnızca onay sürecinde incelenir.',
                ],
                [
                    'q' => 'Destek almak istersem?',
                    'a' => 'Panelden <strong>Admin\'e Mesaj</strong> gönderebilir, <strong>0850 303 5073</strong> numaradan veya <strong>info@kuafortedarik.com</strong> üzerinden bize ulaşabilirsiniz.',
                ],
            ],
        ],
    ],
];
