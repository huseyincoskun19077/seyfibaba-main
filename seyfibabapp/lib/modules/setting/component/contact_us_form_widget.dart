import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:shop_o/widgets/loading_widget.dart';
import '../../../widgets/custom_text.dart';
import '../../../widgets/translate_form_text.dart';
import '../../authentication/widgets/sign_up_form.dart';
import '/widgets/capitalized_word.dart';
import '../../../utils/language_string.dart';
import '../../../utils/utils.dart';
import '../../../widgets/primary_button.dart';
import '../controllers/contact_us_form_bloc/contact_us_form_bloc.dart';

class ContactUsFormWidget extends StatelessWidget {
  const ContactUsFormWidget({super.key});

  @override
  Widget build(BuildContext context) {
    /*return BlocListener<ContactUsFormBloc, ContactUsFormState>(
      listener: (context, state) {
        if (state.status is ContactUsFormStatusError) {
          final status = state.status as ContactUsFormStatusError;
          ScaffoldMessenger.of(context)
            ..hideCurrentSnackBar()
            ..showSnackBar(
              SnackBar(
                  content: Text(status.errorMessage,
                      style: const TextStyle(color: Colors.red))),
            );
        } else if (state.status is ContactUsFormStatusLoaded) {
          final status = state.status as ContactUsFormStatusLoaded;
          ScaffoldMessenger.of(context)
            ..hideCurrentSnackBar()
            ..showSnackBar(SnackBar(content: Text(status.message)));
        }
      },
      child: const _FormWidget(),
    );*/
    return const _FormWidget();
  }
}

class _FormWidget extends StatelessWidget {
  const _FormWidget();

  @override
  Widget build(BuildContext context) {
    final contactUsFormBloc = context.read<ContactUsFormBloc>();
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        CustomText(
            text: Language.sendUsMessage.capitalizeByWord(),
            fontSize: 18,
            fontWeight: FontWeight.w600,
            height: 1.5),
        const SizedBox(height: 8),
        BlocBuilder<ContactUsFormBloc, ContactUsFormState>(
            // buildWhen: (previous, current) => false,
            builder: (context, state) {
          final editState = state.status;
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
                        contactUsFormBloc.add(ContactUsFormNameChange(value)),
                    decoration: InputDecoration(
                      hintText: snap,
                      // fillColor: borderColor.withOpacity(.10),
                    ),
                  );
                },
              ),
              if (editState is ContactUsFormValidateError) ...[
                if (editState.errors.name.isNotEmpty)
                  ErrorText(text: editState.errors.name.first),
              ]
            ],
          );
        }),
        const SizedBox(height: 16),
        BlocBuilder<ContactUsFormBloc, ContactUsFormState>(
            // buildWhen: (previous, current) => false,
            builder: (context, state) {
          final editState = state.status;
          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              TranslateWidget(
                future: Utils.hintText(context, Language.emailAddress),
                hintText: Language.emailAddress,
                builder: (context, snap) {
                  return TextFormField(
                    keyboardType: TextInputType.emailAddress,
                    initialValue: state.email,
                    onChanged: (value) =>
                        contactUsFormBloc.add(ContactUsFormEmailChange(value)),
                    decoration: InputDecoration(
                      hintText: snap,
                    ),
                  );
                },
              ),
              if (editState is ContactUsFormValidateError) ...[
                if (editState.errors.email.isNotEmpty)
                  ErrorText(text: editState.errors.email.first),
              ]
            ],
          );
        }),
        const SizedBox(height: 16),
        BlocBuilder<ContactUsFormBloc, ContactUsFormState>(
            buildWhen: (previous, current) => previous.phone != current.phone,
            builder: (context, state) {
              return TranslateWidget(
                future: Utils.hintText(context, Language.phoneNumber),
                hintText: Language.phoneNumber,
                builder: (context, snap) {
                  return TextFormField(
                    keyboardType: TextInputType.phone,
                    initialValue: state.phone,
                    onChanged: (value) =>
                        contactUsFormBloc.add(ContactUsFormPhoneChange(value)),
                    decoration: InputDecoration(
                      hintText: snap,
                      // fillColor: borderColor.withOpacity(.10),
                    ),
                  );
                },
              );
            }),
        const SizedBox(height: 16),
        BlocBuilder<ContactUsFormBloc, ContactUsFormState>(
            // buildWhen: (previous, current) =>
            //     previous.subject != current.subject,
            builder: (context, state) {
          final editState = state.status;
          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              TranslateWidget(
                future: Utils.hintText(context, Language.subject),
                hintText: Language.subject,
                builder: (context, snap) {
                  return TextFormField(
                    keyboardType: TextInputType.text,
                    initialValue: state.subject,
                    onChanged: (value) => contactUsFormBloc
                        .add(ContactUsFormSubjectChange(value)),
                    decoration: InputDecoration(
                      hintText: snap,
                    ),
                  );
                },
              ),
              if (editState is ContactUsFormValidateError) ...[
                if (editState.errors.subject.isNotEmpty)
                  ErrorText(text: editState.errors.subject.first),
              ]
            ],
          );
        }),
        const SizedBox(height: 16),
        BlocBuilder<ContactUsFormBloc, ContactUsFormState>(
            builder: (context, state) {
          final message = state.status;
          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              TranslateWidget(
                future: Utils.hintText(context, Language.message),
                hintText: Language.message,
                builder: (context, snap) {
                  return TextFormField(
                    keyboardType: TextInputType.multiline,
                    initialValue: state.message,
                    onChanged: (value) => contactUsFormBloc
                        .add(ContactUsFormMessageChange(value)),
                    decoration: InputDecoration(
                      hintText: snap,
                    ),
                    minLines: 5,
                    maxLines: null,
                  );
                },
              ),
              if (message is ContactUsFormValidateError) ...[
                if (message.errors.message.isNotEmpty)
                  ErrorText(text: message.errors.message.first),
              ]
            ],
          );
        }),
        const SizedBox(height: 20),
        BlocListener<ContactUsFormBloc, ContactUsFormState>(
          listener: (context, state) {
            // final contact = state.status;
            // if (contact is ContactUsFormStatusLoading) {
            //   Utils.loadingDialog(context);
            // } else {
            //   Utils.closeDialog(context);
            //   if (contact is ContactUsFormStatusError) {
            //     Utils.closeDialog(context);
            //     Utils.errorSnackBar(context, contact.errorMessage);
            //   } else if (contact is ContactUsFormStatusLoaded) {
            //     Utils.closeDialog(context);
            //     Navigator.of(context).pop();
            //     Utils.showSnackBar(context, 'Form Submit Success');
            //   }
            // }
            final s = state.status;
            if (s is ContactUsFormStatusError) {
              Utils.errorSnackBar(context, state.message);
            }
            if (s is ContactUsFormStatusLoaded) {
              Utils.showSnackBar(context, Language.formSubmitSuccess);
              Navigator.of(context).pop();
            }
          },
          child: BlocBuilder<ContactUsFormBloc, ContactUsFormState>(
            builder: (context, state) {
              return state.status is ContactLoading
                  ? const LoadingWidget()
                  : PrimaryButton(
                      text: Language.sendNow.capitalizeByWord(),
                      onPressed: () {
                        Utils.closeKeyBoard(context);
                        contactUsFormBloc.add(const ContactUsFormSubmit());
                      },
                    );
            },
          ),
          /*    child: PrimaryButton(
            text: Language.sendNow.capitalizeByWord(),
            onPressed: () {
              Utils.closeKeyBoard(context);
              contactUsFormBloc.add(const ContactUsFormSubmit());
            },
          ),*/
        ),
      ],
    );
  }
}
