import 'package:flutter/material.dart';

import '../../../utils/constants.dart';
import '../../../utils/utils.dart';

class DeliveryInfoComponent extends StatelessWidget {
  const DeliveryInfoComponent({super.key, required this.deliveryInfo});

  final String deliveryInfo;

  @override
  Widget build(BuildContext context) {
    final custom = deliveryInfo.trim();
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Teslimat Bilgisi',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w700,
              color: blackColor,
            ),
          ),
          Utils.verticalSpace(12),
          if (custom.isNotEmpty) ...[
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: const Color(0xFFFFFAF0),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFECE3CF)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Satıcı kargo süresi',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: Color(0xFF9A7B2F),
                    ),
                  ),
                  Utils.verticalSpace(6),
                  Text(
                    custom,
                    style: TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w600,
                      height: 1.4,
                      color: blackColor,
                    ),
                  ),
                ],
              ),
            ),
            Utils.verticalSpace(14),
          ],
          _bullet(
            custom.isEmpty
                ? 'Siparişiniz onaylandıktan sonra 1-3 iş günü içinde kargoya verilir.'
                : null,
          ),
          _bullet(
            'Kargo süresi bulunduğunuz bölgeye göre 2-5 iş günü arasında değişir.',
          ),
          _bullet(
            'Kargo takip numarası sipariş detaylarınızda görüntülenecektir.',
          ),
          _bullet('Teslimat sırasında alıcının kimliği kontrol edilebilir.'),
        ],
      ),
    );
  }

  Widget _bullet(String? text) {
    if (text == null || text.isEmpty) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('•  ', style: TextStyle(fontSize: 15, height: 1.45)),
          Expanded(
            child: Text(
              text,
              style: TextStyle(
                fontSize: 14,
                height: 1.45,
                color: grayColor,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
