import 'package:flutter/material.dart';

const kDeliveryInfoPresets = <String>[
  'Bugün kargoda',
  "Saat 15:00'e kadar verilen siparişler bugün kargoda",
  '1 iş günü içinde kargoya verilir',
  '2-3 iş günü içinde kargoya verilir',
  'Özel üretim — 7-14 gün içinde siparişe / kargoya verilir',
  'Özel üretim — 15-30 gün içinde siparişe / kargoya verilir',
  'Özel üretim — yaklaşık 7 gün sonra kargoya verilir',
  'Özel üretim — yaklaşık 15 gün sonra kargoya verilir',
  'Özel üretim — ölçüye / siparişe özel; üretim sonrası kargoya verilir',
];

/// Web ile aynı: kargo satıcıda uyarısı.
class SellerShippingNotice extends StatelessWidget {
  const SellerShippingNotice({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: const Color(0xFFFFFBEB),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: const Color(0xFFF59E0B)),
      ),
      child: const Text(
        'Kargo sizin üzerinizde: Müşteri kargo ücreti ödemez. Kargo bedelini siz ödersiniz; fiyatınızı buna göre yazın.',
        style: TextStyle(height: 1.4, fontWeight: FontWeight.w600),
      ),
    );
  }
}

/// Opsiyonel kargo/teslimat süresi alanı (satıcı ürün formları).
class SellerDeliveryInfoField extends StatelessWidget {
  const SellerDeliveryInfoField({
    super.key,
    required this.controller,
  });

  final TextEditingController controller;

  @override
  Widget build(BuildContext context) {
    final current = controller.text.trim();
    final selected =
        kDeliveryInfoPresets.contains(current) ? current : '';

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        DropdownButtonFormField<String>(
          value: selected.isEmpty ? '' : selected,
          isExpanded: true,
          decoration: const InputDecoration(
            labelText: 'Kargo süresi (hazır seçenek)',
            border: OutlineInputBorder(),
          ),
          items: [
            const DropdownMenuItem<String>(
              value: '',
              child: Text('Kendim yazacağım / boş'),
            ),
            ...kDeliveryInfoPresets.map(
              (p) => DropdownMenuItem<String>(
                value: p,
                child: Text(p, overflow: TextOverflow.ellipsis),
              ),
            ),
          ],
          onChanged: (v) {
            if (v == null || v.isEmpty) return;
            controller.text = v;
          },
        ),
        const SizedBox(height: 12),
        TextField(
          controller: controller,
          maxLength: 500,
          maxLines: 2,
          decoration: const InputDecoration(
            labelText: 'Kargo / teslimat süresi (opsiyonel)',
            hintText:
                'Örn: Özel üretim — 7-14 gün içinde siparişe / kargoya verilir',
            border: OutlineInputBorder(),
            helperText:
                'Zorunlu değil. Özel üretim seçerseniz ileride etiketlenebilir; müşteri Teslimat Bilgisi’nde görür.',
          ),
        ),
      ],
    );
  }
}
