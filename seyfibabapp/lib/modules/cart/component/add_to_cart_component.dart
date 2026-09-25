import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../core/remote_urls.dart';
import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../../utils/k_images.dart';
import '../../../utils/language_string.dart';
import '../../../utils/utils.dart';
import '../../../widgets/confirm_dialog.dart';
import '../../../widgets/custom_image.dart';
import '../../../widgets/custom_text.dart';
import '../../animated_splash_screen/controller/app_setting_cubit/app_setting_cubit.dart';
import '../../category/component/price_card_widget.dart';
import '../controllers/cart/cart_cubit.dart';
import '../model/cart_product_model.dart';
import '../model/varient_model.dart';

/// Web ProductsTable kartına uyumlu sepet satırı.
class AddToCartComponent extends StatelessWidget {
  const AddToCartComponent({
    super.key,
    required this.product,
    required this.onChange,
    required this.appSetting,
  });

  final CartProductModel product;
  final ValueChanged<int> onChange;
  final AppSettingCubit appSetting;

  String get _code {
    final b = product.product.barcode.trim();
    if (b.isNotEmpty) return b;
    return product.product.sku.trim();
  }

  String get _variantsText {
    if (product.variants.isEmpty) return '';
    return product.variants
        .map((VarientModel v) {
          final vi = v.varientItem;
          if (vi == null) return null;
          final g = vi.productVariantName.trim().isEmpty
              ? 'Seçenek'
              : vi.productVariantName.trim();
          return '$g: ${vi.name}';
        })
        .whereType<String>()
        .join(' · ');
  }

  @override
  Widget build(BuildContext context) {
    final p = product.product;
    final qty = product.qty < 1 ? 1 : product.qty;
    final saleUnit = p.saleUnitQty < 1 ? 1 : p.saleUnitQty;
    final maxInst = p.maxInstallment < 1 ? 1 : p.maxInstallment;
    final cat = p.categoryName.trim();
    final code = _code;
    final variantsText = _variantsText;

    return Container(
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
                    arguments: p.slug,
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
                    path: RemoteUrls.imageUrl(p.thumbImage),
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
                          child: InkWell(
                            onTap: () {
                              Navigator.pushNamed(
                                context,
                                RouteNames.productDetailsScreen,
                                arguments: p.slug,
                              );
                            },
                            child: CustomText(
                              text: p.name,
                              maxLine: 2,
                              fontWeight: FontWeight.w800,
                              fontSize: 14,
                              height: 1.25,
                              color: const Color(0xFF04334A),
                            ),
                          ),
                        ),
                        InkWell(
                          onTap: () => _confirmRemove(context),
                          child: const Padding(
                            padding: EdgeInsets.only(left: 6),
                            child: Icon(Icons.delete_outline,
                                size: 22, color: yellowColor),
                          ),
                        ),
                      ],
                    ),
                    if (code.isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Text(
                        code,
                        style: TextStyle(
                          fontSize: 12,
                          color: const Color(0xFF04334A).withValues(alpha: 0.50),
                          fontFeatures: const [FontFeature.tabularFigures()],
                        ),
                      ),
                    ],
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
              _QtyControl(
                qty: qty,
                onDec: qty > 1
                    ? () async {
                        final result = await context
                            .read<CartCubit>()
                            .decrementQuantity(product.id.toString());
                        result.fold(
                          (f) => Utils.errorSnackBar(context, f.message),
                          (_) => Future.microtask(
                            () => context.read<CartCubit>().getCartProducts(),
                          ),
                        );
                      }
                    : null,
                onInc: () async {
                  final result = await context
                      .read<CartCubit>()
                      .incrementQuantity(product.id.toString());
                  result.fold(
                    (f) => Utils.errorSnackBar(context, f.message),
                    (_) => Future.microtask(
                      () => context.read<CartCubit>().getCartProducts(),
                    ),
                  );
                },
              ),
              const Spacer(),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  PriceCardWidget(
                    price: Utils.cartProductRegularPrice(context, product)
                        .toString(),
                    offerPrice:
                        Utils.cartProductPrice(context, product).toString(),
                    textSize: 14,
                    saleUnitQty: saleUnit,
                  ),
                  const SizedBox(height: 2),
                  CustomText(
                    text:
                        'Toplam ${Utils.formatPrice(Utils.cartLineTotal(context, product), context)}',
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

  Future<void> _confirmRemove(BuildContext context) async {
    await showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => ConfirmDialog(
        icon: Kimages.deleteIcon2,
        message: Language.wishToRemoveProduct,
        confirmText: Language.yesRemove,
        cancelText: Language.no,
        onTap: () async {
          final result =
              await context.read<CartCubit>().removerCartItem(product.id.toString());
          result.fold(
            (failure) => Utils.errorSnackBar(context, failure.message),
            (success) {
              onChange(product.id);
              Utils.showSnackBar(context, success);
            },
          );
          if (ctx.mounted) Navigator.of(ctx).pop(true);
        },
      ),
    );
  }
}

class _QtyControl extends StatelessWidget {
  const _QtyControl({
    required this.qty,
    required this.onInc,
    this.onDec,
  });

  final int qty;
  final VoidCallback? onDec;
  final VoidCallback onInc;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 36,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: const Color(0xFF04334A).withValues(alpha: 0.15)),
        color: whiteColor,
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          InkWell(
            onTap: onDec,
            child: SizedBox(
              width: 36,
              height: 36,
              child: Icon(
                Icons.remove,
                size: 18,
                color: onDec == null
                    ? Colors.grey.shade400
                    : yellowColor,
              ),
            ),
          ),
          Container(
            constraints: const BoxConstraints(minWidth: 36),
            alignment: Alignment.center,
            decoration: BoxDecoration(
              border: Border.symmetric(
                vertical: BorderSide(
                  color: const Color(0xFF04334A).withValues(alpha: 0.10),
                ),
              ),
            ),
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
            onTap: onInc,
            child: const SizedBox(
              width: 36,
              height: 36,
              child: Icon(Icons.add, size: 18, color: yellowColor),
            ),
          ),
        ],
      ),
    );
  }
}
