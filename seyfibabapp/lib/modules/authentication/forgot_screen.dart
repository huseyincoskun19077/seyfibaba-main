import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:shop_o/modules/authentication/widgets/sign_up_form.dart';
import 'package:shop_o/widgets/loading_widget.dart';

import '../../widgets/custom_text.dart';
import '/utils/language_string.dart';
import '/widgets/capitalized_word.dart';
import '../../../../utils/constants.dart';
import '../../../../widgets/primary_button.dart';
import '../../core/router_name.dart';
import '../../utils/k_images.dart';
import '../../utils/utils.dart';
import '../../widgets/custom_image.dart';
import 'controller/forgot_password/forgot_password_cubit.dart';

class ForgotScreen extends StatefulWidget {
  const ForgotScreen({super.key});

  @override
  State<ForgotScreen> createState() => _ForgotScreenState();
}

class _ForgotScreenState extends State<ForgotScreen> {
  @override
  Widget build(BuildContext context) {
    final bloc = context.read<ForgotPasswordCubit>();
    final size = MediaQuery.of(context).size;
    return BlocListener<ForgotPasswordCubit, ForgotPasswordState>(
      listener: (context, state) {
        if (state is ForgotPasswordStateError) {
          Utils.errorSnackBar(context, state.errorMsg);
        } else if (state is ForgotPasswordStateLoaded) {
          Navigator.pushReplacementNamed(context, RouteNames.setPasswordScreen);
          Utils.showSnackBar(context, state.email);
        }
      },
      child: Scaffold(
        body: SafeArea(
          child: Container(
            padding: const EdgeInsets.all(20),
            width: size.width,
            height: size.height,
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.center,
                end: Alignment.bottomRight,
                colors: [Colors.white, Color(0xffFFEFE7)],
              ),
            ),
            child: Center(
              child: SingleChildScrollView(child: _buildForm(bloc)),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildForm(ForgotPasswordCubit bloc) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.center,
      mainAxisAlignment: MainAxisAlignment.center,
      mainAxisSize: MainAxisSize.max,
      children: [
        const CustomImage(path: Kimages.forgotIcon),
        const SizedBox(height: 55.0),
        Align(
          alignment: Alignment.centerLeft,
          child: CustomText(text:
            Language.forgotPassword.capitalizeByWord(),
              height: 1, fontSize: 30, fontWeight: FontWeight.bold
          ),
        ),
        const SizedBox(height: 22),
        BlocBuilder<ForgotPasswordCubit, ForgotPasswordState>(
          builder: (context, state) {
            return Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const CustomText(
                  text: 'Telefon Numarası*',
                  fontSize: 14,
                  color: textGreyColor,
                ),
                const SizedBox(height: 8),
                Container(
                  decoration: BoxDecoration(
                    border: Border.all(color: borderColor),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 14,
                        ),
                        decoration: BoxDecoration(
                          color: tabBgColor,
                          border: Border(
                            right: BorderSide(color: borderColor),
                          ),
                        ),
                        child: const CustomText(
                          text: '+90',
                          fontWeight: FontWeight.w600,
                          color: blackColor,
                        ),
                      ),
                      Expanded(
                        child: TextFormField(
                          keyboardType: TextInputType.phone,
                          controller: bloc.phoneController,
                          inputFormatters: [
                            FilteringTextInputFormatter.digitsOnly,
                            LengthLimitingTextInputFormatter(10),
                          ],
                          decoration: const InputDecoration(
                            hintText: '5XXXXXXXXX',
                            border: InputBorder.none,
                            contentPadding: EdgeInsets.symmetric(
                              horizontal: 12,
                              vertical: 14,
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                if (state is ForgotPasswordFormValidateError) ...[
                  if (state.errors.email.isNotEmpty)
                    ErrorText(text: state.errors.email.first)
                ]
              ],
            );
          },
        ),
        const SizedBox(height: 28),
        BlocBuilder<ForgotPasswordCubit, ForgotPasswordState>(
            builder: (context, state) {
          if (state is ForgotPasswordStateLoading) {
            return const LoadingWidget();
          }
          return PrimaryButton(
            text: Language.sendOtp,
            onPressed: () {
              Utils.closeKeyBoard(context);
              bloc.forgotPassWord();
            },
          );
        }),
      ],
    );
  }
}
