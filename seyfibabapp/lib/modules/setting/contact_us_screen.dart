import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_font_awesome_web_names/flutter_font_awesome.dart';
import 'package:shop_o/widgets/custom_image.dart';
import 'package:shop_o/widgets/fetch_error_text.dart';
import 'package:shop_o/widgets/loading_widget.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:webview_flutter/webview_flutter.dart';

import '../../utils/k_images.dart';
import '../../widgets/custom_text.dart';
import '/widgets/capitalized_word.dart';
import '../../utils/constants.dart';
import '../../utils/language_string.dart';
import '../../utils/utils.dart';
import '../../widgets/rounded_app_bar.dart';
import 'component/contact_us_form_widget.dart';
import 'controllers/contact_us_cubit/contact_us_cubit.dart';
import 'model/contact_us_mode.dart';

class ContactUsScreen extends StatefulWidget {
  const ContactUsScreen({super.key});

  @override
  State<ContactUsScreen> createState() => _ContactUsScreenState();
}

class _ContactUsScreenState extends State<ContactUsScreen> {
  late ContactUsCubit cCubit;
  @override

  void initState() {
    cCubit = context.read<ContactUsCubit>();
    cCubit.getContactUsContent();
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: RoundedAppBar(titleText: Language.contactUs.capitalizeByWord()),
      body:  SingleChildScrollView(
        padding: EdgeInsets.all(20),
        child: Column(
          children: [
            ContactUsFormWidget(),
            ContactUsComponent(),


          ],
        ),
      ),
    );
  }
}

class ContactUsComponent extends StatefulWidget {
  const ContactUsComponent({super.key});

  @override
  State<ContactUsComponent> createState() => _ContactUsComponentState();
}

class _ContactUsComponentState extends State<ContactUsComponent> {
  late WebViewController controller;

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<ContactUsCubit, ContactUsState>(
      builder: (context, state) {
        if (state is ContactUsStateLoaded) {
          final aboutUsData = state.contactModel;
          return _buildLoadedWidget(aboutUsData);
        } else if (state is ContactUsStateLoading) {
          return const LoadingWidget();
        } else if (state is ContactUsStateError) {
          return Padding(
            padding: const EdgeInsets.all(18.0),
            child: FetchErrorText(text: state.errorMessage),
          );
        }
        return const SizedBox();
      },
    );
  }

  Widget _buildLoadedWidget(ContactModel aboutUsData) {
    return Column(
      mainAxisAlignment: MainAxisAlignment.spaceEvenly,
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const SizedBox(height: 30, width: double.infinity),
        _buildSingleInfo(Language.emailAddress.capitalizeByWord(), Icons.email,
            aboutUsData.contactUsModel!.email),
        Divider(height: 20, color: grayColor.withOpacity(0.05)),
        _buildSingleInfo(Language.phoneNumber.capitalizeByWord(), Icons.call,
            aboutUsData.contactUsModel!.phone),
        Divider(height: 20, color: grayColor.withOpacity(0.05)),
        _buildSingleInfo(Language.address.capitalizeByWord(), Icons.location_on,
            aboutUsData.contactUsModel!.address),

        Utils.verticalSpace(20.0),
        // Row(
        //   children: [
        //     ...List.generate(aboutUsData.socialLinks!.length, (index) {
        //       final social = aboutUsData.socialLinks![index];
        //
        //       // Function returns a Widget based on icon string
        //       Widget getFlutterIcon(String? iconName) {
        //         switch (iconName) {
        //           case 'fab fa-facebook-f':
        //             return CustomImage(path: Kimages.facebookIcon, height: 50.0,);
        //           case 'fab fa-twitter':
        //             return CustomImage(path: Kimages.twitterIcon, height: 50.0,);
        //           case 'fab fa-linkedin':
        //             return CustomImage(path: Kimages.linkdinIcon, height: 50.0,);
        //           case 'fab fa-instagram':
        //             return CustomImage(path: Kimages.instragramIcon, height: 50.0,);
        //           case 'fab fa-youtube':
        //             return CustomImage(path: Kimages.youtubeIcon, height: 50.0,);
        //           default:
        //             return CustomImage(path: Kimages.google,height: 50.0,);
        //         }
        //       }
        //
        //       return Padding(
        //         padding: const EdgeInsets.only(right: 20.0, top: 20.0, bottom: 20.0),
        //         child: GestureDetector(
        //           onTap: () {
        //             _launchUrl(social.link);
        //           },
        //           child: getFlutterIcon(social.icon),
        //         ),
        //       );
        //     }),
        //   ],
        // )

        // Container(
        //   color: redColor,
        //   height: 200,
        //   width: MediaQuery.of(context).size.width,
        //   child: WebView(
        //     zoomEnabled: true,
        //     javascriptMode: JavascriptMode.unrestricted,
        //     onWebViewCreated: (WebViewController ctr) {
        //       controller = ctr;
        //       loadMap(aboutUsData.map);
        //     },
        //   ),
        // ),
      ],
    );
  }

  Future<void> _launchUrl(String url) async {
    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else {
      debugPrint('Could not launch $url');
    }
  }

  void loadMap(String iframe) {
    final uri = Uri.dataFromString(
      iframe,
      mimeType: 'text/html',
      encoding: Encoding.getByName('utf-8'),
    ).toString();
    controller.loadRequest(Uri.parse(uri));
  }

  Widget _buildSingleInfo(String header, IconData icon, content) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 30.0, color: blackColor),
        CustomText(text: header, fontSize: 16.0, fontWeight: FontWeight.w600),
        CustomText(
            isTranslate: false,
            text: content,
            fontWeight: FontWeight.w600,
            color: iconGreyColor),
      ],
    );
  }
}
