import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_svg/flutter_svg.dart';

import '../../../utils/constants.dart';
import '../../../utils/k_images.dart';
import '../../../utils/utils.dart';
import '../../../widgets/custom_text.dart';
import '../../../widgets/primary_button.dart';
import '../../animated_splash_screen/controller/app_setting_cubit/app_setting_cubit.dart';
import '../../cart/controllers/checkout/checkout_cubit.dart';
import '../../cart/controllers/delivery_charges/delivery_charges_cubit.dart';
import '../../cart/model/checkout_response_model.dart';
import '../../cart/model/coupon_response_model.dart';
import '../controllers/bank/bank_cubit.dart';
import '../controllers/iyzico/iyzico_cubit.dart';
import '../utils/payment_payload_helper.dart';

enum _PaymentMethod { iyzico, bank }

class MarketplacePaymentMethods extends StatefulWidget {
  const MarketplacePaymentMethods({
    super.key,
    required this.checkoutData,
    required this.baseBody,
    required this.isGuest,
    this.guestBody,
  });

  final CheckoutResponseModel checkoutData;
  final Map<String, dynamic> baseBody;
  final bool isGuest;
  final Map<String, dynamic>? guestBody;

  @override
  State<MarketplacePaymentMethods> createState() =>
      _MarketplacePaymentMethodsState();
}

class _MarketplacePaymentMethodsState extends State<MarketplacePaymentMethods> {
  _PaymentMethod? _selectedMethod;
  final _tnxController = TextEditingController();

  bool get _iyzicoEnabled => widget.checkoutData.iyzico?.status == 1;

  bool get _bankEnabled => widget.checkoutData.bankStatus?.status == 1;

  String get _bankAccountInfo =>
      widget.checkoutData.bankStatus?.accountInfo.trim().isNotEmpty == true
          ? widget.checkoutData.bankStatus!.accountInfo
          : context
                  .read<AppSettingCubit>()
                  .settingModel
                  ?.setting
                  .bankTransferInfo
                  .trim()
                  .isNotEmpty ==
              true
          ? context.read<AppSettingCubit>().settingModel!.setting.bankTransferInfo
          : 'Hesap bilgileri yüklenemedi. Lütfen destek ile iletişime geçin.';

  double get _discountPercent {
    final fromSettings = context
        .read<AppSettingCubit>()
        .settingModel
        ?.setting
        .bankTransferDiscountPercent;
    return fromSettings ?? 3;
  }

  double _resolveOrderTotal() {
    final delivery = context.read<DeliveryChargesCubit>().state;
    final deliveryTotal = delivery.initialPrice + delivery.distancePrice;
    if (deliveryTotal > 0) return deliveryTotal;

    final checkoutState = context.read<CheckoutCubit>().state;
    if (checkoutState.totalCheckoutPrice > 0) {
      return checkoutState.totalCheckoutPrice;
    }

    final productsTotal =
        PaymentPayloadHelper.calculateProductsTotal(widget.checkoutData.cartProducts);
    return productsTotal + checkoutState.shippingFee + checkoutState.distancePrice;
  }

  double get _orderTotal => _resolveOrderTotal();

  Map<String, dynamic> get _paymentBody {
    if (widget.isGuest && widget.guestBody != null) {
      final apiCart = PaymentPayloadHelper.cartProductsForApi(
        widget.checkoutData.cartProducts,
      );
      final guestCart = widget.guestBody!['cart_products'];
      return {
        ...widget.guestBody!,
        'cart_products':
            apiCart.isNotEmpty ? apiCart : guestCart ?? <dynamic>[],
        'agree_terms_condition': '1',
      };
    }

    return PaymentPayloadHelper.mergePaymentBody(
      baseBody: widget.baseBody,
      cartProducts: widget.checkoutData.cartProducts,
    );
  }

  @override
  void initState() {
    super.initState();
    if (_iyzicoEnabled) {
      _selectedMethod = _PaymentMethod.iyzico;
    } else if (_bankEnabled) {
      _selectedMethod = _PaymentMethod.bank;
    }
  }

  @override
  void dispose() {
    _tnxController.dispose();
    super.dispose();
  }

  void _submitIyzico() {
    context.read<IyzicoCubit>().startCheckout(
          _paymentBody,
          isGuest: widget.isGuest,
        );
  }

  void _submitBank() {
    final tnxInfo = _tnxController.text.trim();
    if (tnxInfo.isEmpty) {
      Utils.errorSnackBar(
        context,
        'Havale/EFT açıklamasına sipariş veya dekont bilgisini yazın.',
      );
      return;
    }

    context.read<BankCubit>().makeBankPayment({
      ..._paymentBody,
      'tnx_info': tnxInfo,
    });
  }

  void _onPrimaryAction() {
    if (_selectedMethod == null) {
      Utils.errorSnackBar(context, 'Lütfen bir ödeme yöntemi seçin.');
      return;
    }

    if (_selectedMethod == _PaymentMethod.iyzico) {
      _submitIyzico();
    } else {
      _submitBank();
    }
  }

  @override
  Widget build(BuildContext context) {
    if (!_iyzicoEnabled && !_bankEnabled) {
      return const Padding(
        padding: EdgeInsets.all(20),
        child: CustomText(
          text:
              'Şu anda aktif ödeme yöntemi bulunmuyor. Lütfen daha sonra tekrar deneyin.',
          textAlign: TextAlign.center,
        ),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSummaryCard(),
        const SizedBox(height: 20),
        const CustomText(
          text: 'Ödeme Yöntemi',
          fontSize: 18,
          fontWeight: FontWeight.w700,
        ),
        const SizedBox(height: 12),
        if (_iyzicoEnabled)
          _PaymentOptionTile(
            title: 'Kredi / Banka Kartı',
            subtitle: 'Iyzico ile güvenli ödeme',
            iconAsset: Kimages.paymentIcon,
            selected: _selectedMethod == _PaymentMethod.iyzico,
            onTap: () => setState(() => _selectedMethod = _PaymentMethod.iyzico),
          ),
        if (_bankEnabled) ...[
          _PaymentOptionTile(
            title: 'Banka Havalesi / EFT',
            subtitle: '%${_discountPercent.toStringAsFixed(0)} indirim',
            iconAsset: Kimages.bankIcon,
            selected: _selectedMethod == _PaymentMethod.bank,
            badge: '-%${_discountPercent.toStringAsFixed(0)}',
            onTap: () => setState(() => _selectedMethod = _PaymentMethod.bank),
          ),
          if (_selectedMethod == _PaymentMethod.bank) ...[
            const SizedBox(height: 12),
            _buildBankInfoCard(),
            const SizedBox(height: 12),
            TextField(
              controller: _tnxController,
              maxLines: 3,
              decoration: InputDecoration(
                hintText:
                    'Havale açıklamasına yazdığınız bilgiyi buraya girin (dekont no, ad-soyad vb.)',
                filled: true,
                fillColor: whiteColor,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: const BorderSide(color: borderColor),
                ),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: const BorderSide(color: borderColor),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: const BorderSide(color: deepGreenColor),
                ),
              ),
            ),
          ],
        ],
        const SizedBox(height: 24),
        PrimaryButton(
          text: _selectedMethod == _PaymentMethod.bank
              ? 'Havale Bildirimi Gönder'
              : 'Kart ile Öde',
          onPressed: _onPrimaryAction,
        ),
      ],
    );
  }

  Widget _buildSummaryCard() {
    final isBank = _selectedMethod == _PaymentMethod.bank && _bankEnabled;
    return BlocBuilder<CheckoutCubit, CouponResponseModel>(
      builder: (context, _) {
        final orderTotal = _resolveOrderTotal();
        final bankDiscount = orderTotal * _discountPercent / 100;
        final bankTotal = orderTotal - bankDiscount;

        return Container(
          width: double.infinity,
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: deepGreenColor.withOpacity(0.08),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: deepGreenColor.withOpacity(0.2)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const CustomText(
                text: 'Sipariş Özeti',
                fontWeight: FontWeight.w700,
                fontSize: 16,
              ),
              const SizedBox(height: 8),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const CustomText(text: 'Ara Toplam + Kargo'),
                  CustomText(
                    text: Utils.formatPrice(orderTotal, context),
                    isTranslate: false,
                    fontWeight: FontWeight.w600,
                  ),
                ],
              ),
              if (isBank) ...[
                const SizedBox(height: 6),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    CustomText(
                      text:
                          'Havale İndirimi (%${_discountPercent.toStringAsFixed(0)})',
                      color: deepGreenColor,
                    ),
                    CustomText(
                      text: '- ${Utils.formatPrice(bankDiscount, context)}',
                      isTranslate: false,
                      color: deepGreenColor,
                      fontWeight: FontWeight.w600,
                    ),
                  ],
                ),
              ],
              const Divider(height: 20),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const CustomText(
                    text: 'Ödenecek Tutar',
                    fontWeight: FontWeight.w700,
                    fontSize: 16,
                  ),
                  CustomText(
                    text: Utils.formatPrice(
                        isBank ? bankTotal : orderTotal, context),
                    isTranslate: false,
                    color: redColor,
                    fontWeight: FontWeight.w700,
                    fontSize: 18,
                  ),
                ],
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildBankInfoCard() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: yellowColor.withOpacity(0.15),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: yellowColor.withOpacity(0.5)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const CustomText(
            text: 'Banka Hesap Bilgileri',
            fontWeight: FontWeight.w700,
          ),
          const SizedBox(height: 8),
          CustomText(
            text: _bankAccountInfo,
            isTranslate: false,
            fontSize: 14,
            color: paragraphColor,
          ),
          const SizedBox(height: 8),
          const CustomText(
            text:
                'Havale/EFT sonrası açıklama alanına yazdığınız bilgiyi yukarıdaki alana girin. Sipariş admin onayından sonra işleme alınır.',
            fontSize: 12,
            color: textGreyColor,
          ),
        ],
      ),
    );
  }
}

class _PaymentOptionTile extends StatelessWidget {
  const _PaymentOptionTile({
    required this.title,
    required this.subtitle,
    required this.iconAsset,
    required this.selected,
    required this.onTap,
    this.badge,
  });

  final String title;
  final String subtitle;
  final String iconAsset;
  final bool selected;
  final VoidCallback onTap;
  final String? badge;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(14),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
          decoration: BoxDecoration(
            color: selected ? deepGreenColor.withOpacity(0.08) : whiteColor,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(
              color: selected ? deepGreenColor : borderColor,
              width: selected ? 1.5 : 1,
            ),
          ),
          child: Row(
            children: [
              Container(
                width: 44,
                height: 44,
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: scaBgColor,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: iconAsset.endsWith('.svg')
                    ? SvgPicture.asset(iconAsset)
                    : Image.asset(iconAsset),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    CustomText(text: title, fontWeight: FontWeight.w700),
                    const SizedBox(height: 2),
                    CustomText(text: subtitle, fontSize: 12, color: textGreyColor),
                  ],
                ),
              ),
              if (badge != null)
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: deepGreenColor.withOpacity(0.15),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: CustomText(
                    text: badge!,
                    color: deepGreenColor,
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              const SizedBox(width: 8),
              Icon(
                selected ? Icons.radio_button_checked : Icons.radio_button_off,
                color: selected ? deepGreenColor : textGreyColor,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
