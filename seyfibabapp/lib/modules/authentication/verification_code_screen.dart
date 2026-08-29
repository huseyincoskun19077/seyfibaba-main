import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:pinput/pinput.dart';
import 'package:shop_o/utils/utils.dart';
import '../../widgets/custom_text.dart';
import '/widgets/loading_widget.dart';

import '/widgets/capitalized_word.dart';
import '../../../../utils/constants.dart';
import '../../../../widgets/primary_button.dart';
import '../../core/router_name.dart';
import '../../utils/k_images.dart';
import '../../utils/language_string.dart';
import '../../widgets/custom_image.dart';
import 'controller/login/login_bloc.dart';
import 'controller/sign_up/sign_up_bloc.dart';

class VerificationCodeScreen extends StatefulWidget {
  const VerificationCodeScreen({super.key});

  @override
  State<VerificationCodeScreen> createState() => _VerificationCodeScreenState();
}

class _VerificationCodeScreenState extends State<VerificationCodeScreen> {
  bool _isValid = false;
  final pinController = TextEditingController();

  void _submitCode(String code) {
    final signUpState = context.read<SignUpBloc>().state;
    if (signUpState.state is SignUpStateLoading) return;

    if (signUpState.awaitingOtp) {
      context.read<SignUpBloc>().add(SignUpEventVerifyOtp(code));
      return;
    }

    context.read<LoginBloc>().add(AccountActivateCodeSubmit(code));
  }

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.of(context).size;

    return MultiBlocListener(
      listeners: [
        BlocListener<SignUpBloc, SignUpModelState>(
          listenWhen: (previous, current) => previous.state != current.state,
          listener: (context, state) {
            if (state.state is SignUpStateFormError) {
              final error = state.state as SignUpStateFormError;
              Utils.errorSnackBar(context, error.errorMsg);
              if (!state.awaitingOtp) {
                Navigator.pop(context);
              }
            } else if (state.state is SignUpStateFormValidationError) {
              final error = state.state as SignUpStateFormValidationError;
              final message = [
                ...error.errors.message,
                ...error.errors.phone,
                ...error.errors.email,
                ...error.errors.password,
              ].firstWhere((item) => item.isNotEmpty, orElse: () => 'Doğrulama başarısız.');
              Utils.errorSnackBar(context, message);
            } else if (state.state is SignUpStateLoggedIn) {
              final loggedIn = state.state as SignUpStateLoggedIn;
              context.read<LoginBloc>().user = loggedIn.user;
              Navigator.pushNamedAndRemoveUntil(
                context,
                RouteNames.mainPage,
                (route) => false,
              );
            }
          },
        ),
        BlocListener<LoginBloc, LoginModelState>(
          listenWhen: (previous, current) => previous.state != current.state,
          listener: (context, state) {
            if (state.state is LoginStateError) {
              final status = state.state as LoginStateError;
              Utils.errorSnackBar(context, status.errorMsg);
            } else if (state.state is AccountActivateSuccess) {
              final messageState = state.state as AccountActivateSuccess;
              Utils.showSnackBar(context, messageState.msg);
              Navigator.pop(context);
            } else if (state.state is LoginStateLoaded) {
              Navigator.pushNamedAndRemoveUntil(
                context,
                RouteNames.mainPage,
                (route) => false,
              );
            }
          },
        ),
      ],
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
              child: SingleChildScrollView(child: _buildForm()),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildForm() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.center,
      mainAxisAlignment: MainAxisAlignment.center,
      mainAxisSize: MainAxisSize.max,
      children: [
        const CustomImage(path: Kimages.forgotIcon),
        const SizedBox(height: 55.0),
        Align(
          alignment: Alignment.centerLeft,
          child: CustomText(
              text: Language.verificationCode,
              height: 1,
              fontSize: 30,
              fontWeight: FontWeight.bold),
        ),
        const SizedBox(height: 22),
        Pinput(
          controller: pinController,
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
            if (s.length == 6) {
              _isValid = true;
            } else {
              _isValid = false;
            }
            setState(() {});
          },
          onCompleted: _submitCode,
          onSubmitted: (String s) {},
        ),
        const SizedBox(height: 28),
        BlocBuilder<SignUpBloc, SignUpModelState>(
          builder: (context, signUpState) {
            return BlocBuilder<LoginBloc, LoginModelState>(
              builder: (context, loginState) {
                if (signUpState.state is SignUpStateLoading ||
                    loginState.state is LoginStateLoading) {
                  return const LoadingWidget();
                }
                return Column(
                  children: [
                    _buildContinueBtn(),
                    if (signUpState.awaitingOtp) ...[
                      const SizedBox(height: 16),
                      TextButton(
                        onPressed: () {
                          context.read<SignUpBloc>().add(SignUpEventResendOtp());
                        },
                        child: CustomText(
                          text: Language.resend.capitalizeByWord(),
                          color: blackColor,
                        ),
                      ),
                    ],
                  ],
                );
              },
            );
          },
        ),
      ],
    );
  }

  Widget _buildContinueBtn() {
    return PrimaryButton(
      text: 'Continue',
      onPressed: () {
        if (_isValid) {
          _submitCode(pinController.text);
        } else {
          Utils.showSnackBar(context, 'Please enter valid OTP');
        }
      },
    );
  }
}
