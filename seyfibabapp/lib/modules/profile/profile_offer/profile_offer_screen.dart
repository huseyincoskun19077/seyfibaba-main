import 'package:flutter/material.dart';
import '/utils/language_string.dart';
import '/widgets/capitalized_word.dart';
import '../../../widgets/app_empty_state.dart';
import '../../../widgets/rounded_app_bar.dart';

class ProfileOfferScreen extends StatelessWidget {
  const ProfileOfferScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: RoundedAppBar(titleText: Language.myOffers.capitalizeByWord()),
      body: AppEmptyState(
        icon: Icons.local_offer_outlined,
        title: Language.noOfferAvailable,
      ),
    );
  }
}
