import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../../utils/language_string.dart';
import '../../../utils/utils.dart';
import '../../../widgets/capitalized_word.dart';
import '../../home/widgets/home_theme.dart';
import '../controllers/order/order_cubit.dart';
import '../model/order_model.dart';
import '../widgets/order_product_thumb.dart';

class OrderedListComponent extends StatelessWidget {
  const OrderedListComponent({super.key, required this.orderedItem});

  final OrderModel orderedItem;

  ({Color bg, Color fg, String label}) _statusStyle() {
    switch ('${orderedItem.orderStatus}') {
      case '0':
        return (
          bg: const Color(0xFFFFF6E5),
          fg: const Color(0xFFB45309),
          label: Language.pending.capitalizeByWord(),
        );
      case '1':
        return (
          bg: const Color(0xFFEFF6FF),
          fg: const Color(0xFF2563EB),
          label: Language.progress.capitalizeByWord(),
        );
      case '2':
        return (
          bg: const Color(0xFFECFDF3),
          fg: greenColor,
          label: Language.delivered.capitalizeByWord(),
        );
      case '3':
        return (
          bg: const Color(0xFFECFDF3),
          fg: deepGreenColor,
          label: Language.completed.capitalizeByWord(),
        );
      default:
        return (
          bg: redColor.withValues(alpha: 0.08),
          fg: redColor,
          label: Language.declined.capitalizeByWord(),
        );
    }
  }

  ({Color bg, Color fg, String label})? _paymentStyle() {
    if (orderedItem.paymentStatus == 1) {
      return (
        bg: const Color(0xFFECFDF3),
        fg: greenColor,
        label: Language.paymentPaid,
      );
    }
    if (orderedItem.paymentStatus == 0) {
      return (
        bg: const Color(0xFFFFF6E5),
        fg: const Color(0xFFB45309),
        label: Language.paymentPending,
      );
    }
    return null;
  }

  String _productPreview() {
    final products = orderedItem.orderProducts;
    if (products.isEmpty) {
      return Language.orderProductCount(orderedItem.productQty);
    }
    final first = products.first.productName;
    if (products.length == 1) return first;
    return '$first +${products.length - 1}';
  }

  Future<void> _copyOrderId(BuildContext context) async {
    await Clipboard.setData(ClipboardData(text: orderedItem.orderId));
    if (context.mounted) {
      Utils.showSnackBar(context, Language.copiedToClipboard);
    }
  }

  String? _firstThumb() {
    final products = orderedItem.orderProducts;
    if (products.isEmpty) return null;
    for (final product in products) {
      if (product.thumbImage.isNotEmpty) return product.thumbImage;
    }
    return null;
  }

  @override
  Widget build(BuildContext context) {
    final oCubit = context.read<OrderCubit>();
    final status = _statusStyle();
    final payment = _paymentStyle();
    final firstThumb = _firstThumb() ?? '';

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: HomeTheme.cardDecoration(),
      clipBehavior: Clip.antiAlias,
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () {
            if (oCubit.state.orderState is! OrderStateInitial) {
              oCubit.initPage(isPaginate: false);
            }
            oCubit.tempTrackOrderId(orderedItem.orderId);
            Navigator.pushNamed(context, RouteNames.singleOrderScreen);
          },
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    OrderProductThumb(
                      thumbImage: firstThumb,
                      size: 42,
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Text(
                                '#${orderedItem.orderId}',
                                style: const TextStyle(
                                  fontSize: 15,
                                  fontWeight: FontWeight.w700,
                                  color: HomeTheme.textDark,
                                ),
                              ),
                              InkWell(
                                onTap: () => _copyOrderId(context),
                                borderRadius: BorderRadius.circular(4),
                                child: Padding(
                                  padding: const EdgeInsets.all(4),
                                  child: Icon(
                                    Icons.copy_rounded,
                                    size: 14,
                                    color: HomeTheme.textMuted
                                        .withValues(alpha: 0.85),
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 3),
                          Text(
                            Utils.formatDate(orderedItem.createdAt),
                            style: const TextStyle(
                              fontSize: 12,
                              color: HomeTheme.textMuted,
                            ),
                          ),
                        ],
                      ),
                    ),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 10,
                            vertical: 5,
                          ),
                          decoration: BoxDecoration(
                            color: status.bg,
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Text(
                            status.label,
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                              color: status.fg,
                            ),
                          ),
                        ),
                        if (payment != null) ...[
                          const SizedBox(height: 6),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 8,
                              vertical: 4,
                            ),
                            decoration: BoxDecoration(
                              color: payment.bg,
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Text(
                              payment.label,
                              style: TextStyle(
                                fontSize: 10,
                                fontWeight: FontWeight.w700,
                                color: payment.fg,
                              ),
                            ),
                          ),
                        ],
                      ],
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Text(
                  _productPreview(),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 13,
                    height: 1.35,
                    color: HomeTheme.textDark,
                  ),
                ),
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: HomeTheme.bg,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: HomeTheme.headerBorder),
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        child: _InfoCell(
                          label: Language.quantity.capitalizeByWord(),
                          value: '${orderedItem.productQty}',
                        ),
                      ),
                      Container(
                        width: 1,
                        height: 28,
                        color: HomeTheme.headerBorder,
                      ),
                      Expanded(
                        child: _InfoCell(
                          label: Language.totalAmount.capitalizeByWord(),
                          value: Utils.formatPrice(
                            orderedItem.totalAmount,
                            context,
                            2,
                          ),
                          alignEnd: true,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        Language.viewDetails.capitalizeByWord(),
                        style: const TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                          color: HomeTheme.textDark,
                        ),
                      ),
                    ),
                    const Icon(
                      Icons.chevron_right_rounded,
                      size: 18,
                      color: HomeTheme.textMuted,
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _InfoCell extends StatelessWidget {
  const _InfoCell({
    required this.label,
    required this.value,
    this.alignEnd = false,
  });

  final String label;
  final String value;
  final bool alignEnd;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment:
          alignEnd ? CrossAxisAlignment.end : CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: const TextStyle(
            fontSize: 11,
            color: HomeTheme.textMuted,
          ),
        ),
        const SizedBox(height: 2),
        Text(
          value,
          style: const TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w700,
            color: HomeTheme.textDark,
          ),
        ),
      ],
    );
  }
}

// Detay ekranında kullanılmaya devam ediyor
class OrderItem extends StatelessWidget {
  const OrderItem({
    super.key,
    required this.title,
    required this.value,
    this.isTranslate = true,
  });

  final String title;
  final String value;
  final bool isTranslate;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Flexible(
          fit: FlexFit.loose,
          child: Text(
            title,
            style: const TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w500,
              color: HomeTheme.textDark,
            ),
          ),
        ),
        const SizedBox(width: 6),
        Flexible(
          fit: FlexFit.loose,
          child: Text(
            value,
            style: const TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w700,
              color: HomeTheme.textDark,
            ),
          ),
        ),
      ],
    );
  }
}
