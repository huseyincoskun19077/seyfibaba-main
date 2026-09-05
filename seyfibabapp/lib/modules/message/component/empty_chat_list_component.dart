import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../../utils/constants.dart';
import '../../../utils/k_images.dart';
import '../../../utils/language_string.dart';
import '../../../widgets/custom_image.dart';
import '../../../widgets/primary_button.dart';

class EmptyChatListComponent extends StatelessWidget {
  const EmptyChatListComponent({
    super.key,
  });

  @override
  Widget build(BuildContext context) {
    return SliverToBoxAdapter(
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            const CustomImage(path: Kimages.emptyChatList),
            const SizedBox(height: 43),
            Text(
              Language.noMessageFound,
              style: GoogleFonts.poppins(
                  fontSize: 22, fontWeight: FontWeight.bold, height: 2),
            ),
            Text(
              Language.emptyInboxHint,
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 16, color: iconGreyColor, height: 1.5),
            ),
            const SizedBox(height: 24),
            SizedBox(
              width: 200,
              child: PrimaryButton(
                  text: Language.startShopping, onPressed: () {}),
            )
          ],
        ),
      ),
    );
  }
}
