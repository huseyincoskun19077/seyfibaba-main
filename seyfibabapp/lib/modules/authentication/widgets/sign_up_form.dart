import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl_phone_field/intl_phone_field.dart';

import '../../../widgets/custom_text.dart';
import '../../../widgets/translate_form_text.dart';
import '/widgets/capitalized_word.dart';
import '../../../../utils/constants.dart';
import '../../../../widgets/primary_button.dart';
import '../../../utils/language_string.dart';
import '../../../utils/utils.dart';
import '../../animated_splash_screen/controller/app_setting_cubit/app_setting_cubit.dart';
import '../controller/sign_up/sign_up_bloc.dart';

class SignUpForm extends StatefulWidget {
  const SignUpForm({super.key});

  @override
  State<SignUpForm> createState() => _SignUpFormState();
}

class _SignUpFormState extends State<SignUpForm> {
  @override
  Widget build(BuildContext context) {
    final bloc = context.read<SignUpBloc>();
    final appSettingBloc =
        context.read<AppSettingCubit>().settingModel!.setting;
    final dialCode = Utils.findDialCodeBy(appSettingBloc.defaultPhoneCode);
    bloc.add(SignUpEventCountryCode(dialCode));
    final size = MediaQuery.of(context).size;
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: SingleChildScrollView(
        physics: const BouncingScrollPhysics(),
        child: Column(
          children: [
            const SizedBox(height: 12),
            BlocBuilder<SignUpBloc, SignUpModelState>(
              builder: (context, state) {
                final s = state.state;
                return Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    TranslateWidget(
                      future: Utils.hintText(context, Language.name),
                      hintText: Language.name,
                      builder: (context, snap) {
                        return TextFormField(
                          keyboardType: TextInputType.name,
                          initialValue: state.name,
                          onChanged: (value) =>
                              bloc.add(SignUpEventName(value)),
                          decoration: InputDecoration(hintText: snap),
                        );
                      },
                    ),
                    if (s is SignUpStateFormValidationError) ...[
                      if (s.errors.name.isNotEmpty)
                        ErrorText(text: s.errors.name.first),
                    ]
                  ],
                );
              },
            ),
            const SizedBox(height: 16),
            BlocBuilder<SignUpBloc, SignUpModelState>(
              builder: (context, state) {
                final s = state.state;
                return Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    TranslateWidget(
                      future: Utils.hintText(context, Language.email),
                      hintText: Language.email,
                      builder: (context, snap) {
                        return TextFormField(
                          keyboardType: TextInputType.emailAddress,
                          initialValue: state.email,
                          onChanged: (value) =>
                              bloc.add(SignUpEventEmail(value)),
                          decoration: InputDecoration(hintText: snap),
                        );
                      },
                    ),
                    if (bloc.state.name.isNotEmpty)
                      if (s is SignUpStateFormValidationError) ...[
                        if (s.errors.email.isNotEmpty)
                          ErrorText(text: s.errors.email.first),
                      ]
                  ],
                );
              },
            ),
            const SizedBox(height: 16),
            if (appSettingBloc.phoneNumberRequired == 1) ...[
              BlocBuilder<SignUpBloc, SignUpModelState>(
                builder: (context, state) {
                  final validate = state.state;
                  return Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      IntlPhoneField(
                        initialCountryCode: appSettingBloc.defaultPhoneCode,
                        initialValue: state.phoneNumber,
                        showDropdownIcon: true,
                        disableLengthCheck: true,
                        flagsButtonMargin:
                            const EdgeInsets.symmetric(horizontal: 6.0),
                        flagsButtonPadding: const EdgeInsets.only(bottom: 4.0),
                        dropdownTextStyle: GoogleFonts.inter(
                            fontSize: 16.0, fontWeight: FontWeight.w500),
                        onChanged: (phone) {
                          // debugPrint('number ${phone.number}');
                          // debugPrint('c-code ${phone.countryCode}');
                          // signUpBloc.add(
                          //     SignUpEventCountryCode(phone.countryCode));
                          bloc.add(SignUpEventPhone(phone.number));
                        },
                        onCountryChanged: (country) {
                          // debugPrint('country-name ${country.name}');
                          bloc.add(SignUpEventCountryCode(country.dialCode));
                        },
                        dropdownIcon: const Icon(
                            Icons.keyboard_arrow_down_outlined,
                            color: grayColor),
                        inputFormatters: [
                          FilteringTextInputFormatter.digitsOnly,
                          FilteringTextInputFormatter.deny('a'),
                        ],
                      ),
                      if (appSettingBloc.enableUserRegister == 1 &&
                          state.email.isNotEmpty)
                        if (validate is SignUpStateFormValidationError) ...[
                          if (validate.errors.phone.isNotEmpty)
                            ErrorText(text: validate.errors.phone.first),
                        ]
                    ],
                  );
                },
              ),
              Utils.verticalSpace(16.0),
            ] else ...[
              Utils.verticalSpace(0.0)
            ],
            // if (appSettingBloc.phoneNumberRequired == 1) ...[
            //   const SizedBox(height: 16),
            //   BlocBuilder<SignUpBloc, SignUpModelState>(
            //     builder: (context, state) {
            //       final s = state.state;
            //       return Column(
            //         crossAxisAlignment: CrossAxisAlignment.start,
            //         children: [
            //           TextFormField(
            //             keyboardType: TextInputType.phone,
            //             initialValue: state.phone,
            //             onChanged: (value) => bloc.add(SignUpEventPhone(value)),
            //             decoration: InputDecoration(
            //               hintText: Language.phoneNumber.capitalizeByWord(),
            //               prefixIcon: CountryCodePicker(
            //                 padding: const EdgeInsets.only(bottom: 8),
            //                 onChanged: (country) {
            //                   bloc.add(
            //                     SignUpEventCountryCode(country.dialCode ?? ''),
            //                   );
            //                   // profileEdBlc
            //                   //     .changePhoneCode(country.dialCode ?? '');
            //                 },
            //                 flagWidth: 35,
            //                 initialSelection: appSettingBloc.defaultPhoneCode,
            //                 favorite: const ['+880', 'BD'],
            //                 showCountryOnly: false,
            //                 showOnlyCountryWhenClosed: false,
            //                 alignLeft: false,
            //               ),
            //               // hintText: Language.phoneNumber.capitalizeByWord(),
            //             ),
            //           ),
            //           if (appSettingBloc.enableUserRegister == 1 &&
            //               state.email.isNotEmpty)
            //             if (s is SignUpStateFormValidationError) ...[
            //               if (s.errors.phone.isNotEmpty)
            //                 ErrorText(text: s.errors.phone.first),
            //             ]
            //         ],
            //       );
            //     },
            //   ),
            // ],
            BlocBuilder<SignUpBloc, SignUpModelState>(
              builder: (context, state) {
                final s = state.state;
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
                              bloc.add(SignUpEventPassword(value)),
                          obscureText: state.showPassword,
                          decoration: InputDecoration(
                            hintText: snap,
                            suffixIcon: IconButton(
                                icon: Icon(
                                  state.showPassword
                                      ? Icons.visibility
                                      : Icons.visibility_off,
                                  color: grayColor,
                                ),
                                onPressed: () => bloc.add(
                                    SignUpEventShowPassword(
                                        state.showPassword))),
                          ),
                        );
                      },
                    ),
                    if ((appSettingBloc.enableUserRegister == 1 ||
                            state.phone.isNotEmpty) &&
                        (state.email.isNotEmpty && state.password.isEmpty))
                      if (s is SignUpStateFormValidationError) ...[
                        if (s.errors.password.isNotEmpty)
                          ErrorText(text: s.errors.password.first),
                      ]
                  ],
                );
              },
            ),
            const SizedBox(height: 16),
            BlocBuilder<SignUpBloc, SignUpModelState>(
              builder: (context, state) {
                final s = state.state;
                return Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    TranslateWidget(
                      future: Utils.hintText(context, Language.confirmPassword),
                      hintText: Language.confirmPassword,
                      builder: (context, snap) {
                        return TextFormField(
                          keyboardType: TextInputType.visiblePassword,
                          initialValue: state.passwordConfirmation,
                          onChanged: (value) =>
                              bloc.add(SignUpEventPasswordConfirm(value)),
                          obscureText: state.showConPassword,
                          decoration: InputDecoration(
                            hintText: snap,
                            suffixIcon: IconButton(
                              icon: Icon(
                                state.showConPassword
                                    ? Icons.visibility
                                    : Icons.visibility_off,
                                color: grayColor,
                              ),
                              onPressed: () => bloc.add(
                                  SignUpEventShowConPassword(
                                      state.showConPassword)),
                            ),
                          ),
                        );
                      },
                    ),
                    if (state.passwordConfirmation.isNotEmpty ||
                        (state.passwordConfirmation.isEmpty &&
                            !state.passwordConfirmation
                                .contains(state.password)))
                      if (s is SignUpStateFormValidationError) ...[
                        if (s.errors.password.isNotEmpty)
                          ErrorText(text: s.errors.password.first),
                      ]
                  ],
                );
              },
            ),
            const SizedBox(height: 8),
            _buildRememberMe(),
            const SizedBox(height: 25),
            BlocBuilder<SignUpBloc, SignUpModelState>(
              buildWhen: (previous, current) => previous.state != current.state,
              builder: (context, state) {
                if (state.state is SignUpStateLoading) {
                  return const Center(child: CircularProgressIndicator());
                }
                return PrimaryButton(
                  text: Language.singUp.capitalizeByWord(),
                  onPressed: () {
                    Utils.closeKeyBoard(context);
                    bloc.add(SignUpEventSubmit());
                  },
                );
              },
            ),
            // const SizedBox(height: 28),
            // const GuestButton(),
            SizedBox(height: size.height * 0.2),
          ],
        ),
      ),
    );
  }

  Widget _buildRememberMe() {
    return BlocBuilder<SignUpBloc, SignUpModelState>(
      buildWhen: (previous, current) => previous.agree != current.agree,
      builder: (context, state) {
        return Padding(
          padding: Utils.only(top: 4.0),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Container(
                height: Utils.vSize(24.0),
                width: Utils.vSize(24.0),
                margin: Utils.only(right: 10.0),
                child: Checkbox(
                  value: state.agree == 1,
                  checkColor: whiteColor,
                  activeColor: blackColor,
                  onChanged: (v) {
                    if (v == null) return;
                    context.read<SignUpBloc>().add(SignUpEventAgree(v ? 1 : 0));
                  },
                ),
              ),
              CustomText(
                  text: Language.signUpCondition.capitalizeByWord(),
                  color: blackColor.withOpacity(0.5))
            ],
          ),
        );
      },
    );
  }
}

class ErrorText extends StatelessWidget {
  const ErrorText({super.key, required this.text});

  final String text;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: Utils.only(top: 6.0),
      child: CustomText(
        text: '* $text',
        color: redColor,
        fontWeight: FontWeight.w500,
      ),
    );
  }
}
