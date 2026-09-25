import 'package:flutter/material.dart';

/// Web sepetindeki taksit uyarı kutusu.
class CartInstallmentWarning extends StatelessWidget {
  const CartInstallmentWarning({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 8, 16, 16),
      decoration: BoxDecoration(
        color: const Color(0xFFFFF9E6),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFC4A35A).withValues(alpha: 0.35)),
      ),
      child: IntrinsicHeight(
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Container(
              width: 5,
              decoration: const BoxDecoration(
                color: Color(0xFFA67C2D),
                borderRadius: BorderRadius.horizontal(left: Radius.circular(12)),
              ),
            ),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(12, 14, 14, 14),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(Icons.warning_amber_rounded,
                        size: 20, color: Color(0xFFA67C2D)),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Uyarı',
                            style: TextStyle(
                              fontWeight: FontWeight.w800,
                              fontSize: 13,
                              color: Color(0xFF8A6A24),
                            ),
                          ),
                          const SizedBox(height: 6),
                          Text.rich(
                            TextSpan(
                              style: TextStyle(
                                fontSize: 12.5,
                                height: 1.45,
                                color: const Color(0xFF6B5420),
                              ),
                              children: const [
                                TextSpan(
                                  text: 'Taksit Seçeneği: ',
                                  style: TextStyle(fontWeight: FontWeight.w800),
                                ),
                                TextSpan(
                                  text:
                                      'Sepetinizde yasal düzenleme sebebiyle taksit sınırlaması olan bir ürün varsa, ödeme adımında taksit sınırı tüm sepetinize uygulanır. Dilerseniz daha yüksek taksit seçeneği olan ürünleri ayrıca sipariş edebilirsiniz. Kart türüne göre bankaların taksit seçenekleri değişir, ödeme adımında kartınıza uygun taksitleri görebilirsiniz.',
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
