import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:image_picker/image_picker.dart';

import '../../../core/error/exception.dart';
import '../../../modules/authentication/controller/login/login_bloc.dart';
import '../../../modules/home/widgets/home_theme.dart';
import '../../../utils/utils.dart';
import '../model/buyer_return_model.dart';
import '../model/product_order_model.dart';
import '../services/buyer_return_service.dart';

class CreateReturnArgs {
  const CreateReturnArgs({
    required this.orderItem,
    required this.orderCode,
    required this.returnable,
  });

  final OrderedProductModel orderItem;
  final String orderCode;
  final BuyerReturnableItem returnable;
}

class CreateReturnScreen extends StatefulWidget {
  const CreateReturnScreen({super.key, required this.args});

  final CreateReturnArgs args;

  @override
  State<CreateReturnScreen> createState() => _CreateReturnScreenState();
}

class _CreateReturnScreenState extends State<CreateReturnScreen> {
  final _service = BuyerReturnService();
  final _detailsController = TextEditingController();
  final _picker = ImagePicker();

  static const _reasons = <MapEntry<String, String>>[
    MapEntry('defective', 'Arızalı ürün'),
    MapEntry('wrong_item', 'Yanlış ürün geldi'),
    MapEntry('not_as_described', 'Açıklamadaki gibi değil'),
    MapEntry('changed_mind', 'Kararım değişti'),
    MapEntry('damaged_in_shipping', 'Kargoda hasar gördü'),
    MapEntry('other', 'Diğer'),
  ];

  static const _imageRequired = {
    'defective',
    'damaged_in_shipping',
    'wrong_item',
  };

  String? _reason;
  int _qty = 1;
  final List<XFile> _images = [];
  bool _submitting = false;

  @override
  void initState() {
    super.initState();
    final maxQty = widget.args.returnable.maxReturnableQty;
    _qty = maxQty > 0 ? 1 : 1;
  }

  @override
  void dispose() {
    _detailsController.dispose();
    super.dispose();
  }

  Future<void> _pickImages() async {
    final files = await _picker.pickMultiImage(imageQuality: 85);
    if (files.isEmpty) return;
    setState(() {
      _images
        ..clear()
        ..addAll(files.take(3));
    });
  }

  Future<void> _submit() async {
    if (_reason == null || _reason!.isEmpty) {
      Utils.errorSnackBar(context, 'Lütfen iade nedeni seçin');
      return;
    }
    if (_imageRequired.contains(_reason) && _images.isEmpty) {
      Utils.errorSnackBar(context, 'Bu neden için en az bir fotoğraf gerekli');
      return;
    }
    if (_submitting) return;

    setState(() => _submitting = true);
    try {
      final token = context.read<LoginBloc>().userInfo!.accessToken;
      final message = await _service.createReturnRequest(
        token: token,
        orderId: widget.args.orderItem.orderId,
        orderProductId: widget.args.orderItem.id,
        reason: _reason!,
        details: _detailsController.text.trim(),
        qty: _qty,
        images: _images.map((e) => File(e.path)).toList(),
      );
      if (!mounted) return;
      Utils.showSnackBar(context, message.isNotEmpty ? message : 'İade talebi alındı');
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      var msg = e.toString().replaceFirst('Exception: ', '');
      if (e is InvalidInputException) {
        final list = e.errors.message;
        if (list.isNotEmpty) msg = list.first;
      }
      if (msg.contains('active return request') ||
          msg.contains('already exists')) {
        msg =
            'Bu ürün için zaten bekleyen bir iade talebiniz var. İade Taleplerim’den iptal edebilirsiniz.';
      }
      Utils.errorSnackBar(context, msg);
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final maxQty = widget.args.returnable.maxReturnableQty.clamp(1, 99);
    final paid = widget.args.returnable.paidUnitPrice > 0
        ? widget.args.returnable.paidUnitPrice
        : widget.args.returnable.unitPrice;
    final hintQty = maxQty;
    final scale = _qty / hintQty;
    final lineGross = widget.args.returnable.unitPrice * _qty;
    final couponPart = widget.args.returnable.couponShare * scale;
    final bankPart = widget.args.returnable.bankDiscountShare * scale;
    final estimatedFromParts = (lineGross - couponPart - bankPart) < 0
        ? 0.0
        : lineGross - couponPart - bankPart;
    final estimatedFromApi = widget.args.returnable.suggestedRefund > 0
        ? widget.args.returnable.suggestedRefund * scale
        : paid * _qty;
    final estimatedRefund = (bankPart > 0.009 &&
            estimatedFromApi + 0.05 < estimatedFromParts)
        ? estimatedFromParts
        : estimatedFromApi;

    return Scaffold(
      backgroundColor: HomeTheme.bg,
      appBar: AppBar(
        title: const Text('İade Talebi Oluştur'),
        backgroundColor: HomeTheme.header,
        foregroundColor: HomeTheme.textDark,
        elevation: 0,
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 32),
        children: [
          Text(
            widget.args.orderItem.productName,
            style: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w700,
              color: HomeTheme.textDark,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'Sipariş: ${widget.args.orderCode}',
            style: const TextStyle(fontSize: 12, color: HomeTheme.textMuted),
          ),
          const SizedBox(height: 16),
          const Text(
            'İade nedeni',
            style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13),
          ),
          const SizedBox(height: 8),
          DropdownButtonFormField<String>(
            value: _reason,
            decoration: InputDecoration(
              filled: true,
              fillColor: Colors.white,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
            hint: const Text('Bir neden seçin'),
            items: _reasons
                .map(
                  (e) => DropdownMenuItem(value: e.key, child: Text(e.value)),
                )
                .toList(),
            onChanged: (v) => setState(() => _reason = v),
          ),
          const SizedBox(height: 16),
          const Text(
            'Adet',
            style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13),
          ),
          const SizedBox(height: 8),
          DropdownButtonFormField<int>(
            value: _qty.clamp(1, maxQty),
            decoration: InputDecoration(
              filled: true,
              fillColor: Colors.white,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
            items: List.generate(
              maxQty,
              (i) => DropdownMenuItem(value: i + 1, child: Text('${i + 1}')),
            ),
            onChanged: (v) {
              if (v != null) setState(() => _qty = v);
            },
          ),
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: HomeTheme.border),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Ürün tutarı: ${Utils.formatPrice(lineGross, context)}',
                  style: const TextStyle(fontSize: 13),
                ),
                if (couponPart > 0.009) ...[
                  const SizedBox(height: 4),
                  Text(
                    'Kupon payı: − ${Utils.formatPrice(couponPart, context)}',
                    style: const TextStyle(
                      fontSize: 13,
                      color: HomeTheme.textMuted,
                    ),
                  ),
                ],
                if (bankPart > 0.009 ||
                    (widget.args.returnable.isBankPayment &&
                        lineGross - estimatedRefund > 0.009)) ...[
                  const SizedBox(height: 4),
                  Text(
                    'Havale indirimi (~%3): − ${Utils.formatPrice(bankPart > 0.009 ? bankPart : (lineGross - couponPart - estimatedRefund < 0 ? 0 : lineGross - couponPart - estimatedRefund), context)}',
                    style: const TextStyle(
                      fontSize: 13,
                      color: HomeTheme.textMuted,
                    ),
                  ),
                ],
                const SizedBox(height: 6),
                Text(
                  'Tahmini iade: ${Utils.formatPrice(estimatedRefund, context)}',
                  style: const TextStyle(
                    fontWeight: FontWeight.w700,
                    color: HomeTheme.textDark,
                  ),
                ),
                if (widget.args.returnable.isBankPayment || bankPart > 0.009) ...[
                  const SizedBox(height: 6),
                  const Text(
                    'Havale siparişlerinde ~%3 indirim iade tutarından düşülür; ödediğiniz kadar iade edilir.',
                    style: TextStyle(fontSize: 11, color: HomeTheme.textMuted),
                  ),
                ],
              ],
            ),
          ),
          const SizedBox(height: 16),
          const Text(
            'Açıklama',
            style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13),
          ),
          const SizedBox(height: 8),
          TextField(
            controller: _detailsController,
            maxLines: 4,
            decoration: InputDecoration(
              hintText: 'Sorunu kısaca anlatın',
              filled: true,
              fillColor: Colors.white,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
          const SizedBox(height: 16),
          const Text(
            'Kanıt fotoğrafları',
            style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13),
          ),
          const SizedBox(height: 8),
          OutlinedButton.icon(
            onPressed: _pickImages,
            icon: const Icon(Icons.photo_library_outlined),
            label: Text(
              _images.isEmpty
                  ? 'Fotoğraf seç (max 3)'
                  : '${_images.length} fotoğraf seçildi',
            ),
          ),
          const SizedBox(height: 24),
          SizedBox(
            height: 48,
            child: ElevatedButton(
              onPressed: _submitting ? null : _submit,
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFFEF262C),
                foregroundColor: Colors.white,
                elevation: 0,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
              child: Text(
                _submitting ? 'Gönderiliyor...' : 'Talebi Oluştur',
                style: const TextStyle(fontWeight: FontWeight.w800),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
