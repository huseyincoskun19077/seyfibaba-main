<?php

return [
    'title' => 'Satıcı Şartlar ve Tanıtım',
    'subtitle' => 'Satıcı ol → doğrula → ürün ekle → kargola → hakediş al. Komisyon, kargo, ödeme ve iade burada net.',
    'hero' => 'Seyfibaba; berber, kuaför ve güzellik salonu ekipmanlarına özel Türkiye pazaryeridir. Amacımız doğru müşteriyle buluşmanız, ödemenin Iyzico güvencesinde kalması ve süreçlerin şeffaf olmasıdır. Aşağıdaki yol haritası, satışa başlamadan bilmeniz gerekenlerin özetidir.',

    'highlights' => [
        [
            'icon' => 'fa-percent',
            'title' => 'Sadece %10 komisyon',
            'text' => 'Ürün satışından yalnızca %10 kesilir. Gizli üyelik / listeleme ücreti yoktur. ~%90 satıcıya aittir.',
        ],
        [
            'icon' => 'fa-truck',
            'title' => 'Kargo satıcıya aittir',
            'text' => 'Gönderim ücreti ve sorumluluğu tamamen satıcıya aittir. Platform ürünü depolamaz.',
        ],
        [
            'icon' => 'fa-university',
            'title' => 'Iyzico hakediş',
            'text' => 'Ödeme Iyzico havuzunda işlenir. Alıcı onayı sonrası net tutar IBAN’ınıza aktarılır.',
        ],
        [
            'icon' => 'fa-undo',
            'title' => 'Panelden iade',
            'text' => 'İade talebi size düşer; inceler, kabul veya gerekçeli ret verirsiniz. Hakediş sonuca göre güncellenir.',
        ],
    ],

    'sections' => [
        [
            'id' => 'roadmap',
            'title' => 'Satıcı Yol Haritası (Özet)',
            'icon' => 'fa-route',
            'body' => 'Tek bakışta akış:',
            'bullets' => [
                '<strong>1)</strong> Satıcı Ol / Satıcı Girişi',
                '<strong>2)</strong> KYC doğrulama (belge + IBAN + Iyzico alt üye işyeri)',
                '<strong>3)</strong> Ürün ekle (tekil, hızlı AI veya Excel)',
                '<strong>4)</strong> Satışta yalnızca <strong>%10</strong> komisyon — ekstra platform ücreti yok',
                '<strong>5)</strong> Siparişi hazırla, kargoya ver (kargo tamamen satıcıya ait)',
                '<strong>6)</strong> Ürün alıcıya ulaşır → alıcı onayı',
                '<strong>7)</strong> Iyzico talimatı → havuzdaki ödemeden %10 düşülür, kalan IBAN’a yatar',
                '<strong>8)</strong> İade olursa: talep → satıcı incelemesi → kabul/ret → gerekirse geri gönderim → hakediş düzeltmesi',
            ],
        ],
        [
            'id' => 'step-register',
            'title' => '1) Satıcı Ol ve Giriş Yap',
            'icon' => 'fa-user-plus',
            'body' => 'Siteden veya uygulamadan <strong>Satıcı Ol</strong> ile kaydolun, ardından <strong>Satıcı Girişi</strong> yapın. Web satıcı paneli ve mobil satıcı paneli aynı hesabı kullanır.',
            'bullets' => [
                'Mağaza / iletişim bilgilerinizi eksiksiz doldurun',
                'Giriş sonrası paneli açın (web veya mobil)',
                'Destek: Admin’e Mesaj, SSS veya 0850 303 5073',
            ],
        ],
        [
            'id' => 'step-kyc',
            'title' => '2) KYC Doğrulama Yap',
            'icon' => 'fa-id-card',
            'body' => 'Satış ve hakediş için hesap doğrulama zorunludur. Kimlik/vergi belgeleri, IBAN ve Iyzico alt üye işyeri kaydı tamamlanmadan ödeme aktarımı gecikebilir veya yapılamaz.',
            'bullets' => [
                'Belgeleri panelden yükleyin, onay sürecini takip edin',
                'IBAN’ınız kimlik/vergi bilgilerinizle uyumlu olsun',
                'Iyzico alt üye işyeri kaydı ödeme alabilmek için gereklidir',
            ],
        ],
        [
            'id' => 'step-product',
            'title' => '3) Ürün Ekle',
            'icon' => 'fa-box-open',
            'body' => 'Ürünlerinizi tek tek, hızlı ürün ekleme veya Excel toplu yükleme ile ekleyebilirsiniz. Fotoğraf, stok, fiyat ve ölçü bilgilerini doğru girin; kargo maliyeti ve müşteri deneyimi buna bağlıdır.',
            'bullets' => [
                'Tek ürün / Hızlı ürün (AI) / Excel toplu yükleme',
                'Stok ve fiyatı güncel tutun',
                'Ölçü/ağırlık bilgisi kargo için önemlidir',
            ],
        ],
        [
            'id' => 'commission',
            'title' => '4) Komisyon: Sadece %10',
            'icon' => 'fa-percent',
            'body' => 'Hangi ürünü eklerseniz ekleyin, satıştan <strong>yalnızca %10 platform komisyonu</strong> kesilir. Gizli üyelik, listeleme veya ek platform ücreti alınmaz. Satış tutarının yaklaşık <strong>%90’ı satıcıya</strong> aittir (iade ve kargo ayrı konularıdır).',
            'bullets' => [
                'Örnek: 1.000 TL satış → ~100 TL komisyon, ~900 TL satıcı payı (kargo hariç)',
                'Komisyon, hakediş hesaplanırken düşülür',
                'Kart ödemesi Iyzico’da işlenir; kart bilgisi Seyfibaba’da tutulmaz',
            ],
        ],
        [
            'id' => 'shipping',
            'title' => '5) Kargoya Ver — Ücret Satıcıya Aittir',
            'icon' => 'fa-truck',
            'body' => '<strong>Kargo tamamen satıcıya aittir.</strong> Ürünü siz paketler, kargoya verir ve takip numarasını panele işlersiniz. Seyfibaba ürünü depolamaz ve sizin yerinize taşımaz.',
            'bullets' => [
                'Geliver entegrasyonu veya kendi kargo anlaşmanızı kullanabilirsiniz',
                'Kargo bedeli satıcıya aittir; platform kargo ücreti ödemez',
                'Takip numarasını siparişe mutlaka girin',
            ],
        ],
        [
            'id' => 'orders',
            'title' => '6) Sipariş Akışı',
            'icon' => 'fa-shopping-cart',
            'body' => 'Sipariş düşünce panel ve mobil bildirim alırsınız. Adımlar:',
            'bullets' => [
                'Siparişi kontrol edip onaylayın / hazırlayın',
                'Kargolayın ve takip numarasını girin',
                'Teslimat / alıcı onayı sonrası hakediş süreci ilerler',
            ],
        ],
        [
            'id' => 'payout',
            'title' => '7) Alıcı Onayı Sonrası Hesaba Yatırma',
            'icon' => 'fa-university',
            'body' => 'Müşteri ödediğinde tutar Seyfibaba kasasında tutulmaz; <strong>Iyzico pazaryeri havuzunda</strong> işlenir. Ürün alıcıya ulaşır ve <strong>alıcı onayı</strong> sonrası (iade süreci de sorunsuzsa) Iyzico’ya talimat verilir. Havuzdaki sipariş ödemesinden %10 komisyon düşülür; kalan net tutar satıcının doğrulanmış <strong>IBAN hesabına</strong> yatar.',
            'bullets' => [
                'Alıcı onayı / teslimat onayı kritik adımdır',
                'KYC + IBAN + Iyzico kaydı tamamlanmış olmalıdır',
                'Hakedişi panelden takip edebilirsiniz',
            ],
        ],
        [
            'id' => 'returns',
            'title' => '8) Müşteri İade Talep Ederse',
            'icon' => 'fa-undo',
            'body' => 'İade, yaygın pazaryeri düzeninde olduğu gibi panel üzerinden yürütülür:',
            'bullets' => [
                'Alıcı yasal süre içinde uygulamadan/siteden iade talebi açar (neden + gerekirse fotoğraf)',
                'Talep size <strong>İade Talepleri</strong> ekranında düşer; ürünü ve gerekçeyi incelersiniz',
                'Kabul veya gerekçeli ret verirsiniz',
                'Kabulde: alıcı ürünü geri gönderir; ürün size ulaşınca süreç tamamlanır',
                'Onaylanan iadede alıcıya ödeme iadesi yapılır; satıcı hakedişinden ilgili tutar düşülür veya henüz aktarılmamışsa aktarım düzeltilir/iptal edilir',
                'Anlaşmazlıkta Admin’e Mesaj / destek hattı (0850 303 5073) devreye girer',
            ],
        ],
        [
            'id' => 'summary',
            'title' => 'Özet Kutusu',
            'icon' => 'fa-check-circle',
            'body' => 'Akılda kalsın:',
            'bullets' => [
                '<strong>Komisyon:</strong> Sadece %10 — ekstra platform ücreti yok',
                '<strong>Kargo:</strong> Tamamen satıcıya aittir',
                '<strong>Ödeme:</strong> Iyzico havuzu → alıcı onayı → net tutar IBAN’a',
                '<strong>İade:</strong> Talep → satıcı incelemesi → kabul/ret → gerekirse geri gönderim → hakediş güncellemesi',
            ],
        ],
    ],

    'contact' => [
        'title' => 'Destek',
        'text' => 'Detaylı sorular için SSS’ye bakın veya bize ulaşın. Bu sayfa satıcı süreçlerinin özet yol haritasıdır.',
        'phone' => '0850 303 5073',
        'email' => 'info@seyfibaba.com',
    ],
];
