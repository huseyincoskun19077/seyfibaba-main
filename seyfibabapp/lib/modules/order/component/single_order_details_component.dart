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
import '../model/product_order_model.dart';
import '../widgets/order_product_thumb.dart';

class SingleOrderDetailsComponent extends StatefulWidget {
  const SingleOrderDetailsComponent({
    super.key,
    required this.orderItem,
  });

  final OrderedProductModel orderItem;

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
                Text(
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
                const SizedBox(height: 4),
                Text(
                  '${Language.quantity.capitalizeByWord()}: ${orderItem.qty}',
                  style: const TextStyle(
                    fontSize: 12,
                    color: HomeTheme.textMuted,
                  ),
                ),
                if (cargo != null && cargo.hasTracking) ...[
                  const SizedBox(height: 8),
                  _CargoInfo(
                    cargo: cargo,
                    onOpenTracking: _openTrackingUrl,
                  ),
                ],
                const SizedBox(height: 6),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    Text(
                      Utils.formatPrice(orderItem.unitPrice, context),
                      style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                        color: HomeTheme.textDark,
                      ),
                    ),
                    _buildActionArea(context),
                  ],
                ),
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
          height: 34,
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
            Text(
              Language.deliveryConfirmedBadge,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: greenColor.withValues(alpha: 0.95),
              ),
            ),
          ],
        ),
      );
    }

    if (orderItem.userHasReviewed) {
      actions.add(
        Text(
          Language.reviewSubmitted,
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w600,
            color: HomeTheme.textMuted.withValues(alpha: 0.95),
          ),
        ),
      );
    } else if (orderItem.canWriteReview) {
      actions.add(
        InkWell(
          onTap: () {
            Navigator.pushNamed(
              context,
              RouteNames.submitFeedBackScreen,
              arguments: orderItem,
            );
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

    if (actions.isEmpty) {
      return const SizedBox.shrink();
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.end,
      children: [
        for (var i = 0; i < actions.length; i++) ...[
          if (i > 0) const SizedBox(height: 6),
          actions[i],
        ],
      ],
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
