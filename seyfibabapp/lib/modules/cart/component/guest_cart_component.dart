import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../core/remote_urls.dart';
import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../../utils/utils.dart';
import '../../../widgets/custom_image.dart';
import '../../../widgets/custom_text.dart';
import '../../category/component/price_card_widget.dart';
import '../../product_details/controller/cubit/product_details_cubit.dart';
import '../model/guest_cart_product.dart';

/// Web cart kartına yakın misafir sepet satırı.
class GuestCartComponent extends StatelessWidget {
  const GuestCartComponent({
    super.key,
    required this.product,
    this.isVisible,
    this.visibleQty,
  });

  final GustCartProduct? product;
  final bool? isVisible;
  final bool? visibleQty;

  String get _variantsText {
    final list = product?.variants;
    if (list == null || list.isEmpty) return '';
    return list
        .map((v) {
          final vi = v.variantItem;
          if (vi == null) return null;
          final g = vi.variantName.trim().isEmpty
              ? 'Seçenek'
              : vi.variantName.trim();
          return '$g: ${vi.name}';
        })
        .whereType<String>()
        .join(' · ');
  }

  @override
  Widget build(BuildContext context) {
    final detailCubit = context.read<ProductDetailsCubit>();
    final p = product?.product;
    final qty = (product?.qty ?? 1) < 1 ? 1 : (product?.qty ?? 1);
    final saleUnit = (p?.saleUnitQty ?? 1) < 1 ? 1 : (p?.saleUnitQty ?? 1);
    final maxInst = (p?.maxInstallment ?? 1) < 1 ? 1 : (p?.maxInstallment ?? 1);
    final cat = (p?.categoryName ?? '').trim();
    final variantsText = _variantsText;
    final showActions = isVisible ?? true;
    final showQty = visibleQty ?? true;

    return Container(
      key: ValueKey(product?.productId ?? 0),
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      padding: const EdgeInsets.fromLTRB(12, 14, 12, 14),
      decoration: BoxDecoration(
        color: whiteColor,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFF04334A).withValues(alpha: 0.10)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              InkWell(
                onTap: () {
                  Navigator.pushNamed(
                    context,
                    RouteNames.productDetailsScreen,
                    arguments: p?.slug ?? '',
                  );
                },
                child: Container(
                  width: 76,
                  height: 76,
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(
                        color: const Color(0xFF04334A).withValues(alpha: 0.12)),
                    color: const Color(0xFFFAFAFA),
                  ),
                  clipBehavior: Clip.antiAlias,
                  child: CustomImage(
                    path: RemoteUrls.imageUrl(p?.thumbImage ?? ''),
                    fit: BoxFit.contain,
                  ),
                ),
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
                          child: CustomText(
                            text: p?.name ?? '',
                            maxLine: 2,
                            fontWeight: FontWeight.w800,
                            fontSize: 14,
                            height: 1.25,
                            color: const Color(0xFF04334A),
                          ),
                        ),
                        if (showActions)
                          InkWell(
                            onTap: () {
                              detailCubit.deleteGuestProduct(
                                  context, p?.id ?? 0);
                            },
                            child: const Padding(
                              padding: EdgeInsets.only(left: 6),
                              child: Icon(Icons.delete_outline,
                                  size: 22, color: yellowColor),
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text.rich(
                      TextSpan(
                        style: TextStyle(
                          fontSize: 12,
                          color: const Color(0xFF04334A).withValues(alpha: 0.70),
                        ),
                        children: [
                          const TextSpan(
                            text: 'İçindeki miktar: ',
                            style: TextStyle(
                              fontWeight: FontWeight.w700,
                              color: Color(0xFF04334A),
                            ),
                          ),
                          TextSpan(text: '$saleUnit adet'),
                          if (qty > 1)
                            TextSpan(
                              text:
                                  ' · Sepette $qty × $saleUnit = ${qty * saleUnit} adet',
                              style: TextStyle(
                                color: const Color(0xFF04334A)
                                    .withValues(alpha: 0.50),
                              ),
                            ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text.rich(
                      TextSpan(
                        style: TextStyle(
                          fontSize: 12,
                          height: 1.35,
                          color: const Color(0xFF04334A).withValues(alpha: 0.70),
                        ),
                        children: [
                          const TextSpan(
                            text: 'Bireysel kart: ',
                            style: TextStyle(
                              fontWeight: FontWeight.w800,
                              color: yellowColor,
                            ),
                          ),
                          TextSpan(
                            text: maxInst > 1
                                ? '$maxInst taksite kadar'
                                : 'Tek çekim',
                          ),
                          if (cat.isNotEmpty)
                            TextSpan(
                              text: ' ($cat)',
                              style: TextStyle(
                                color: const Color(0xFF04334A)
                                    .withValues(alpha: 0.45),
                              ),
                            ),
                        ],
                      ),
                    ),
                    Text.rich(
                      TextSpan(
                        style: TextStyle(
                          fontSize: 12,
                          height: 1.35,
                          color: const Color(0xFF04334A).withValues(alpha: 0.70),
                        ),
                        children: [
                          const TextSpan(
                            text: 'Ticari kart: ',
                            style: TextStyle(
                              fontWeight: FontWeight.w800,
                              color: yellowColor,
                            ),
                          ),
                          TextSpan(
                            text: '—',
                            style: TextStyle(
                              color: const Color(0xFF04334A)
                                  .withValues(alpha: 0.35),
                            ),
                          ),
                        ],
                      ),
                    ),
                    if (variantsText.isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Text(
                        variantsText,
                        style: TextStyle(
                          fontSize: 12,
                          color: const Color(0xFF04334A).withValues(alpha: 0.55),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              if (showQty)
                Container(
                  height: 36,
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(
                        color: const Color(0xFF04334A).withValues(alpha: 0.15)),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      InkWell(
                        onTap: qty > 1
                            ? () => detailCubit.updateQty(p?.id ?? 0, false)
                            : null,
                        child: SizedBox(
                          width: 36,
                          height: 36,
                          child: Icon(
                            Icons.remove,
                            size: 18,
                            color: qty > 1 ? yellowColor : Colors.grey.shade400,
                          ),
                        ),
                      ),
                      Container(
                        constraints: const BoxConstraints(minWidth: 36),
                        alignment: Alignment.center,
                        child: Text(
                          '$qty',
                          style: const TextStyle(
                            fontWeight: FontWeight.w800,
                            fontSize: 14,
                            color: Color(0xFF04334A),
                          ),
                        ),
                      ),
                      InkWell(
                        onTap: () => detailCubit.updateQty(p?.id ?? 0, true),
                        child: const SizedBox(
                          width: 36,
                          height: 36,
                          child: Icon(Icons.add, size: 18, color: yellowColor),
                        ),
                      ),
                    ],
                  ),
                ),
              const Spacer(),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  PriceCardWidget(
                    price: Utils.guestCartRegularPrice(context, product)
                        .toString(),
                    offerPrice: Utils.guestCart(context, product).toString(),
                    textSize: 14,
                    saleUnitQty: saleUnit,
                  ),
                  const SizedBox(height: 2),
                  CustomText(
                    text:
                        'Toplam ${Utils.formatPrice(Utils.guestCartLineTotal(context, product), context)}',
                    isTranslate: false,
                    fontSize: 13,
                    fontWeight: FontWeight.w800,
                    color: const Color(0xFF04334A),
                  ),
                ],
              ),
            ],
          ),
        ],
      ),
    );
  }
}
