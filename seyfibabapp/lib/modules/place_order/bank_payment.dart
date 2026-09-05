import 'dart:developer';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../utils/constants.dart';
import '../../utils/utils.dart';
import '../../widgets/custom_text.dart';
import '../../widgets/primary_button.dart';
import '../../widgets/rounded_app_bar.dart';
import '../animated_splash_screen/controller/app_setting_cubit/app_setting_cubit.dart';
import '../cart/controllers/checkout/checkout_cubit.dart';
import 'controllers/bank/bank_cubit.dart';
import 'widgets/bank_account_copy_card.dart';

class BankPaymentScreen extends StatefulWidget {
  const BankPaymentScreen({super.key, required this.mapBody});
  final Map<String, dynamic> mapBody;

  @override
  State<BankPaymentScreen> createState() => _BankPaymentScreenState();
}

class _BankPaymentScreenState extends State<BankPaymentScreen> {
  final _tnxController = TextEditingController();

  @override
  void dispose() {
    _tnxController.dispose();
    super.dispose();
  }

  String _accountInfo(BuildContext context) {
    final checkCubit = context.read<CheckoutCubit>();
    final fromCheckout = checkCubit.checkoutResponseModel?.bankStatus?.accountInfo ??
        checkCubit.guestInfo?.bankStatus?.accountInfo ??
        '';
    if (fromCheckout.trim().isNotEmpty) return fromCheckout;

    final fromSettings =
        context.read<AppSettingCubit>().settingModel?.setting.bankTransferInfo ??
            '';
    if (fromSettings.trim().isNotEmpty) return fromSettings;

    return 'Hesap bilgileri yüklenemedi.';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: RoundedAppBar(titleText: 'Banka Havalesi / EFT'),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            BankAccountCopyCard(accountInfo: _accountInfo(context)),
            const SizedBox(height: 20),
            const CustomText(
              text: 'Havale Açıklama / Dekont Bilgisi',
              fontWeight: FontWeight.w600,
            ),
            const SizedBox(height: 8),
            TextField(
              controller: _tnxController,
              maxLines: 4,
              decoration: InputDecoration(
                hintText:
                    'Havale açıklamasına yazdığınız bilgiyi veya dekont numarasını girin',
                filled: true,
                fillColor: whiteColor,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: const BorderSide(color: borderColor),
                ),
              ),
            ),
            const SizedBox(height: 24),
            PrimaryButton(
              text: 'Havale Bildirimi Gönder',
              onPressed: () {
                final tnxInfo = _tnxController.text.trim();
                if (tnxInfo.isEmpty) {
                  Utils.errorSnackBar(
                    context,
                    'Lütfen havale açıklama bilgisini girin.',
                  );
                  return;
                }

                final body = {
                  ...widget.mapBody,
                  'tnx_info': tnxInfo,
                  'agree_terms_condition': '1',
                };
                log('bank-body $body');
                Utils.closeKeyBoard(context);
                context.read<BankCubit>().makeBankPayment(body);
              },
            ),
          ],
        ),
      ),
    );
  }
}
