import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../utils/constants.dart';
import '../../../utils/language_string.dart';
import '../../../utils/utils.dart';
import '../../home/widgets/home_theme.dart';
import '../model/order_address_model.dart';
import '../model/order_model.dart';

class OrderSummaryPanel extends StatelessWidget {
  const OrderSummaryPanel({super.key, required this.order});

  final OrderModel order;

  String _paymentMethodLabel() {
    final method = order.paymentMethod.toLowerCase();
    // Havale siparişlerinde cash_on_delivery yanlışlıkla 1 kaydedilmiş olabilir
    if (method.contains('bank')) return Language.bankPayment;
    if (method.contains('cash') || order.cashOnDelivery == 1) {
      return Language.cashOnDelivery;
    }
    if (method.contains('iyzico')) return 'iyzico';
    if (method.isEmpty) return '-';
    return order.paymentMethod;
  }

  bool get _isBankPayment =>
      order.paymentMethod.toLowerCase().contains('bank');

  ({Color bg, Color fg, String label}) _paymentBadge() {
    if (order.paymentStatus == 1) {
      return (
        bg: const Color(0xFFECFDF3),
        fg: greenColor,
        label: Language.paymentPaid,
      );
    }
    return (
      bg: const Color(0xFFFFF6E5),
      fg: const Color(0xFFB45309),
      label: Language.paymentPending,
    );
  }

  @override
  Widget build(BuildContext context) {
    final payment = _paymentBadge();
    final address = order.orderAddress;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _SectionCard(
          title: Language.orderSummary,
          child: Column(
            children: [
              _SummaryRow(
                label: Language.subTotal,
                value: Utils.formatPrice(
                  order.totalAmount - order.shippingCost + order.couponCoast,
                  context,
                ),
              ),
              if (order.shippingCost > 0)
                _SummaryRow(
                  label: Language.shippingCost,
                  value: Utils.formatPrice(order.shippingCost, context),
                ),
              if (order.couponCoast > 0)
                _SummaryRow(
                  label: Language.discountCoupon,
                  value: '- ${Utils.formatPrice(order.couponCoast, context)}',
                  valueColor: greenColor,
                ),
              const Divider(height: 20),
              _SummaryRow(
                label: Language.totalAmount,
                value: Utils.formatPrice(order.totalAmount, context),
                bold: true,
              ),
            ],
          ),
        ),
        const SizedBox(height: 12),
        _SectionCard(
          title: Language.paymentMethod,
          trailing: Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            decoration: BoxDecoration(
              color: payment.bg,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Text(
              payment.label,
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w700,
                color: payment.fg,
              ),
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                _paymentMethodLabel(),
                style: const TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                  color: HomeTheme.textDark,
                ),
              ),
              if (_isBankPayment && order.paymentStatus != 1) ...[
                const SizedBox(height: 8),
                Text(
                  Language.bankPaymentPendingNote,
                  style: const TextStyle(
                    fontSize: 13,
                    height: 1.4,
                    color: HomeTheme.textMuted,
                  ),
                ),
              ],
            ],
          ),
        ),
        if (order.shippingMethod.isNotEmpty) ...[
          const SizedBox(height: 12),
          _SectionCard(
            title: Language.shippingMethod,
            child: Text(
              order.shippingMethod,
              style: const TextStyle(
                fontSize: 14,
                color: HomeTheme.textDark,
              ),
            ),
          ),
        ],
        if (address != null) ...[
          const SizedBox(height: 12),
          _SectionCard(
            title: Language.shippingAddress,
            child: _AddressBlock(address: address),
          ),
        ],
      ],
    );
  }
}

class _SectionCard extends StatefulWidget {
  const _SectionCard({
    required this.title,
    required this.child,
    this.trailing,
  });

  final String title;
  final Widget child;
  final Widget? trailing;

  @override
  State<_SectionCard> createState() => _SectionCardState();
}

class _SectionCardState extends State<_SectionCard> {
  bool _expanded = true;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: HomeTheme.cardDecoration(),
      clipBehavior: Clip.antiAlias,
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () => setState(() => _expanded = !_expanded),
          child: Padding(
            padding: const EdgeInsets.fromLTRB(14, 12, 14, 12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        widget.title,
                        style: const TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.w700,
                          color: HomeTheme.textDark,
                        ),
                      ),
                    ),
                    if (widget.trailing != null) widget.trailing!,
                    const SizedBox(width: 4),
                    Icon(
                      _expanded
                          ? Icons.keyboard_arrow_up_rounded
                          : Icons.keyboard_arrow_down_rounded,
                      color: HomeTheme.textMuted,
                      size: 22,
                    ),
                  ],
                ),
                if (_expanded) ...[
                  const SizedBox(height: 12),
                  widget.child,
                ],
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _SummaryRow extends StatelessWidget {
  const _SummaryRow({
    required this.label,
    required this.value,
    this.bold = false,
    this.valueColor,
  });

  final String label;
  final String value;
  final bool bold;
  final Color? valueColor;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: bold ? 14 : 13,
              fontWeight: bold ? FontWeight.w600 : FontWeight.w400,
              color: bold ? HomeTheme.textDark : HomeTheme.textMuted,
            ),
          ),
          Text(
            value,
            style: TextStyle(
              fontSize: bold ? 15 : 13,
              fontWeight: bold ? FontWeight.w800 : FontWeight.w600,
              color: valueColor ?? HomeTheme.textDark,
            ),
          ),
        ],
      ),
    );
  }
}

class _AddressBlock extends StatelessWidget {
  const _AddressBlock({required this.address});

  final OrderAddressModel address;

  @override
  Widget build(BuildContext context) {
    final lines = <String>[
      if (address.shippingName.isNotEmpty) address.shippingName,
      if (address.shippingPhone.isNotEmpty) address.shippingPhone,
      if (address.shippingAddress.isNotEmpty) address.shippingAddress,
      [
        address.shippingCity,
        address.shippingState,
        address.shippingCountry,
      ].where((e) => e.isNotEmpty).join(', '),
    ].where((e) => e.isNotEmpty).toList();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: lines
          .map(
            (line) => Padding(
              padding: const EdgeInsets.only(bottom: 4),
              child: Text(
                line,
                style: const TextStyle(
                  fontSize: 13,
                  height: 1.45,
                  color: HomeTheme.textDark,
                ),
              ),
            ),
          )
          .toList(),
    );
  }
}

class OrderIdHeader extends StatelessWidget {
  const OrderIdHeader({
    super.key,
    required this.orderId,
    required this.createdAt,
    required this.statusLabel,
    required this.statusBg,
    required this.statusFg,
  });

  final String orderId;
  final String createdAt;
  final String statusLabel;
  final Color statusBg;
  final Color statusFg;

  Future<void> _copy(BuildContext context) async {
    await Clipboard.setData(ClipboardData(text: orderId));
    if (context.mounted) {
      Utils.showSnackBar(context, Language.copiedToClipboard);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: HomeTheme.cardDecoration(),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Text(
                      '#$orderId',
                      style: const TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.w800,
                        color: HomeTheme.textDark,
                      ),
                    ),
                    const SizedBox(width: 6),
                    InkWell(
                      onTap: () => _copy(context),
                      borderRadius: BorderRadius.circular(6),
                      child: Padding(
                        padding: const EdgeInsets.all(4),
                        child: Icon(
                          Icons.copy_rounded,
                          size: 16,
                          color: HomeTheme.textMuted.withValues(alpha: 0.9),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                Text(
                  Utils.formatDate(createdAt),
                  style: const TextStyle(
                    fontSize: 12,
                    color: HomeTheme.textMuted,
                  ),
                ),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
            decoration: BoxDecoration(
              color: statusBg,
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text(
              statusLabel,
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w700,
                color: statusFg,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
