import 'dart:developer';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:pinput/pinput.dart';
import '../../widgets/custom_text.dart';
import '../../widgets/translate_form_text.dart';
import '/modules/authentication/widgets/sign_up_form.dart';
import '/widgets/loading_widget.dart';
import '/widgets/capitalized_word.dart';
import '../../../../utils/constants.dart';
import '../../../../widgets/primary_button.dart';
import '../../utils/k_images.dart';
import '../../utils/language_string.dart';
import '../../utils/utils.dart';
import '../../widgets/custom_image.dart';
import 'controller/forgot_password/forgot_password_cubit.dart';

class SetPasswordScreen extends StatefulWidget {
  const SetPasswordScreen({super.key});

  @override
  State<SetPasswordScreen> createState() => _SetPasswordScreenState();
}

class _SetPasswordScreenState extends State<SetPasswordScreen> {
  bool _passwordVisible = false;
  bool _passwordVisible2 = false;

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.of(context).size;
    final bloc = context.read<ForgotPasswordCubit>();
    return BlocListener<ForgotPasswordCubit, ForgotPasswordState>(
      listener: (context, state) {
        if (state is ForgotPasswordStateError) {
          Utils.errorSnackBar(context, state.errorMsg);
        } else if (state is ForgotPasswordStateLoaded) {
          Utils.showSnackBar(context, state.email);
        } else if (state is PasswordSetStateLoaded) {
          Navigator.pop(context);
          Utils.showSnackBar(context, state.message);
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
           Utils.formText(context, 'Password'),
              height: 1, fontSize: 30.0, fontWeight: FontWeight.bold
          ),
        ),
        const SizedBox(height: 22),
        _buildValidationCode(bloc),
        const SizedBox(height: 16),
        _buildPassword(bloc),
        const SizedBox(height: 16),
        _buildConfirmPass(bloc),
        const SizedBox(height: 28),
        BlocBuilder<ForgotPasswordCubit, ForgotPasswordState>(
          builder: (context, state) {
            if (state is ForgotPasswordStateLoading) {
              return const LoadingWidget();
            }

            return PrimaryButton(
              text: Language.updatePassword.capitalizeByWord(),
              onPressed: () {
                Utils.closeKeyBoard(context);
                if (bloc.codeController.text.isEmpty) {
                  Utils.errorSnackBar(context, 'Lütfen doğrulama kodunu girin.');
                } else if (bloc.paswordController.text.isEmpty) {
                  Utils.errorSnackBar(context, 'Lütfen yeni şifrenizi girin.');
                } else if (bloc.paswordConfirmController.text.isEmpty) {
                  Utils.errorSnackBar(context, 'Lütfen şifreyi tekrar girin.');
                } else if (bloc.paswordController.text !=
                    bloc.paswordConfirmController.text) {
                  Utils.errorSnackBar(context, 'Şifreler eşleşmiyor.');
                } else {
                  bloc.setNewPassword();
                }
                // Navigator.pushReplacementNamed(
                //     context, RouteNames.mainPage);
              },
            );
          },
        ),
        const SizedBox(height: 12),
        TextButton(
          onPressed: () {
            Utils.closeKeyBoard(context);
            bloc.resendOtp();
          },
          child: const CustomText(
            text: 'Kodu tekrar gönder',
            fontWeight: FontWeight.w600,
            color: blackColor,
          ),
        ),
      ],
    );
  }

  Widget _buildValidationCode(ForgotPasswordCubit bloc) {
    return Pinput(
      defaultPinTheme: PinTheme(
        height: 52,
        width: 52,
        textStyle: GoogleFonts.poppins(fontSize: 26, color: blackColor),
        decoration: BoxDecoration(
          color: Colors.white,
          border: Border.all(color: borderColor),
          borderRadius: BorderRadius.circular(8),
        ),
      ),
      autofocus: true,
      keyboardType: TextInputType.number,
      length: 6,
      validator: (String? s) {
        if (s == null || s.isEmpty) {
          return Language.enterCode.capitalizeByWord();
        }
        return null;
      },
      onChanged: (String s) {
        bloc.codeController.text = s;
        log(bloc.codeController.text);
      },
      onCompleted: (String s) {
        // log('onComplete');
      },
      onSubmitted: (String s) {
        // log('onSUbmit');
      },
    );
  }

  Widget _buildPassword(ForgotPasswordCubit bloc) {
    return BlocBuilder<ForgotPasswordCubit, ForgotPasswordState>(
      builder: (context, state) {
        // ForgotPasswordFormValidateError
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            TextFormField(
              keyboardType: TextInputType.visiblePassword,
              controller: bloc.paswordController,
              obscureText: !_passwordVisible,
              decoration: InputDecoration(
                hintText: Utils.formText(context, 'Password'),
                suffixIcon: IconButton(
                  icon: Icon(
                    _passwordVisible ? Icons.visibility : Icons.visibility_off,
                    color: grayColor,
                  ),
                  onPressed: () {
                    setState(() {
                      _passwordVisible = !_passwordVisible;
                    });
                  },
                ),
              ),
            ),
            if (state is ForgotPasswordFormValidateError) ...[
              if (state.errors.password.isNotEmpty)
                ErrorText(text: state.errors.password.first)
            ]
          ],
        );
      },
    );
  }

  Widget _buildConfirmPass(ForgotPasswordCubit bloc) {
    return BlocBuilder<ForgotPasswordCubit, ForgotPasswordState>(
      builder: (context, state) {
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [

            TranslateWidget(
              future: Utils.hintText(context, Language.confirmPassword),
              hintText: Language.confirmPassword,
              builder: (context, snap) {
                return TextFormField(
                  keyboardType: TextInputType.visiblePassword,
                  controller: bloc.paswordConfirmController,
                  obscureText: !_passwordVisible2,
                  decoration: InputDecoration(
                    hintText: snap,
                    // hintText: Language.confirmPassword.capitalizeByWord(),
                    suffixIcon: IconButton(
                      icon: Icon(
                        _passwordVisible2 ? Icons.visibility : Icons.visibility_off,
                        color: grayColor,
                      ),
                      onPressed: () {
                        setState(() {
                          _passwordVisible2 = !_passwordVisible2;
                        });
                      },
                    ),
                  ),
                );
              },
            ),

            if (state is ForgotPasswordFormValidateError) ...[
              if (state.errors.password.isNotEmpty)
                ErrorText(text: state.errors.password.first)
            ]
          ],
        );
      },
    );
  }
}
