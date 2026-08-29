<?php

return [
    'intro' => 'Berber, kuaför ve güzellik salonu ekipmanları satışında aklınıza takılanları burada topladık. Kısa, net ve şeffaf — satıcı olarak sizin yanınızdayız.',

    'sections' => [
        [
            'title' => 'Ödeme koşulları ve hakediş',
            'icon' => 'fa-wallet',
            'items' => [
                [
                    'q' => 'Satış yaptığımda param nereye düşer, ne zaman hesabıma geçer?',
                    'a' => 'Müşteri kredi kartıyla ödeme yaptığında tutar Seyfibaba kasasında tutulmaz — ödeme doğrudan <strong>Iyzico pazaryeri sisteminin güvenli havuzuna</strong> aktarılır. Ürünü kargolayıp teslimat tamamlandıktan sonra, müşteriden iade talebi gelmezse sistem süreci otomatik tamamlar. Ardından <strong>%10 platform komisyonu</strong> kesilir ve kalan tutar Iyzico tarafından kayıtlı <strong>IBAN hesabınıza</strong> gönderilir. Ödeme takvimini satıcı panelinizdeki sipariş ve ödeme bölümlerinden izleyebilirsiniz.',
                ],
                [
                    'q' => 'Hakedişimi manuel mi çekmem gerekiyor?',
                    'a' => 'Hayır. Iyzico pazaryeri modelinde ödeme, sipariş koşulları sağlandığında <strong>otomatik olarak IBAN\'ınıza</strong> yönlendirilir. Ayrıca bir “para çek” talebi oluşturmanız gerekmez. IBAN ve KYC bilgilerinizin güncel olduğundan emin olun; ödeme bu hesaba gider.',
                ],
                [
                    'q' => 'Seyfibaba müşterinin kartından çekilen parayı elinde tutuyor mu?',
                    'a' => 'Hayır. Kredi kartı ödemeleri <strong>Iyzico altyapısında</strong> işlenir ve yasal pazaryeri kurallarına uygun şekilde dağıtılır. Bu sayede hem alıcı hem satıcı için şeffaf ve güvenli bir ödeme akışı sağlanır.',
                ],
            ],
        ],
        [
            'title' => 'Kargo süreçleri',
            'icon' => 'fa-truck',
            'items' => [
                [
                    'q' => 'Kargoyu nasıl gönderirim?',
                    'a' => 'İki seçeneğiniz var:<br><br><strong>1) Geliver anlaşması:</strong> Seyfibaba\'nın Geliver ile entegrasyonu vardır. Sipariş sonrası uygun kargo fiyatı panel üzerinden görülebilir. Ürün boyutu/ağırlığı önceden girilmediyse Geliver anlaşmalı kargo firması adresinize gelir, ölçüm yapar, ücreti belirler — <strong>bu tutar satıcıya aittir</strong> ve Seyfibaba satıcı bakiyesinden kesilir.<br><br><strong>2) Kendi kargo anlaşmanız:</strong> Anlaşmalı olduğunuz kargo firmasıyla da gönderim yapabilirsiniz; kargo bedelini yine siz ödersiniz. Takip numarasını satıcı panelinden siparişe işlemeniz yeterlidir.',
                ],
                [
                    'q' => 'Kargo ücretini kim öder?',
                    'a' => '<strong>Kargo bedeli her durumda satıcıya aittir.</strong> Ne ürün satarsanız satın, gönderim maliyetini siz karşılarsınız. Ücretsiz kargo sunmak isterseniz bunu ürün fiyatınıza yansıtabilir veya ayrı kargo ücreti tanımlayabilirsiniz — ancak ödeme yükümlülüğü satıcıdadır; müşteriden ayrıca kargo tahsil edilmez (ürün fiyatınıza dahil etmediyseniz maliyet yine size kalır).',
                ],
                [
                    'q' => 'Geliver kullanırsam ücret nasıl kesilir?',
                    'a' => 'Geliver üzerinden gönderim yaptığınızda, o gönderi için belirlenen kargo tutarı kadar <strong>Seyfibaba satıcı hesabınızdan otomatik kesilir</strong>. Panelde görünen Geliver fiyatı ne ise, kesinti de o tutar üzerinden yapılır. Boyut/ağırlık sonradan ölçülürse fark yansıyabilir; bu nedenle ürün kartlarında doğru ölçü bilgisi girmeniz önemlidir.',
                ],
                [
                    'q' => 'Sipariş geldikten sonra ne yapmalıyım?',
                    'a' => 'Satıcı panelinden <strong>Siparişler</strong> bölümüne girin, siparişi onaylayın, ürünü hazırlayıp kargolayın ve <strong>takip numarasını</strong> sisteme girin. Müşteri siparişini panelinden takip eder; siz de aynı ekrandan durumu güncellersiniz.',
                ],
            ],
        ],
        [
            'title' => 'İade süreçleri',
            'icon' => 'fa-undo',
            'items' => [
                [
                    'q' => 'İade talebi gelirse ne olur?',
                    'a' => 'Müşteri yasal süre içinde iade talebi açabilir. Talep size <strong>İade Talepleri</strong> bölümünde düşer. Ürün durumunu, iade nedenini ve varsa fotoğrafları inceleyerek <strong>kabul veya ret</strong> verirsiniz. Kabul ettiğinizde müşteri ürünü geri gönderir; ürün elinize ulaştığında süreç Iyzico ve platform kurallarına göre tamamlanır.',
                ],
                [
                    'q' => 'İade onaylanırsa param geri mi gider?',
                    'a' => 'Onaylanan iadelerde müşteriye ödeme iadesi yapılır; satıcı tarafında ilgili tutar hakedişten düşülür veya henüz aktarılmamışsa iptal edilir. Bu süreç Iyzico pazaryeri kuralları ve mesafeli satış mevzuatına uygun yürütülür.',
                ],
                [
                    'q' => 'Haksız iade talebine ne yapabilirim?',
                    'a' => 'Gerekçeyi ve kanıtları inceleyerek talebi reddedebilirsiniz. Ret kararınızda kısa ve net bir açıklama yazın. Anlaşmazlık durumunda <strong>Admin\'e Mesaj</strong> veya destek hattımız (0850 303 5073) devreye girer.',
                ],
            ],
        ],
        [
            'title' => 'Komisyon ve ücretler',
            'icon' => 'fa-percent',
            'items' => [
                [
                    'q' => 'Hangi kesintiler uygulanır? (Iyzico ve IBAN tarafı)',
                    'a' => 'Kesintiler iki hat üzerinden düşünülebilir:<br><br><strong>1) Seyfibaba platform komisyonu:</strong> Her satışta <strong>sabit %10</strong> hizmet komisyonu kesilir. Bu, pazaryeri kullanım bedelidir.<br><br><strong>2) Iyzico ödeme altyapısı:</strong> Müşterinin kart ödemesi Iyzico pazaryeri sisteminde işlenir. Alt üye işyeri kaydı, ödeme güvenliği ve hakediş dağıtımı Iyzico tarafından yürütülür.<br><br><strong>3) IBAN hakediş aktarımı:</strong> Komisyon ve iade kontrollerinden sonra kalan net tutar, doğruladığınız <strong>IBAN hesabınıza</strong> otomatik aktarılır. IBAN, KYC ve Iyzico kaydındaki bilgilerle eşleşmelidir.',
                ],
                [
                    'q' => 'Komisyon oranı nedir?',
                    'a' => 'Seyfibaba platform komisyonu tüm satışlarda <strong>sabit %10</strong>dır. Gizli ek platform kesintisi uygulanmaz; ne kazandığınızı panelden takip edebilirsiniz.',
                ],
                [
                    'q' => 'Komisyon ne zaman kesilir?',
                    'a' => 'Platform komisyonu, sipariş tamamlanıp iade süresi sorunsuz geçtikten sonra hakediş hesaplanırken otomatik düşülür. Kalan net tutar Iyzico aracılığıyla IBAN\'ınıza aktarılır.',
                ],
                [
                    'q' => 'IBAN bilgim neden zorunlu?',
                    'a' => 'Hakedişiniz yalnızca doğrulanmış <strong>IBAN</strong> hesabınıza gönderilir. Bu, hem sizin hem alıcının güvenliği için Iyzico pazaryeri kurallarının bir parçasıdır. IBAN, kimlik/KYC bilgilerinizle uyumlu olmalıdır.',
                ],
            ],
        ],
        [
            'title' => 'Ürün ve mağaza görünürlüğü',
            'icon' => 'fa-store',
            'items' => [
                [
                    'q' => 'Ürün yüklediğimde satıcı adım ve profilim müşteriye görünür mü?',
                    'a' => 'Ürün sayfalarında alıcının dikkatini dağıtmamak için <strong>satıcı adı ve profil bilgileri doğrudan gösterilmez</strong>. Müşteri ürüne odaklanır; mağaza profiliniz yalnızca size özel satıcı panelinde yönetilir. Bu, berber ve güzellik salonlarının ürün karşılaştırmasını kolaylaştırmak içindir.',
                ],
                [
                    'q' => 'Markam ve ürün açıklamalarım yine de görünür mü?',
                    'a' => 'Evet. Ürün adı, fotoğraflar, teknik özellikler, fiyat ve açıklamalarınız müşteriye açıkça sunulur. Satıcı kimliğiniz gizli tutulur; ürün kaliteniz ön plandadır.',
                ],
            ],
        ],
        [
            'title' => 'Entegrasyon, AI asistan ve panel',
            'icon' => 'fa-plug',
            'items' => [
                [
                    'q' => 'Harici ERP / stok programı entegrasyonum var mı?',
                    'a' => 'Şu an <strong>harici ERP veya muhasebe yazılımına doğrudan API entegrasyonu</strong> sunmuyoruz. Stok ve ürünlerinizi satıcı panelinden, <strong>Excel toplu yükleme</strong> veya <strong>Hızlı Ürün Ekle</strong> ile yönetebilirsiniz.',
                ],
                [
                    'q' => 'Hangi sistemler zaten entegre?',
                    'a' => 'Platform içinde hazır çalışan entegrasyonlar:<br>• <strong>Iyzico</strong> — güvenli ödeme ve otomatik hakediş<br>• <strong>Geliver</strong> — kargo fiyatlandırma ve gönderim<br>• <strong>Yapay zeka asistan</strong> — fiyat/stok güncelleme, içerik üretimi<br>• <strong>Excel toplu import</strong> — yüzlerce ürün tek seferde<br>• <strong>FCM bildirimleri</strong> — sipariş ve mesaj uyarıları (mobil)',
                ],
                [
                    'q' => 'AI asistan ne işe yarar?',
                    'a' => 'Sağ alttaki robot ikonundan panel içinde Türkçe yazışarak <strong>fiyat güncelleyebilir</strong>, stok değiştirebilir, ürün açıklaması düzenletebilir ve süreç hakkında soru sorabilirsiniz. Ayrı bir program kurmanız gerekmez — panelin içinde, 7/24 yanınızda.',
                ],
            ],
        ],
        [
            'title' => 'Neden Seyfibaba?',
            'icon' => 'fa-heart',
            'items' => [
                [
                    'q' => 'Neden Seyfibaba\'da satış yapmalıyım?',
                    'a' => 'Seyfibaba yalnızca <strong>berber, kuaför ve güzellik salonu</strong> sektörüne odaklanır. Genel pazaryerlerinde kaybolmak yerine, doğru müşteriye — salon sahiplerine ve profesyonellere — ulaşırsınız. %10 şeffaf komisyon, Iyzico güvencesi, AI destekli kolay ürün yükleme ve sektöre özel kategorilerle satışı sadeleştiriyoruz.',
                ],
                [
                    'q' => 'Satıcı olmak için neler gerekiyor?',
                    'a' => 'Satışa başlamak için sırasıyla:<br>• Satıcı hesabı ve mağaza bilgileri<br>• <strong>Hesap doğrulama (KYC)</strong> — kimlik ve vergi belgeleri<br>• <strong>Iyzico alt üye işyeri</strong> kaydı — ödeme alabilmek için zorunlu<br>• Doğrulanmış <strong>IBAN</strong> (hakediş bu hesaba gider)<br><br>Kayıt ve panel kullanımı ücretsizdir; yalnızca satış gerçekleştiğinde %10 platform komisyonu uygulanır.',
                ],
                [
                    'q' => 'Iyzico neden TC kimlik numarası istiyor?',
                    'a' => 'Iyzico alt üye işyeri kaydı, yasal ödeme altyapısı gereği <strong>TC kimlik numaranızı</strong> ister. Bu bilgi yalnızca ödeme kuruluşu kaydı ve hakediş güvenliği için kullanılır; kart bilgileriniz Seyfibaba\'da saklanmaz.',
                ],
                [
                    'q' => 'Vergi levhası neden isteniyor?',
                    'a' => 'Vergi levhası talebimiz, <strong>doğrulama amaçlıdır</strong>: yalnızca berber, kuaför ve güzellik salonu sektörüne mi hitap ettiğinizi, yoksa platform dışında farklı bir ticari faaliyetiniz olup olmadığını netleştirmek için. Seyfibaba sektöre özel bir pazaryeridir; bu belge, hem sizi hem alıcıları korumak içindir. Belgeleriniz yalnızca onay sürecinde incelenir.',
                ],
                [
                    'q' => 'Seyfibaba\'ya güvenebilir miyim?',
                    'a' => 'Ödemeler <strong>Iyzico</strong> lisanslı pazaryeri altyapısıyla işlenir; Seyfibaba müşteri kart ödemesini kendi kasasında tutmaz. Mesafeli satış ve iade süreçleri yasal çerçevede yürütülür. Satıcı doğrulama (KYC), şeffaf %10 komisyon ve panel üzerinden izlenebilir sipariş/hakediş takibi güvenilirlik için tasarlandı.',
                ],
                [
                    'q' => 'Kişisel ve ticari bilgilerim güvende mi?',
                    'a' => 'KYC belgeleri yalnızca hesap doğrulama ve yasal yükümlülükler için kullanılır; onay sonrası erişim kısıtlıdır. Ödeme verileri Iyzico güvenli altyapısında işlenir. Sorularınız için <strong>0850 303 5073</strong> veya <strong>info@seyfibaba.com</strong> üzerinden doğrudan bize ulaşabilirsiniz.',
                ],
                [
                    'q' => 'Destek almak istersem?',
                    'a' => 'Panelden <strong>Admin\'e Mesaj</strong> gönderebilir, <strong>0850 303 5073</strong> numaradan veya <strong>info@seyfibaba.com</strong> üzerinden bize ulaşabilirsiniz. AI asistan da günlük işlerinizde hızlı yardım sağlar.',
                ],
            ],
        ],
    ],
];
