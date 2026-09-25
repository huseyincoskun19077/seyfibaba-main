import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_html/flutter_html.dart';
import 'package:http/http.dart' as http;

import '../../../core/remote_urls.dart';
import '../../../modules/authentication/controller/login/login_bloc.dart';
import '../../../utils/constants.dart';
import '../../../utils/language_string.dart';

class CheckoutConsentLink {
  final String slug;
  final String label;
  final String key;
  final String suffix;

  const CheckoutConsentLink({
    required this.slug,
    required this.label,
    required this.key,
    required this.suffix,
  });
}

class CheckoutLegalConsentCatalog {
  static const groups = [
    CheckoutConsentLink(
      key: 'checkout-pre-information',
      slug: 'pre-information',
      label: 'Ön Bilgilendirme Koşulları',
      suffix: "'nı okudum, onaylıyorum.",
    ),
    CheckoutConsentLink(
      key: 'checkout-distance-sales',
      slug: 'distance-sales',
      label: 'Ticari Nitelikli Mesafeli Satış Sözleşmesi',
      suffix: "'ni okudum, onaylıyorum.",
    ),
  ];

  static bool allAccepted(Map<String, bool> values) {
    for (final group in groups) {
      if (values[group.key] != true) return false;
    }
    return true;
  }
}

class CheckoutLegalConsentPanel extends StatefulWidget {
  const CheckoutLegalConsentPanel({
    super.key,
    required this.values,
    required this.onChanged,
    this.shippingAddressId,
    this.billingAddressId,
    this.shippingAddress,
    this.billingAddress,
    this.shippingCharge = 0,
    this.paymentMethod = '',
    this.guestItems,
    this.padding = const EdgeInsets.fromLTRB(20, 8, 20, 8),
  });

  final Map<String, bool> values;
  final void Function(String key, bool value) onChanged;
  final int? shippingAddressId;
  final int? billingAddressId;
  final Map<String, dynamic>? shippingAddress;
  final Map<String, dynamic>? billingAddress;
  final double shippingCharge;
  final String paymentMethod;
  final List<Map<String, dynamic>>? guestItems;
  final EdgeInsets padding;

  @override
  State<CheckoutLegalConsentPanel> createState() =>
      _CheckoutLegalConsentPanelState();
}

class _CheckoutLegalConsentPanelState extends State<CheckoutLegalConsentPanel> {
  Map<String, dynamic>? _docs;
  bool _loading = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void didUpdateWidget(covariant CheckoutLegalConsentPanel oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.shippingAddressId != widget.shippingAddressId ||
        oldWidget.billingAddressId != widget.billingAddressId ||
        oldWidget.shippingCharge != widget.shippingCharge ||
        oldWidget.paymentMethod != widget.paymentMethod) {
      _load();
    }
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final token =
          context.read<LoginBloc>().userInfo?.accessToken;
      final body = <String, dynamic>{
        if (widget.shippingAddressId != null)
          'shipping_address_id': widget.shippingAddressId,
        if (widget.billingAddressId != null)
          'billing_address_id': widget.billingAddressId,
        if (widget.shippingAddress != null)
          'shipping_address': widget.shippingAddress,
        if (widget.billingAddress != null)
          'billing_address': widget.billingAddress,
        'shipping_charge': widget.shippingCharge,
        if (widget.paymentMethod.isNotEmpty)
          'payment_method': widget.paymentMethod,
        if (token == null || token.isEmpty)
          'items': widget.guestItems ?? const [],
      };
      final headers = <String, String>{
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token',
      };
      final res = await http.post(
        Uri.parse(RemoteUrls.checkoutLegalContracts),
        headers: headers,
        body: jsonEncode(body),
      );
      final decoded = jsonDecode(res.body);
      if (res.statusCode >= 200 && res.statusCode < 300 && decoded is Map) {
        if (!mounted) return;
        setState(() {
          _docs = Map<String, dynamic>.from(decoded);
          _loading = false;
        });
        return;
      }
      throw Exception(
        decoded is Map
            ? '${decoded['message'] ?? 'Sözleşme yüklenemedi'}'
            : 'Sözleşme yüklenemedi',
      );
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = '$e';
      });
    }
  }

  Future<void> _openDoc(CheckoutConsentLink item) async {
    final raw = item.slug == 'distance-sales'
        ? (_docs?['distance_sales'])
        : (_docs?['pre_information']);
    final bundle = raw is Map ? Map<String, dynamic>.from(raw) : null;
    final html = '${bundle?['html'] ?? ''}';
    final title = '${bundle?['title'] ?? item.label}';

    final accepted = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
      ),
      builder: (ctx) {
        return DraggableScrollableSheet(
          expand: false,
          initialChildSize: 0.85,
          minChildSize: 0.5,
          maxChildSize: 0.95,
          builder: (_, controller) {
            return Column(
              children: [
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 14, 8, 8),
                  child: Row(
                    children: [
                      Expanded(
                        child: Text(
                          title,
                          style: const TextStyle(
                            fontWeight: FontWeight.w800,
                            fontSize: 16,
                          ),
                        ),
                      ),
                      IconButton(
                        onPressed: () => Navigator.pop(ctx, false),
                        icon: const Icon(Icons.close),
                      ),
                    ],
                  ),
                ),
                const Divider(height: 1),
                Expanded(
                  child: SingleChildScrollView(
                    controller: controller,
                    padding: const EdgeInsets.all(16),
                    child: Html(
                      data: html.isEmpty
                          ? '<p>Metin henüz hazır değil. Adres ve sepet bilgilerini kontrol edin.</p>'
                          : html,
                    ),
                  ),
                ),
                SafeArea(
                  top: false,
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
                    child: SizedBox(
                      width: double.infinity,
                      height: 48,
                      child: ElevatedButton(
                        onPressed: () => Navigator.pop(ctx, true),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFFFFBB38),
                          foregroundColor: blackColor,
                        ),
                        child: const Text(
                          'Okudum, onaylıyorum',
                          style: TextStyle(fontWeight: FontWeight.w800),
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            );
          },
        );
      },
    );
    if (accepted == true) {
      widget.onChanged(item.key, true);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: widget.padding,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            Language.checkoutLegalTitle,
            style: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w700,
              color: blackColor,
            ),
          ),
          if (_loading)
            const Padding(
              padding: EdgeInsets.only(top: 8),
              child: Text(
                'Sözleşmeler hazırlanıyor…',
                style: TextStyle(fontSize: 12, color: Color(0xFF64748B)),
              ),
            ),
          if (_error != null)
            Padding(
              padding: const EdgeInsets.only(top: 8),
              child: Row(
                children: [
                  Expanded(
                    child: Text(
                      _error!,
                      style: const TextStyle(fontSize: 12, color: Colors.red),
                    ),
                  ),
                  TextButton(onPressed: _load, child: const Text('Tekrar')),
                ],
              ),
            ),
          const SizedBox(height: 10),
          ...CheckoutLegalConsentCatalog.groups.map((group) {
            final checked = widget.values[group.key] == true;
            return Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: InkWell(
                onTap: () => widget.onChanged(group.key, !checked),
                borderRadius: BorderRadius.circular(12),
                child: Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: checked
                        ? const Color(0xFFF0FDF4)
                        : Colors.white,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                      color: checked
                          ? const Color(0xFF22C55E)
                          : borderColor,
                    ),
                  ),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Icon(
                        checked
                            ? Icons.check_box
                            : Icons.check_box_outline_blank,
                        color: checked ? const Color(0xFF16A34A) : paragraphColor,
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: RichText(
                          text: TextSpan(
                            style: const TextStyle(
                              fontSize: 13.5,
                              color: blackColor,
                              height: 1.35,
                            ),
                            children: [
                              WidgetSpan(
                                alignment: PlaceholderAlignment.baseline,
                                baseline: TextBaseline.alphabetic,
                                child: GestureDetector(
                                  onTap: () => _openDoc(group),
                                  child: Text(
                                    group.label,
                                    style: const TextStyle(
                                      fontWeight: FontWeight.w700,
                                      color: Color(0xFF04334A),
                                      decoration: TextDecoration.underline,
                                      fontSize: 13.5,
                                    ),
                                  ),
                                ),
                              ),
                              TextSpan(text: group.suffix),
                              const TextSpan(
                                text: ' *',
                                style: TextStyle(color: Colors.red),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            );
          }),
        ],
      ),
    );
  }
}
