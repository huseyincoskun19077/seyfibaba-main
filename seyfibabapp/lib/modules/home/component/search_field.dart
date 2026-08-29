import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../../utils/utils.dart';
import '../../animated_splash_screen/controller/translate_cubit/translate_cubit.dart';
import '../../animated_splash_screen/controller/translate_cubit/translate_state_model.dart';
import '../../../utils/language_string.dart';
import '../widgets/home_theme.dart';

class SearchField extends StatelessWidget {
  const SearchField({super.key});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () {
        Navigator.pushNamed(context, RouteNames.productSearchScreen);
      },
      child: BlocBuilder<TranslateCubit, TranslateStateModel>(
        builder: (context, state) {
          return Container(
            height: 46,
            decoration: BoxDecoration(
              color: HomeTheme.bg,
              borderRadius: BorderRadius.circular(HomeTheme.radiusSm),
              border: Border.all(color: HomeTheme.headerBorder),
            ),
            padding: const EdgeInsets.symmetric(horizontal: 14),
            child: Row(
              children: [
                Icon(
                  Icons.search_rounded,
                  color: HomeTheme.textMuted.withValues(alpha: 0.85),
                  size: 22,
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    '${Language.searchProductHint}',
                    style: TextStyle(
                      color: HomeTheme.textMuted.withValues(alpha: 0.9),
                      fontSize: 14,
                      fontWeight: FontWeight.w400,
                    ),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: whiteColor,
                    borderRadius: BorderRadius.circular(6),
                    border: Border.all(
                      color: HomeTheme.headerBorder,
                    ),
                  ),
                  child: Icon(
                    Icons.tune_rounded,
                    size: 16,
                    color: HomeTheme.textMuted.withValues(alpha: 0.9),
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}
