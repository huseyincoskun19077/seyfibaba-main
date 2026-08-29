import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/data/datasources/remote_data_source.dart';
import '../../../core/error/exception.dart';
import '../../../core/error/failure.dart';
import '../../../utils/utils.dart';
import '../model/product_details_product_model.dart';

class ProductFurnitureInquiry extends StatelessWidget {
  const ProductFurnitureInquiry({super.key, required this.product});

  final ProductDetailsProductModel product;

  bool get _enabled {
    if (product.allowFurnitureInquiry) return true;
    final name = (product.category?.name ?? '').toLowerCase();
    return name.contains('mobilya');
  }

  String get _whatsappDigits {
    var digits = product.furnitureInquiryWhatsapp.replaceAll(RegExp(r'\D'), '');
    if (digits.isEmpty) digits = '908503035073';
    if (digits.startsWith('0') && digits.length == 11) {
      digits = '90${digits.substring(1)}';
    } else if (digits.length == 10) {
      digits = '90$digits';
    }
    return digits;
  }

  Future<void> _openWhatsApp(BuildContext context) async {
    final url = 'https://seyfibaba.com/urun/${product.slug}';
    final text = [
      'Merhaba, kuaför mobilyası hakkında bilgi almak istiyorum.',
      '',
      'Ürün: ${product.name}',
      'Link: $url',
    ].join('\n');
    final uri = Uri.parse(
      'https://wa.me/$_whatsappDigits?text=${Uri.encodeComponent(text)}',
    );
    final ok = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!ok && context.mounted) {
      Utils.errorSnackBar(context, 'WhatsApp açılamadı');
    }
  }

  void _openSupportSheet(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
      ),
      builder: (sheetContext) {
        return _FurnitureInquiryForm(
          product: product,
          parentContext: context,
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    if (!_enabled) return const SizedBox.shrink();

    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFF6FFF4),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFD9EAD3)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Bu ürün hakkında bilgi alın',
            style: TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 6),
          const Text(
            'Kuaför mobilyalarında ölçü, renk ve teslimat için WhatsApp’tan yazabilir veya destek talebi oluşturabilirsiniz.',
            style: TextStyle(fontSize: 13, height: 1.4, color: Color(0xFF6B7280)),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: ElevatedButton(
                  onPressed: () => _openWhatsApp(context),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF25D366),
                    foregroundColor: Colors.white,
                    elevation: 0,
                    minimumSize: const Size.fromHeight(44),
                  ),
                  child: const Text('WhatsApp ile yazın'),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: OutlinedButton(
                  onPressed: () => _openSupportSheet(context),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: Colors.black,
                    minimumSize: const Size.fromHeight(44),
                  ),
                  child: const Text('Destek talebi'),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _FurnitureInquiryForm extends StatefulWidget {
  const _FurnitureInquiryForm({
    required this.product,
    required this.parentContext,
  });

  final ProductDetailsProductModel product;
  final BuildContext parentContext;

  @override
  State<_FurnitureInquiryForm> createState() => _FurnitureInquiryFormState();
}

class _FurnitureInquiryFormState extends State<_FurnitureInquiryForm> {
  final _name = TextEditingController();
  final _phone = TextEditingController();
  final _email = TextEditingController();
  final _message = TextEditingController();
  bool _loading = false;

  @override
  void dispose() {
    _name.dispose();
    _phone.dispose();
    _email.dispose();
    _message.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_name.text.trim().isEmpty || _phone.text.trim().isEmpty) {
      Utils.errorSnackBar(context, 'Ad ve telefon gerekli');
      return;
    }
    setState(() => _loading = true);
    try {
      final msg = await context.read<RemoteDataSource>().sendProductInquiry({
        'name': _name.text.trim(),
        'phone': _phone.text.trim(),
        'email': _email.text.trim(),
        'message': _message.text.trim(),
        'product_id': '${widget.product.id}',
      });
      if (!mounted) return;
      Navigator.pop(context);
      Utils.showSnackBar(widget.parentContext, msg);
    } on InvalidAuthData catch (e) {
      final first = e.errors.name.isNotEmpty
          ? e.errors.name.first
          : e.errors.phone.isNotEmpty
              ? e.errors.phone.first
              : e.errors.message.isNotEmpty
                  ? e.errors.message.first
                  : 'Talep gönderilemedi';
      if (mounted) Utils.errorSnackBar(context, first);
    } on ServerException catch (e) {
      if (mounted) Utils.errorSnackBar(context, e.message);
    } catch (_) {
      if (mounted) {
        Utils.errorSnackBar(context, 'Talep gönderilemedi');
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.of(context).viewInsets.bottom;
    return Padding(
      padding: EdgeInsets.fromLTRB(16, 16, 16, 16 + bottom),
      child: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text(
              'Destek talebi',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 6),
            Text(
              '${widget.product.name} için bilgi talebiniz Seyfibaba destek ekibine iletilir.',
              style: const TextStyle(fontSize: 13, color: Color(0xFF6B7280)),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _name,
              decoration: const InputDecoration(labelText: 'Ad Soyad*'),
            ),
            TextField(
              controller: _phone,
              keyboardType: TextInputType.phone,
              decoration: const InputDecoration(labelText: 'Telefon*'),
            ),
            TextField(
              controller: _email,
              keyboardType: TextInputType.emailAddress,
              decoration: const InputDecoration(labelText: 'E-posta (opsiyonel)'),
            ),
            TextField(
              controller: _message,
              maxLines: 4,
              decoration: const InputDecoration(
                labelText: 'Mesaj',
                hintText: 'Ölçü, renk veya teslimat hakkında sorun',
              ),
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              height: 46,
              child: ElevatedButton(
                onPressed: _loading ? null : _submit,
                child: Text(_loading ? 'Gönderiliyor...' : 'Talebi gönder'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
