import 'package:flutter/material.dart';

import 'onbording_model.dart';

/// İlk kurulum tanıtımı — kuaför / güzellik salonu sahipleri.
final onBoardingList = <OnBordingModel>[
  const OnBordingModel(
    art: 0,
    accent: Color(0xFFFFBB38),
    badge: 'Kuaför Toptan pazarı',
    title: 'Salonunuzun toptan pazarı',
    paragraph:
        'Seyfibaba, kuaför ve güzellik salonları için kurulmuş bir toptan pazaryeridir. Boya, fön, koltuk, cihaz ve sarf malzemeyi salon fiyatına bulun; tedariki tek yerden yönetin. Uygulamayı kullanmak tamamen ücretsizdir.',
  ),
  const OnBordingModel(
    art: 1,
    accent: Color(0xFFFFBB38),
    badge: 'Tamamen ücretsiz',
    title: 'Kullanılmayan ekipman değer görsün',
    paragraph:
        'Atıl koltuk, cihaz veya aleti ikinci elde yayın; ihtiyaç duyduğunuzu da sektörün içinden alın. Alıcı ve satıcı salon sahipleri. İlan vermek ve bakmak ücretsizdir.',
  ),
  const OnBordingModel(
    art: 2,
    accent: Color(0xFFFFBB38),
    badge: 'Tamamen ücretsiz',
    title: 'Market ve salon aynı uygulamada',
    paragraph:
        'Randevu, personel, gelir-gider ve müşteri takibini tek panelden yönetin. Alışveriş, ikinci el ve salon işletmesi aynı uygulamada; ekstra yazılım yok, tamamen ücretsiz.',
  ),
];
