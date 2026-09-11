import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../modules/authentication/controller/login/login_bloc.dart';
import '../../../modules/home/widgets/home_theme.dart';
import '../../../utils/utils.dart';
import '../services/seller_api_service.dart';

/// Barkod kataloğundan ürün ekleme — fiyat / stok / indirim satıcıdan.
class SellerBarcodeProductScreen extends StatefulWidget {
  const SellerBarcodeProductScreen({super.key});

  @override
  State<SellerBarcodeProductScreen> createState() =>
      _SellerBarcodeProductScreenState();
}

class _SellerBarcodeProductScreenState extends State<SellerBarcodeProductScreen> {
  final _service = SellerApiService();
  final _barcodeCtrl = TextEditingController();
  final _priceCtrl = TextEditingController();
  final _qtyCtrl = TextEditingController(text: '1');
  final _offerCtrl = TextEditingController();

  Map<String, dynamic>? _catalog;
  Map<String, dynamic>? _alreadyOwned;
  bool _looking = false;
  bool _submitting = false;
  String? _message;
  bool _messageError = false;

  String get _token => context.read<LoginBloc>().userInfo!.accessToken;

  @override
  void dispose() {
    _barcodeCtrl.dispose();
    _priceCtrl.dispose();
    _qtyCtrl.dispose();
    _offerCtrl.dispose();
    super.dispose();
  }

  Future<void> _lookup() async {
    final code = _barcodeCtrl.text.replaceAll(RegExp(r'\s+'), '');
    if (code.isEmpty) {
      setState(() {
        _message = 'Barkod girin veya kamerayla okutup yapıştırın.';
        _messageError = true;
        _catalog = null;
      });
      return;
    }

    setState(() {
      _looking = true;
      _message = 'Aranıyor…';
      _messageError = false;
      _catalog = null;
      _alreadyOwned = null;
    });

    try {
      final res = await _service.lookupBarcodeProduct(
        token: _token,
        barcode: code,
      );
      if (!mounted) return;
      if (res['success'] != true) {
        setState(() {
          _looking = false;
          _message = '${res['message'] ?? 'Katalogda yok'}';
          _messageError = true;
        });
        return;
      }
      setState(() {
        _looking = false;
        _catalog = Map<String, dynamic>.from(res['catalog'] as Map);
        _alreadyOwned = res['already_owned'] is Map
            ? Map<String, dynamic>.from(res['already_owned'] as Map)
            : null;
        _barcodeCtrl.text = '${_catalog!['barcode'] ?? code}';
        _message = 'Katalog bulundu. Fiyat ve stok girin.';
        _messageError = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _looking = false;
        _message = '$e';
        _messageError = true;
      });
    }
  }

  Future<void> _submit() async {
    if (_catalog == null) {
      Utils.errorSnackBar(context, 'Önce barkodu bulun.');
      return;
    }
    final price = double.tryParse(_priceCtrl.text.trim().replaceAll(',', '.'));
    final qty = int.tryParse(_qtyCtrl.text.trim()) ?? -1;
    final offerRaw = _offerCtrl.text.trim();
    final offer = offerRaw.isEmpty
        ? null
        : double.tryParse(offerRaw.replaceAll(',', '.'));

    if (price == null || price <= 0) {
      Utils.errorSnackBar(context, 'Geçerli satış fiyatı girin.');
      return;
    }
    if (qty < 0) {
      Utils.errorSnackBar(context, 'Geçerli stok girin.');
      return;
    }

    setState(() => _submitting = true);
    try {
      await _service.createBarcodeProduct(
        token: _token,
        barcode: '${_catalog!['barcode']}',
        price: price,
        quantity: qty,
        offerPrice: offer,
      );
      if (!mounted) return;
      Utils.showSnackBar(context, 'Ürün eklendi');
      Navigator.pop(context, true);
    } catch (e) {
      if (!mounted) return;
      Utils.errorSnackBar(context, '$e');
      setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final thumb = '${_catalog?['thumb_url'] ?? ''}';
    final hasImage = _catalog?['has_image'] == true && thumb.isNotEmpty;

    return Scaffold(
      backgroundColor: HomeTheme.bg,
      appBar: AppBar(
        title: const Text('Barkod ile ekle'),
        backgroundColor: HomeTheme.header,
        foregroundColor: HomeTheme.textDark,
        elevation: 0,
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 32),
        children: [
          Text(
            'Barkodu yazın veya telefon kamerasıyla okutup buraya yapıştırın. '
            'İsim, görsel ve kategori katalogdan gelir; siz sadece fiyat / stok girersiniz.',
            style: TextStyle(color: HomeTheme.textMuted, height: 1.4),
          ),
          const SizedBox(height: 16),
          TextField(
            controller: _barcodeCtrl,
            keyboardType: TextInputType.number,
            textInputAction: TextInputAction.search,
            onSubmitted: (_) => _lookup(),
            decoration: InputDecoration(
              labelText: 'Barkod',
              hintText: 'Örn. 4045787020083',
              filled: true,
              fillColor: HomeTheme.header,
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              suffixIcon: IconButton(
                onPressed: _looking ? null : _lookup,
                icon: _looking
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.search),
              ),
            ),
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: _looking ? null : _lookup,
                  icon: const Icon(Icons.qr_code_scanner_rounded),
                  label: const Text('Barkodu bul'),
                ),
              ),
            ],
          ),
          if (_message != null) ...[
            const SizedBox(height: 10),
            Text(
              _message!,
              style: TextStyle(
                color: _messageError ? Colors.red.shade700 : HomeTheme.textMuted,
              ),
            ),
          ],
          if (_catalog != null) ...[
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: HomeTheme.header,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: HomeTheme.border),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (hasImage)
                    ClipRRect(
                      borderRadius: BorderRadius.circular(10),
                      child: CachedNetworkImage(
                        imageUrl: thumb,
                        width: 88,
                        height: 88,
                        fit: BoxFit.cover,
                      ),
                    )
                  else
                    Container(
                      width: 88,
                      height: 88,
                      alignment: Alignment.center,
                      decoration: BoxDecoration(
                        color: HomeTheme.border.withValues(alpha: 0.4),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: const Icon(Icons.image_not_supported_outlined),
                    ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          '${_catalog!['name'] ?? ''}',
                          style: const TextStyle(
                            fontWeight: FontWeight.w700,
                            fontSize: 15,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'Barkod: ${_catalog!['barcode']}',
                          style: TextStyle(
                            color: HomeTheme.textMuted,
                            fontSize: 12,
                          ),
                        ),
                        if ('${_catalog!['category'] ?? ''}'.isNotEmpty)
                          Text(
                            '${_catalog!['category']}',
                            style: TextStyle(
                              color: HomeTheme.textMuted,
                              fontSize: 12,
                            ),
                          ),
                        if (_alreadyOwned != null) ...[
                          const SizedBox(height: 6),
                          Text(
                            'Bu barkod ürünlerinizde var: ${_alreadyOwned!['name']} (yine ekleyebilirsiniz)',
                            style: TextStyle(
                              color: Colors.orange.shade800,
                              fontSize: 12,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: _priceCtrl,
              keyboardType:
                  const TextInputType.numberWithOptions(decimal: true),
              decoration: InputDecoration(
                labelText: 'Satış fiyatı (₺) *',
                filled: true,
                fillColor: Colors.white,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _qtyCtrl,
              keyboardType: TextInputType.number,
              decoration: InputDecoration(
                labelText: 'Stok *',
                filled: true,
                fillColor: Colors.white,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _offerCtrl,
              keyboardType:
                  const TextInputType.numberWithOptions(decimal: true),
              decoration: InputDecoration(
                labelText: 'İndirimli fiyat (opsiyonel)',
                filled: true,
                fillColor: Colors.white,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
            const SizedBox(height: 20),
            SizedBox(
              width: double.infinity,
              height: 48,
              child: FilledButton(
                onPressed: _submitting ? null : _submit,
                child: _submitting
                    ? const SizedBox(
                        width: 22,
                        height: 22,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Ürünü yayınla'),
              ),
            ),
          ],
        ],
      ),
    );
  }
}
