import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:shop_o/utils/k_images.dart';
import 'package:shop_o/widgets/custom_image.dart';
import 'package:shop_o/widgets/custom_text.dart';

import '../../../widgets/translate_form_text.dart';
import '/modules/authentication/widgets/sign_up_form.dart';
import '/widgets/capitalized_word.dart';
import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../../utils/language_string.dart';
import '../../../utils/utils.dart';
import '../../../widgets/primary_button.dart';
import '../controller/login/login_bloc.dart';
import 'guest_button.dart';

class SignInForm extends StatefulWidget {
  const SignInForm({super.key});

  @override
  State<SignInForm> createState() => _SignInFormState();
}

class _SignInFormState extends State<SignInForm> {
  @override
  Widget build(BuildContext context) {
    final loginBloc = context.read<LoginBloc>();
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: Form(
        key: loginBloc.formKey,
        child: Column(
          children: [
            const SizedBox(height: 12),
            _buildLoginTypeToggle(loginBloc),
            const SizedBox(height: 16),
            BlocBuilder<LoginBloc, LoginModelState>(
              builder: (context, state) {
                final login = state.state;
                return Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (state.loginInputType == LoginInputType.email)
                      TranslateWidget(
                        future: Utils.hintText(context, Language.email),
                        hintText: Language.email,
                        builder: (context, snap) {
                          return TextFormField(
                            key: const ValueKey('login-email'),
                            keyboardType: TextInputType.emailAddress,
                            initialValue: state.text,
                            onChanged: (value) =>
                                loginBloc.add(LoginEvenEmailOrPhone(value)),
                            decoration: InputDecoration(
                              hintText: snap.isNotEmpty ? snap : 'ornek@email.com',
                              labelText: 'E-posta Adresi*',
                            ),
                          );
                        },
                      )
                    else
                      Column(
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
                                    key: const ValueKey('login-phone'),
                                    keyboardType: TextInputType.phone,
                                    inputFormatters: [
                                      FilteringTextInputFormatter.digitsOnly,
                                      LengthLimitingTextInputFormatter(10),
                                    ],
                                    initialValue: state.text,
                                    onChanged: (value) => loginBloc.add(
                                      LoginEvenEmailOrPhone(value),
                                    ),
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
                        ],
                      ),
                    if (login is LoginStateFormInvalid) ...[
                      if (login.error.email.isNotEmpty)
                        ErrorText(text: login.error.email.first)
                    ]
                  ],
                );
              },
            ),
            const SizedBox(height: 16),
            BlocBuilder<LoginBloc, LoginModelState>(
              builder: (context, state) {
                final login = state.state;
                return Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    TranslateWidget(
                      future: Utils.hintText(context, Language.password),
                      hintText: Language.password,
                      builder: (context, snap) {
                        return TextFormField(
                          keyboardType: TextInputType.visiblePassword,
                          initialValue: state.password,
                          onChanged: (value) =>
                              loginBloc.add(LoginEventPassword(value)),
                          obscureText: state.showPassword,
                          decoration: InputDecoration(
                            hintText: snap,
                            labelText: '${Language.password.capitalizeByWord()}*',
                            suffixIcon: IconButton(
                              icon: Icon(
                                state.showPassword
                                    ? Icons.visibility
                                    : Icons.visibility_off,
                                color: grayColor,
                              ),
                              onPressed: () => loginBloc
                                  .add(ShowPasswordEvent(state.showPassword)),
                            ),
                          ),
                        );
                      },
                    ),
                    if (state.text != '')
                      if (login is LoginStateFormInvalid) ...[
                        if (login.error.password.isNotEmpty)
                          ErrorText(text: login.error.password.first)
                      ]
                  ],
                );
              },
            ),
            const SizedBox(height: 8),
            _buildRememberMe(),
            const SizedBox(height: 25),
            BlocBuilder<LoginBloc, LoginModelState>(
              buildWhen: (previous, current) => previous.state != current.state,
              builder: (context, state) {
                if (state.state is LoginStateLoading) {
                  return const Center(child: CircularProgressIndicator());
                }
                return PrimaryButton(
                  text: Language.login.capitalizeByWord(),
                  onPressed: () {
                    Utils.closeKeyBoard(context);
                    loginBloc.add(const LoginEventSubmit());
                  },
                );
              },
            ),
            const SizedBox(height: 20),
            if (defaultTargetPlatform == TargetPlatform.android ||
                defaultTargetPlatform == TargetPlatform.iOS) ...[
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                Expanded(
                  child: Container(
                    color: borderColor,
                    width: double.infinity,
                    height: 1,
                    margin: const EdgeInsets.symmetric(horizontal: 10),
                  ),
                ),
                const CustomText(
                  text: "VEYA",
                  color: textGreyColor,
                  fontSize: 16,
                  height: 1.5,
                  fontWeight: FontWeight.w500,
                ),
                Expanded(
                  child: Container(
                    color: borderColor,
                    width: double.infinity,
                    height: 1,
                    margin: const EdgeInsets.symmetric(horizontal: 10),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            BlocBuilder<LoginBloc, LoginModelState>(
              buildWhen: (previous, current) => previous.state != current.state,
              builder: (context, state) {
                if (state.state is GoogleStateLoading ||
                    state.state is AppleStateLoading) {
                  return const Center(child: CircularProgressIndicator());
                }
                return Column(
                  children: [
                    GestureDetector(
                      onTap: () {
                        Utils.closeKeyBoard(context);
                        loginBloc.add(const GoogleSignInEvent());
                      },
                      child: Container(
                        padding: Utils.symmetric(v: 14.0),
                        decoration: BoxDecoration(
                          borderRadius: Utils.borderRadius(r: 10.0),
                          border: Border.all(color: borderColor),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Container(
                              height: 25.0,
                              width: 25.0,
                              margin: Utils.only(right: 14.0),
                              child: const CustomImage(path: Kimages.google),
                            ),
                            const CustomText(
                              text: 'Google ile devam et',
                              fontWeight: FontWeight.w600,
                              fontSize: 14.0,
                              color: blackColor,
                            )
                          ],
                        ),
                      ),
                    ),
                    if (defaultTargetPlatform == TargetPlatform.iOS) ...[
                      const SizedBox(height: 12),
                      GestureDetector(
                        onTap: () {
                          Utils.closeKeyBoard(context);
                          loginBloc.add(const AppleSignInEvent());
                        },
                        child: Container(
                          padding: Utils.symmetric(v: 14.0),
                          decoration: BoxDecoration(
                            borderRadius: Utils.borderRadius(r: 10.0),
                            color: blackColor,
                          ),
                          child: const Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(Icons.apple, color: Colors.white, size: 22),
                              SizedBox(width: 12),
                              CustomText(
                                text: 'Apple ile devam et',
                                fontWeight: FontWeight.w600,
                                fontSize: 14.0,
                                color: Colors.white,
                              )
                            ],
                          ),
                        ),
                      ),
                    ],
                  ],
                );
              },
            ),
            const SizedBox(height: 25),
            ],
            const GuestButton(),
          ],
        ),
      ),
    );
  }

  Widget _buildLoginTypeToggle(LoginBloc loginBloc) {
    return BlocBuilder<LoginBloc, LoginModelState>(
      builder: (context, state) {
        final isEmail = state.loginInputType == LoginInputType.email;
        return Container(
          padding: const EdgeInsets.all(4),
          decoration: BoxDecoration(
            color: tabBgColor,
            borderRadius: BorderRadius.circular(999),
          ),
          child: Row(
            children: [
              Expanded(
                child: _loginTypeChip(
                  label: 'E-posta',
                  icon: Icons.email_outlined,
                  selected: isEmail,
                  onTap: () => loginBloc.add(
                    const LoginEventLoginType(LoginInputType.email),
                  ),
                ),
              ),
              Expanded(
                child: _loginTypeChip(
                  label: 'Telefon',
                  icon: Icons.phone_outlined,
                  selected: !isEmail,
                  onTap: () => loginBloc.add(
                    const LoginEventLoginType(LoginInputType.phone),
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _loginTypeChip({
    required String label,
    required IconData icon,
    required bool selected,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(vertical: 10),
        decoration: BoxDecoration(
          color: selected ? whiteColor : Colors.transparent,
          borderRadius: BorderRadius.circular(999),
          boxShadow: selected
              ? [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.08),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  ),
                ]
              : null,
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 16, color: selected ? blackColor : textGreyColor),
            const SizedBox(width: 6),
            CustomText(
              text: label,
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: selected ? blackColor : textGreyColor,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildRememberMe() {
    final loginBloc = context.read<LoginBloc>();
    return BlocBuilder<LoginBloc, LoginModelState>(
      builder: (context, state) {
        return Padding(
          padding: Utils.only(top: 10.0),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Container(
                    height: Utils.vSize(24.0),
                    width: Utils.vSize(24.0),
                    margin: Utils.only(right: 10.0),
                    child: Checkbox(
                      value: state.rememberMe,
                      checkColor: whiteColor,
                      activeColor: blackColor,
                      onChanged: (val) {
                        loginBloc.add(RememberMeEvent(state.rememberMe));
                      },
                    ),
                  ),
                  CustomText(
                    text: Language.rememberMe.capitalizeByWord(),
                    color: blackColor.withOpacity(.5),
                    fontSize: 15.0,
                  ),
                ],
              ),
              InkWell(
                onTap: () {
                  Navigator.pushNamed(context, RouteNames.forgotScreen);
                },
                child: CustomText(
                  text: '${Language.forgotPassword.capitalizeByWord()}?',
                  color: redColor,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}
