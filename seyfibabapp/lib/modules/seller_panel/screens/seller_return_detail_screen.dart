import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../modules/authentication/controller/login/login_bloc.dart';
import '../../../modules/home/widgets/home_theme.dart';
import '../../../utils/utils.dart';
import '../models/seller_return_model.dart';
import '../services/seller_api_service.dart';

class SellerReturnDetailScreen extends StatefulWidget {
  const SellerReturnDetailScreen({super.key, required this.returnId});

  final int returnId;

  @override
  State<SellerReturnDetailScreen> createState() =>
      _SellerReturnDetailScreenState();
}

class _SellerReturnDetailScreenState extends State<SellerReturnDetailScreen> {
  final _service = SellerApiService();
  late Future<SellerReturnRequest> _future;

  final _addressCtrl = TextEditingController();
  final _carrierCtrl = TextEditingController();
  final _codeCtrl = TextEditingController();
  final _instructionsCtrl = TextEditingController();
  final _noteCtrl = TextEditingController();
  final _rejectCtrl = TextEditingController();
  final _receivedNoteCtrl = TextEditingController();
  String _payer = 'buyer';
  bool _formSeeded = false;
  bool _busy = false;
  /// Pending returns: show one form at a time (approve | reject).
  String? _pendingMode;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  @override
  void dispose() {
    _addressCtrl.dispose();
    _carrierCtrl.dispose();
    _codeCtrl.dispose();
    _instructionsCtrl.dispose();
    _noteCtrl.dispose();
    _rejectCtrl.dispose();
    _receivedNoteCtrl.dispose();
    super.dispose();
  }

  String get _token => context.read<LoginBloc>().userInfo!.accessToken;

  Future<SellerReturnRequest> _load() =>
      _service.fetchReturnRequest(_token, widget.returnId);

  Future<void> _refresh() async {
    _formSeeded = false;
    _pendingMode = null;
    final next = _load();
    if (!mounted) return;
    // Arrow form setState(() => _future = _load()) returns a Future and crashes.
    setState(() {
      _future = next;
    });
    try {
      await next;
    } catch (_) {
      // FutureBuilder shows the error; do not rethrow into action handlers.
    }
  }

  String _cleanError(Object e) {
    return e
        .toString()
        .replaceFirst('Exception: ', '')
        .replaceFirst('Error: ', '')
        .trim();
  }

  Future<bool> _ensureStillPending(SellerReturnRequest item) async {
    try {
      final fresh = await _service.fetchReturnRequest(_token, item.id);
      if (!fresh.isPending) {
        if (mounted) {
          Utils.errorSnackBar(
            context,
            'Bu talep artık bekleyen durumda değil (${fresh.statusLabel}). Sayfa yenileniyor.',
          );
          await _refresh();
        }
        return false;
      }
      return true;
    } catch (_) {
      return item.isPending;
    }
  }

  void _seedForm(SellerReturnRequest item) {
    if (_formSeeded) return;
    _formSeeded = true;
    _addressCtrl.text = item.defaultReturnAddress.trim().isNotEmpty
        ? item.defaultReturnAddress
        : item.returnAddress;
    _carrierCtrl.text = item.returnCarrierName;
    _codeCtrl.text = item.returnCargoCode;
    _instructionsCtrl.text = item.returnShippingInstructions;
    _noteCtrl.text = item.sellerNote;
    _receivedNoteCtrl.text = item.sellerNote;
    _payer = (item.defaultShippingPayer == 'seller' ||
            item.defaultShippingPayer == 'buyer')
        ? item.defaultShippingPayer
        : (item.reason == 'changed_mind' || item.reason == 'other'
            ? 'buyer'
            : 'seller');
  }

  Future<void> _approve(SellerReturnRequest item) async {
    if (_busy) return;
    final address = _addressCtrl.text.trim();
    if (address.length < 10) {
      Utils.errorSnackBar(context, 'İade adresi en az 10 karakter olmalıdır');
      return;
    }
    if (!await _ensureStillPending(item)) return;
    setState(() {
      _busy = true;
    });
    try {
      Utils.loadingDialog(context);
      final msg = await _service.approveReturnRequest(
        _token,
        item.id,
        sellerNote: _noteCtrl.text,
        returnAddress: address,
        returnShippingPayer: _payer,
        returnCarrierName: _carrierCtrl.text,
        returnCargoCode: _codeCtrl.text,
        returnShippingInstructions: _instructionsCtrl.text,
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.showSnackBar(
        context,
        msg.isNotEmpty
            ? msg
            : 'İade onaylandı. Müşteri iade adresi ve kargo talimatını görecek.',
      );
      await _refresh();
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, _cleanError(e));
      await _refresh();
    } finally {
      if (mounted) {
        setState(() {
          _busy = false;
        });
      }
    }
  }

  Future<void> _reject(SellerReturnRequest item) async {
    if (_busy) return;
    final reason = _rejectCtrl.text.trim();
    if (reason.length < 5) {
      Utils.errorSnackBar(context, 'Red gerekçesi en az 5 karakter olmalıdır');
      return;
    }
    if (!await _ensureStillPending(item)) return;
    setState(() {
      _busy = true;
    });
    try {
      Utils.loadingDialog(context);
      final msg = await _service.rejectReturnRequest(
        _token,
        item.id,
        reason: reason,
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.showSnackBar(
        context,
        msg.isNotEmpty
            ? msg
            : 'İade reddedildi. Talebiniz yöneticiye ve alıcıya iletildi.',
      );
      await _refresh();
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, _cleanError(e));
      await _refresh();
    } finally {
      if (mounted) {
        setState(() {
          _busy = false;
        });
      }
    }
  }

  Future<void> _markReceived(SellerReturnRequest item) async {
    if (_busy) return;
    if (!item.canMarkReceived) {
      Utils.errorSnackBar(
        context,
        'Bu talep için ürün teslim alındı işaretlenemez.',
      );
      await _refresh();
      return;
    }
    setState(() {
      _busy = true;
    });
    try {
      Utils.loadingDialog(context);
      final msg = await _service.markReturnReceived(
        token: _token,
        id: item.id,
        note: _receivedNoteCtrl.text.trim(),
      );
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.showSnackBar(
        context,
        msg.isNotEmpty
            ? msg
            : 'Ürün teslim alındı. Yönetici para iadesini tamamlayabilir.',
      );
      await _refresh();
    } catch (e) {
      if (!mounted) return;
      Utils.closeDialog(context);
      Utils.errorSnackBar(context, _cleanError(e));
      await _refresh();
    } finally {
      if (mounted) {
        setState(() {
          _busy = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: HomeTheme.bg,
      appBar: AppBar(
        title: const Text('İade Talebi Detayı'),
        backgroundColor: HomeTheme.header,
        foregroundColor: HomeTheme.textDark,
        elevation: 0,
      ),
      body: FutureBuilder<SellerReturnRequest>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.hasError) {
            return Center(child: Text('${snapshot.error}'));
          }
          final item = snapshot.data!;
          _seedForm(item);
          return RefreshIndicator(
            onRefresh: _refresh,
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                _summaryCard(item),
                const SizedBox(height: 12),
                _processCard(item),
                const SizedBox(height: 12),
                _messageCard(item),
                if (item.returnAddress.trim().isNotEmpty) ...[
                  const SizedBox(height: 12),
                  _logisticsCard(item),
                ],
                if (item.imageUrls.isNotEmpty) ...[
                  const SizedBox(height: 12),
                  _imagesCard(item),
                ],
                const SizedBox(height: 12),
                _actionsCard(item),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _summaryCard(SellerReturnRequest item) {
    return Container(
      decoration: HomeTheme.cardDecoration(),
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Expanded(
                child: Text(
                  'Talep Özeti',
                  style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16),
                ),
              ),
              Flexible(
                child: Text(
                  item.statusLabel,
                  textAlign: TextAlign.right,
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: HomeTheme.textMuted,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          _infoTile(
            'Müşteri',
            [
              item.customerName.isEmpty ? '-' : item.customerName,
              if (item.customerEmail.isNotEmpty) item.customerEmail,
              if (item.customerPhone.isNotEmpty) item.customerPhone,
            ].join('\n'),
          ),
          const SizedBox(height: 10),
          _infoTile(
            'Sipariş',
            '#${item.orderCode}\nAdet: ${item.qty}\nTalep No #${item.id}',
          ),
          const SizedBox(height: 10),
          _infoTile(
            'Kazanç etkisi (net)',
            '${Utils.formatPrice(item.sellerImpactNet > 0 ? item.sellerImpactNet : item.refundAmount, context)}\nKomisyon %${item.commissionRate.toStringAsFixed(item.commissionRate % 1 == 0 ? 0 : 1)} düşülmüş',
          ),
          const Divider(height: 24),
          Text(
            item.productName.isEmpty ? 'Ürün' : item.productName,
            style: const TextStyle(fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 4),
          Text(
            'İade nedeni: ${item.reasonLabel}',
            style: const TextStyle(fontSize: 13, color: HomeTheme.textMuted),
          ),
          const SizedBox(height: 4),
          const Text(
            'Müşteriye ödenen tutar satıcıya gösterilmez. İade tamamlanınca net pay kazançtan düşülür.',
            style: TextStyle(fontSize: 12, color: HomeTheme.textMuted),
          ),
        ],
      ),
    );
  }

  Widget _processCard(SellerReturnRequest item) {
    return Container(
      decoration: HomeTheme.cardDecoration(),
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Şu an süreçte neredesiniz?',
            style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15),
          ),
          const SizedBox(height: 10),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: item.status == 0
                  ? const Color(0xFFFFFBEB)
                  : item.status == 4
                      ? const Color(0xFFECFDF5)
                      : (item.status == 5 || item.status == 6)
                          ? const Color(0xFFFEF2F2)
                          : const Color(0xFFEFF6FF),
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: HomeTheme.border),
            ),
            child: Text(
              item.processHint,
              style: const TextStyle(fontSize: 13, height: 1.4),
            ),
          ),
          if (item.isPending) ...[
            const SizedBox(height: 12),
            _step('1', 'Müşteri mesajını ve kanıt fotoğraflarını kontrol edin.'),
            _step('2', 'Uygunsa onaylayın. Değilse net bir red nedeni yazın.'),
            _step(
              '3',
              'Onaylarsanız müşteri kargolar; ürün gelince “Ürünü aldım” dersiniz.',
            ),
          ],
        ],
      ),
    );
  }

  Widget _step(String num, String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CircleAvatar(
            radius: 12,
            backgroundColor: HomeTheme.brandYellow,
            child: Text(
              num,
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w800,
                color: HomeTheme.textDark,
              ),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(text, style: const TextStyle(fontSize: 13, height: 1.35)),
          ),
        ],
      ),
    );
  }

  Widget _messageCard(SellerReturnRequest item) {
    return Container(
      decoration: HomeTheme.cardDecoration(),
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Müşteri Mesajı',
            style: TextStyle(fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 6),
          Text(
            item.details.trim().isEmpty
                ? 'Müşteri ek açıklama yazmadı.'
                : item.details,
            style: const TextStyle(color: HomeTheme.textMuted, height: 1.4),
          ),
          const SizedBox(height: 14),
          const Text(
            'Karar Notları',
            style: TextStyle(fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 6),
          Text(
            'Satıcı notu:\n${item.sellerNote.trim().isEmpty ? 'Henüz satıcı notu yok.' : item.sellerNote}',
            style: const TextStyle(fontSize: 13, height: 1.4),
          ),
          const SizedBox(height: 8),
          Text(
            'Yönetici notu:\n${item.adminNote.trim().isEmpty ? 'Yönetici henüz not yazmadı.' : item.adminNote}',
            style: const TextStyle(fontSize: 13, height: 1.4),
          ),
          if ((item.status == 5 || item.status == 6) &&
              item.rejectedReason.trim().isNotEmpty) ...[
            const SizedBox(height: 10),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: const Color(0xFFFEF2F2),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                'Red gerekçesi (müşteriye görünür):\n${item.rejectedReason}',
                style: const TextStyle(
                  fontSize: 13,
                  height: 1.4,
                  color: Color(0xFF991B1B),
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _logisticsCard(SellerReturnRequest item) {
    return Container(
      decoration: HomeTheme.cardDecoration(),
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'İade kargo bilgileri',
            style: TextStyle(fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 8),
          Text(
            'İade adresi:\n${item.returnAddress}',
            style: const TextStyle(fontSize: 13, height: 1.4),
          ),
          const SizedBox(height: 8),
          Text(
            'Kargo ücreti: ${item.returnShippingPayerLabel}',
            style: const TextStyle(fontSize: 13),
          ),
          if (item.returnCarrierName.trim().isNotEmpty) ...[
            const SizedBox(height: 6),
            Text(
              'Önerilen kargo: ${item.returnCarrierName}',
              style: const TextStyle(fontSize: 13),
            ),
          ],
          if (item.returnCargoCode.trim().isNotEmpty) ...[
            const SizedBox(height: 6),
            Text(
              'İade / anlaşmalı kod: ${item.returnCargoCode}',
              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700),
            ),
          ],
          if (item.returnShippingInstructions.trim().isNotEmpty) ...[
            const SizedBox(height: 6),
            Text(
              'Talimat:\n${item.returnShippingInstructions}',
              style: const TextStyle(fontSize: 13, height: 1.4),
            ),
          ],
        ],
      ),
    );
  }

  Widget _imagesCard(SellerReturnRequest item) {
    return Container(
      decoration: HomeTheme.cardDecoration(),
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Kanıt Görselleri',
            style: TextStyle(fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 10),
          SizedBox(
            height: 96,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              itemCount: item.imageUrls.length,
              separatorBuilder: (_, __) => const SizedBox(width: 8),
              itemBuilder: (context, index) {
                final url = item.imageUrls[index];
                return ClipRRect(
                  borderRadius: BorderRadius.circular(8),
                  child: Image.network(
                    url,
                    width: 96,
                    height: 96,
                    fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) => Container(
                      width: 96,
                      height: 96,
                      color: HomeTheme.border,
                      alignment: Alignment.center,
                      child: const Icon(Icons.broken_image_outlined),
                    ),
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _actionsCard(SellerReturnRequest item) {
    return Container(
      decoration: HomeTheme.cardDecoration(),
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Satıcı İşlemleri',
            style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15),
          ),
          const SizedBox(height: 12),
          if (item.isPending) ...[
            if (_pendingMode == null) ...[
              const Text(
                'Bu talep için ne yapmak istiyorsunuz?',
                style: TextStyle(fontSize: 13, color: HomeTheme.textMuted),
              ),
              const SizedBox(height: 12),
              SizedBox(
                width: double.infinity,
                child: FilledButton(
                  onPressed: _busy
                      ? null
                      : () => setState(() => _pendingMode = 'approve'),
                  style: FilledButton.styleFrom(
                    backgroundColor: HomeTheme.brandYellow,
                    foregroundColor: HomeTheme.textDark,
                    minimumSize: const Size.fromHeight(48),
                  ),
                  child: const Text('Onaylamak istiyorum'),
                ),
              ),
              const SizedBox(height: 10),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton(
                  onPressed: _busy
                      ? null
                      : () => setState(() => _pendingMode = 'reject'),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: const Color(0xFFB91C1C),
                    side: const BorderSide(color: Color(0xFFFECACA)),
                    minimumSize: const Size.fromHeight(48),
                  ),
                  child: const Text('Reddetmek istiyorum'),
                ),
              ),
            ] else if (_pendingMode == 'approve') ...[
              Row(
                children: [
                  const Expanded(
                    child: Text(
                      'Talebi Onayla',
                      style: TextStyle(fontWeight: FontWeight.w700),
                    ),
                  ),
                  TextButton(
                    onPressed: _busy
                        ? null
                        : () => setState(() => _pendingMode = null),
                    child: const Text('Geri'),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              TextField(
                controller: _addressCtrl,
                maxLines: 4,
                decoration: const InputDecoration(
                  labelText: 'İade adresi *',
                  border: OutlineInputBorder(),
                  alignLabelWithHint: true,
                ),
              ),
              const SizedBox(height: 10),
              DropdownButtonFormField<String>(
                value: _payer,
                decoration: const InputDecoration(
                  labelText: 'İade kargo ücretini kim karşılar? *',
                  border: OutlineInputBorder(),
                ),
                items: const [
                  DropdownMenuItem(
                    value: 'seller',
                    child: Text('Satıcı karşılar'),
                  ),
                  DropdownMenuItem(
                    value: 'buyer',
                    child: Text('Alıcı karşılar'),
                  ),
                ],
                onChanged: _busy
                    ? null
                    : (v) {
                        if (v != null) setState(() => _payer = v);
                      },
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _carrierCtrl,
                decoration: const InputDecoration(
                  labelText: 'Anlaşmalı kargo firması (opsiyonel)',
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _codeCtrl,
                decoration: const InputDecoration(
                  labelText: 'İade / anlaşmalı kod (opsiyonel)',
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _instructionsCtrl,
                maxLines: 3,
                decoration: const InputDecoration(
                  labelText: 'Kargo talimatı (opsiyonel)',
                  border: OutlineInputBorder(),
                  alignLabelWithHint: true,
                ),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _noteCtrl,
                maxLines: 2,
                decoration: const InputDecoration(
                  labelText: 'Onay notu (opsiyonel)',
                  border: OutlineInputBorder(),
                  alignLabelWithHint: true,
                ),
              ),
              const SizedBox(height: 12),
              SizedBox(
                width: double.infinity,
                child: FilledButton(
                  onPressed: _busy ? null : () => _approve(item),
                  style: FilledButton.styleFrom(
                    backgroundColor: HomeTheme.brandYellow,
                    foregroundColor: HomeTheme.textDark,
                    minimumSize: const Size.fromHeight(48),
                  ),
                  child: const Text('Talebi Onayla'),
                ),
              ),
            ] else ...[
              Row(
                children: [
                  const Expanded(
                    child: Text(
                      'Talebi Reddet',
                      style: TextStyle(fontWeight: FontWeight.w700),
                    ),
                  ),
                  TextButton(
                    onPressed: _busy
                        ? null
                        : () => setState(() => _pendingMode = null),
                    child: const Text('Geri'),
                  ),
                ],
              ),
              const SizedBox(height: 4),
              const Text(
                'Reddederseniz yazdığınız gerekçe müşteriye gösterilir. Yalnızca bekleyen talepler reddedilebilir.',
                style: TextStyle(fontSize: 12, color: HomeTheme.textMuted),
              ),
              const SizedBox(height: 8),
              TextField(
                controller: _rejectCtrl,
                maxLines: 3,
                decoration: const InputDecoration(
                  labelText: 'Red gerekçesi *',
                  border: OutlineInputBorder(),
                  alignLabelWithHint: true,
                ),
              ),
              const SizedBox(height: 12),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton(
                  onPressed: _busy ? null : () => _reject(item),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: const Color(0xFFB91C1C),
                    side: const BorderSide(color: Color(0xFFFECACA)),
                    minimumSize: const Size.fromHeight(48),
                  ),
                  child: const Text('Talebi Reddet'),
                ),
              ),
            ],
          ] else if (item.canMarkReceived) ...[
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: const Color(0xFFFFFBEB),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: const Color(0xFFFDE68A)),
              ),
              child: const Text(
                'Müşteri ürünü iade adresinize gönderdiğinde, kargoyu açıp kontrol ettikten sonra aşağıdaki butona basın.',
                style: TextStyle(fontSize: 13, height: 1.4),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _receivedNoteCtrl,
              maxLines: 3,
              decoration: const InputDecoration(
                labelText: 'Not (opsiyonel)',
                border: OutlineInputBorder(),
                hintText: 'Örn: Ürün kutusuyla geldi, hasar yok.',
                alignLabelWithHint: true,
              ),
            ),
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: _busy ? null : () => _markReceived(item),
                style: FilledButton.styleFrom(
                  backgroundColor: const Color(0xFF059669),
                  foregroundColor: Colors.white,
                  minimumSize: const Size.fromHeight(48),
                ),
                child: const Text('Ürünü aldım'),
              ),
            ),
          ] else ...[
            Text(
              'Bu talep artık satıcı panelinden işleme alınamaz. Güncel durum: ${item.statusLabel}',
              style: const TextStyle(
                fontSize: 13,
                color: HomeTheme.textMuted,
                height: 1.4,
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _infoTile(String title, String body) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        border: Border.all(color: HomeTheme.border),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              fontSize: 11,
              color: HomeTheme.textMuted,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 4),
          Text(body, style: const TextStyle(fontSize: 13, height: 1.35)),
        ],
      ),
    );
  }
}
