import 'package:flutter/material.dart';

import '../../../core/router_name.dart';
import '../../../utils/constants.dart';

/// Web ProductReturnPolicy ile aynı iade metinleri.
class ProductReturnPolicyComponent extends StatelessWidget {
  const ProductReturnPolicyComponent({super.key});

  static const _steps = <(String, String)>[
    (
      'İade talebinizi oluşturun',
      'Cayma hakkının bulunmadığı ürünler dışındaki tüm ürünler için 8 günlük cayma hakkı süresi içinde iade talebi oluşturabilirsiniz. Siparişlerim sayfasından iade etmek istediğiniz ürünü bulun ve İade oluştur’a tıklayın.',
    ),
    (
      'İade kargo kodunuzu alın ve not edin',
      'İade talebinize en geç 2 iş günü içinde yanıt gelecek ve satıcının tercihine göre ürünleri göndermeniz için bir kargo kodu üretilecektir. Ürünler geri gönderilemeyecek durumdaysa iade talebiniz, kargo kodu oluşmadan da sonuçlanabilir.',
    ),
    (
      'Vergi mükellefi iseniz iade faturanızı kesin',
      'Vergi mükellefi iseniz para iadenizi alabilmek için satıcı adına kurumsal iade faturası düzenlemeniz gerekmektedir. İade faturanızı iade talebinize belge olarak yükleyebilir ya da info@kuafortedarik.com adresine gönderebilirsiniz.',
    ),
    (
      'Ürünleri kargoya teslim edin',
      'Ürünü tüm aparatlarıyla eksiksiz paketleyerek kargo kodunuzun ait olduğu Kuaför Tedarik anlaşmalı kargo firmasından ücretsiz gönderin. Anlaşmalı firma dışında gönderim yaparsanız iadeniz gerçekleştirilemez.',
    ),
    (
      'İadeniz onaylanır ve iade talebiniz yerine getirilir',
      'Ürün satıcıya ulaştıktan sonra ücret iadesi 2 iş günü içinde onaylanır ve bankanıza bağlı olarak 2–8 iş günü içinde kartınıza yansır. Taksitli alışverişlerde bankanız iadeyi taksitler halinde yansıtabilir.',
    ),
  ];

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'İade Koşulları',
            style: TextStyle(
              fontSize: 17,
              fontWeight: FontWeight.w800,
              color: Color(0xFF04334A),
            ),
          ),
          const SizedBox(height: 12),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: const Color(0xFFF4F6F7),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: const Color(0xFF04334A).withValues(alpha: 0.10)),
            ),
            child: const Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Kuaför Tedarik Pazaryeri’nde sipariş tutarları satıcılara doğrudan aktarılmaz ve bir havuzda bekletilir. Yasal iade süreniz dolduktan sonra ödemeniz satıcıya aktarılır.',
                  style: TextStyle(
                    height: 1.45,
                    fontSize: 13.5,
                    color: Color(0xFF04334A),
                  ),
                ),
                SizedBox(height: 10),
                Text(
                  'Ürünleri teslim aldığınız tarihten itibaren en geç 8 gün içinde Hesabım > Siparişlerim üzerinden iade talebinde bulunabilirsiniz.',
                  style: TextStyle(
                    height: 1.45,
                    fontSize: 13.5,
                    color: Color(0xFF04334A),
                  ),
                ),
                SizedBox(height: 10),
                Text(
                  'Ayıpsız ürünlerin iadesi satıcının takdirine bağlıdır. Anlaşmalı kargo ile üretilen kodu kullanarak ürünü satıcıya geri gönderebilirsiniz; ürün satıcıya ulaştıktan sonra para iadesi yapılır.',
                  style: TextStyle(
                    height: 1.45,
                    fontSize: 13.5,
                    color: Color(0xFF04334A),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 18),
          const Text(
            'İade adımları',
            style: TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w800,
              color: Color(0xFF04334A),
            ),
          ),
          const SizedBox(height: 10),
          ...List.generate(_steps.length, (i) {
            final step = _steps[i];
            return Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: Container(
                width: double.infinity,
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: whiteColor,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                      color: const Color(0xFF04334A).withValues(alpha: 0.10)),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      width: 26,
                      height: 26,
                      alignment: Alignment.center,
                      decoration: BoxDecoration(
                        color: yellowColor,
                        borderRadius: BorderRadius.circular(13),
                      ),
                      child: Text(
                        '${i + 1}',
                        style: const TextStyle(
                          fontWeight: FontWeight.w800,
                          fontSize: 12,
                          color: Color(0xFF04334A),
                        ),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            step.$1,
                            style: const TextStyle(
                              fontWeight: FontWeight.w800,
                              fontSize: 14,
                              color: Color(0xFF04334A),
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            step.$2,
                            style: TextStyle(
                              height: 1.45,
                              fontSize: 13,
                              color: const Color(0xFF04334A)
                                  .withValues(alpha: 0.72),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            );
          }),
          const SizedBox(height: 8),
          TextButton(
            onPressed: () {
              Navigator.pushNamed(context, RouteNames.orderScreen);
            },
            child: const Text('Siparişlerime git'),
          ),
        ],
      ),
    );
  }
}
