import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../../utils/language_string.dart';
import '../../../utils/utils.dart';
import '../../../widgets/capitalized_word.dart';
import '../../home/widgets/home_theme.dart';
import '../controllers/order/order_cubit.dart';
import '../model/buyer_return_model.dart';
import '../model/product_order_model.dart';
import '../screens/create_return_screen.dart';
import '../utils/order_display_status.dart';
import '../widgets/order_product_thumb.dart';

class SingleOrderDetailsComponent extends StatefulWidget {
  const SingleOrderDetailsComponent({
    super.key,
    required this.orderItem,
    this.orderCode = '',
    this.returnable,
    this.onReturnCreated,
  });

  final OrderedProductModel orderItem;
  final String orderCode;
  final BuyerReturnableItem? returnable;
  final Future<void> Function()? onReturnCreated;

  @override
  State<SingleOrderDetailsComponent> createState() =>
      _SingleOrderDetailsComponentState();
}

class _SingleOrderDetailsComponentState
    extends State<SingleOrderDetailsComponent> {
  bool _confirming = false;

  OrderedProductModel get orderItem => widget.orderItem;

  Future<void> _handleConfirmDelivery() async {
    if (_confirming) return;
    setState(() => _confirming = true);

    final error = await context
        .read<OrderCubit>()
        .confirmOrderProductDelivery(orderItem.id);

    if (!mounted) return;
    setState(() => _confirming = false);

    if (error != null) {
      Utils.errorSnackBar(context, error);
    } else {
      Utils.showSnackBar(context, Language.deliveryConfirmed);
    }
  }

  Future<void> _openTrackingUrl(String url) async {
    final uri = Uri.tryParse(url);
    if (uri == null) return;
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  @override
  Widget build(BuildContext context) {
    final cargo = orderItem.cargo;
    final lineStatus = OrderDisplayStatusHelper.resolveLine(orderItem);
    final lineColors = OrderDisplayStatusHelper.badgeColors(lineStatus);

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          OrderProductThumb(
            thumbImage: orderItem.thumbImage,
            size: 56,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Text(
                        orderItem.productName,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: HomeTheme.textDark,
                          height: 1.35,
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 3,
                      ),
                      decoration: BoxDecoration(
                        color: lineColors.bg,
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Text(
                        OrderDisplayStatusHelper.badgeLabel(lineStatus),
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.w700,
                          color: lineColors.fg,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                Text(
                  '${Language.quantity.capitalizeByWord()}: ${orderItem.qty}',
                  style: const TextStyle(
                    fontSize: 12,
                    color: HomeTheme.textMuted,
                  ),
                ),
                if (cargo != null &&
                    cargo.hasTracking &&
                    (orderItem.sellerStatus >= 2 ||
                        (orderItem.shippedAt != null &&
                            orderItem.shippedAt!.trim().isNotEmpty))) ...[
                  const SizedBox(height: 8),
                  _CargoInfo(
                    cargo: cargo,
                    onOpenTracking: _openTrackingUrl,
                  ),
                ],
                const SizedBox(height: 6),
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        Utils.formatPrice(orderItem.unitPrice, context),
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w700,
                          color: HomeTheme.textDark,
                        ),
                      ),
                    ),
                    if (!orderItem.canConfirmDelivery)
                      Flexible(
                        child: Align(
                          alignment: Alignment.centerRight,
                          child: _buildActionArea(context),
                        ),
                      ),
                  ],
                ),
                if (orderItem.canConfirmDelivery) ...[
                  const SizedBox(height: 8),
                  _buildActionArea(context),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildActionArea(BuildContext context) {
    final actions = <Widget>[];

    if (orderItem.canConfirmDelivery) {
      actions.add(
        SizedBox(
          width: double.infinity,
          height: 36,
          child: ElevatedButton(
            onPressed: _confirming ? null : _handleConfirmDelivery,
            style: ElevatedButton.styleFrom(
              backgroundColor: greenColor,
              disabledBackgroundColor: greenColor.withValues(alpha: 0.5),
              foregroundColor: whiteColor,
              elevation: 0,
              padding: const EdgeInsets.symmetric(horizontal: 12),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(8),
              ),
            ),
            child: Text(
              _confirming
                  ? Language.confirmingDelivery
                  : Language.confirmDeliveryReceived,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ),
      );
    } else if (orderItem.isCustomerConfirmed) {
      actions.add(
        Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.check_circle_outline,
              size: 16,
              color: greenColor.withValues(alpha: 0.9),
            ),
            const SizedBox(width: 4),
            Flexible(
              child: Text(
                Language.deliveryConfirmedBadge,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  color: greenColor.withValues(alpha: 0.95),
                ),
              ),
            ),
          ],
        ),
      );
    }

    if (orderItem.userHasReviewed) {
      actions.add(
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
          decoration: BoxDecoration(
            color: greenColor.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Text(
            Language.reviewSubmitted,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w700,
              color: greenColor.withValues(alpha: 0.95),
            ),
          ),
        ),
      );
    } else if (orderItem.canWriteReview) {
      actions.add(
        InkWell(
          onTap: () async {
            final result = await Navigator.pushNamed(
              context,
              RouteNames.submitFeedBackScreen,
              arguments: orderItem,
            );
            if (result == true && context.mounted) {
              await context.read<OrderCubit>().showOrderTracking();
            }
          },
          borderRadius: BorderRadius.circular(8),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
            child: Text(
              Language.writeReview,
              style: const TextStyle(
                color: HomeTheme.textDark,
                fontSize: 13,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ),
      );
    }

    final returnable = widget.returnable;
    if (returnable?.isRejected == true) {
      actions.add(
        Column(
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              decoration: BoxDecoration(
                color: const Color(0xFF64748B).withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                (returnable?.returnStatusLabel ?? '').trim().isNotEmpty
                    ? returnable!.returnStatusLabel!.trim()
                    : 'İade talebi reddedildi',
                style: const TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: Color(0xFF334155),
                ),
              ),
            ),
            if ((returnable?.rejectionNote ?? '').trim().isNotEmpty) ...[
              const SizedBox(height: 6),
              ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 220),
                child: Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF8FAFC),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: HomeTheme.border),
                  ),
                  child: Text(
                    'Ret gerekçesi: ${returnable!.rejectionNote!.trim()}',
                    style: const TextStyle(
                      fontSize: 11,
                      height: 1.35,
                      color: HomeTheme.textMuted,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ),
            ],
          ],
        ),
      );
    }

    if (returnable != null && returnable.isReturnable) {
      actions.add(
        SizedBox(
          height: 34,
          child: ElevatedButton(
            onPressed: () async {
              final created = await Navigator.pushNamed(
                context,
                RouteNames.createReturnScreen,
                arguments: CreateReturnArgs(
                  orderItem: orderItem,
                  orderCode: widget.orderCode,
                  returnable: returnable,
                ),
              );
              if (created == true && context.mounted) {
                await widget.onReturnCreated?.call();
              }
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFEF262C),
              foregroundColor: whiteColor,
              elevation: 0,
              padding: const EdgeInsets.symmetric(horizontal: 12),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(8),
              ),
            ),
            child: const Text(
              'İade Et',
              style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700),
            ),
          ),
        ),
      );
    } else if (returnable?.isRejected != true &&
        returnable?.existingReturnRequestId != null) {
      actions.add(
        Column(
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              decoration: BoxDecoration(
                color: const Color(0xFFEF262C).withValues(alpha: 0.08),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                (returnable?.returnStatusLabel ?? '').trim().isNotEmpty
                    ? returnable!.returnStatusLabel!.trim()
                    : 'İade talebi alındı',
                textAlign: TextAlign.right,
                style: const TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: Color(0xFFEF262C),
                ),
              ),
            ),
            if ((returnable?.returnAddress ?? '').trim().isNotEmpty ||
                (returnable?.returnCargoCode ?? '').trim().isNotEmpty ||
                (returnable?.returnStatus == 1 ||
                    returnable?.returnStatus == 2)) ...[
              const SizedBox(height: 6),
              SizedBox(
                height: 32,
                child: OutlinedButton(
                  onPressed: () => _showReturnLogisticsDialog(context, returnable!),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: const Color(0xFF92400E),
                    side: const BorderSide(color: Color(0xFFFDE68A)),
                    backgroundColor: const Color(0xFFFFFBEB),
                    padding: const EdgeInsets.symmetric(horizontal: 10),
                    textStyle: const TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  child: const Text('İade kargo bilgilerini gör'),
                ),
              ),
            ],
          ],
        ),
      );
    }

    if (actions.isEmpty) {
      return const SizedBox.shrink();
    }

    return Column(
      crossAxisAlignment: orderItem.canConfirmDelivery
          ? CrossAxisAlignment.stretch
          : CrossAxisAlignment.end,
      children: [
        for (var i = 0; i < actions.length; i++) ...[
          if (i > 0) const SizedBox(height: 6),
          actions[i],
        ],
      ],
    );
  }

  Future<void> _showReturnLogisticsDialog(
    BuildContext context,
    BuyerReturnableItem returnable,
  ) async {
    final lines = <String>[
      if ((returnable.returnAddress ?? '').trim().isNotEmpty)
        'İade adresi:\n${returnable.returnAddress!.trim()}',
      if ((returnable.returnShippingPayerLabel ?? '').trim().isNotEmpty)
        'Kargo ücreti: ${returnable.returnShippingPayerLabel}',
      if ((returnable.returnCarrierName ?? '').trim().isNotEmpty)
        'Kargo: ${returnable.returnCarrierName}',
      if ((returnable.returnCargoCode ?? '').trim().isNotEmpty)
        'İade kodu: ${returnable.returnCargoCode}',
      if ((returnable.returnShippingInstructions ?? '').trim().isNotEmpty)
        'Talimat:\n${returnable.returnShippingInstructions!.trim()}',
      if ((returnable.buyerReturnTrackingNumber ?? '').trim().isNotEmpty)
        'Takip no: ${returnable.buyerReturnTrackingNumber}',
      if ((returnable.refundInfo ?? '').trim().isNotEmpty)
        'Para iadesi:\n${returnable.refundInfo!.trim()}',
      if (returnable.returnStatus == 1 || returnable.returnStatus == 2)
        'Ürünü iade adresine kargolayın. Satıcı ürünü aldığında süreç devam eder; takip numarası girmeniz gerekmez.',
    ];

    await showDialog<void>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('İade kargo bilgileri'),
        content: SingleChildScrollView(
          child: Text(
            lines.isEmpty ? 'Henüz kargo talimatı yok.' : lines.join('\n\n'),
            style: const TextStyle(fontSize: 13, height: 1.4),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Kapat'),
          ),
        ],
      ),
    );
  }
}

class _CargoInfo extends StatelessWidget {
  const _CargoInfo({
    required this.cargo,
    required this.onOpenTracking,
  });

  final OrderProductCargoModel cargo;
  final Future<void> Function(String url) onOpenTracking;

  Future<void> _copyTracking(BuildContext context) async {
    await Clipboard.setData(ClipboardData(text: cargo.trackingNumber));
    if (context.mounted) {
      Utils.showSnackBar(context, Language.copiedToClipboard);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: HomeTheme.brandYellow.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(
          color: HomeTheme.textMuted.withValues(alpha: 0.15),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(
                Icons.local_shipping_outlined,
                size: 14,
                color: HomeTheme.textDark,
              ),
              const SizedBox(width: 4),
              Expanded(
                child: Text(
                  cargo.carrierName.isNotEmpty
                      ? cargo.carrierName
                      : Language.cargoLabel,
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: HomeTheme.textDark,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Row(
            children: [
              Expanded(
                child: Text(
                  '${Language.trackingNumber}: ${cargo.trackingNumber}',
                  style: const TextStyle(
                    fontSize: 11,
                    color: HomeTheme.textMuted,
                  ),
                ),
              ),
              InkWell(
                onTap: () => _copyTracking(context),
                borderRadius: BorderRadius.circular(4),
                child: Padding(
                  padding: const EdgeInsets.all(2),
                  child: Icon(
                    Icons.copy_rounded,
                    size: 14,
                    color: HomeTheme.textMuted.withValues(alpha: 0.9),
                  ),
                ),
              ),
            ],
          ),
          if (cargo.trackingUrl != null && cargo.trackingUrl!.isNotEmpty) ...[
            const SizedBox(height: 8),
            SizedBox(
              height: 32,
              child: OutlinedButton.icon(
                onPressed: () => onOpenTracking(cargo.trackingUrl!),
                style: OutlinedButton.styleFrom(
                  foregroundColor: HomeTheme.textDark,
                  side: BorderSide(
                    color: HomeTheme.textMuted.withValues(alpha: 0.25),
                  ),
                  padding: const EdgeInsets.symmetric(horizontal: 10),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(8),
                  ),
                ),
                icon: const Icon(Icons.open_in_new_rounded, size: 14),
                label: Text(
                  Language.trackCargo,
                  style: const TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}
