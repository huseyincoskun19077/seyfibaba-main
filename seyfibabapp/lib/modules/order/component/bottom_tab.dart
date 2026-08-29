import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../utils/language_string.dart';
import '../../../widgets/capitalized_word.dart';
import '../../home/controller/cubit/product/product_state_model.dart';
import '../../home/widgets/home_theme.dart';
import '../controllers/order/order_cubit.dart';

class BottomTab extends StatefulWidget implements PreferredSizeWidget {
  const BottomTab({super.key});

  @override
  State<BottomTab> createState() => _BottomTabState();

  @override
  Size get preferredSize => const Size.fromHeight(52);
}

class _BottomTabState extends State<BottomTab> {
  final ScrollController _scrollController = ScrollController();

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _scrollToIndex(int index) {
    const itemWidth = 128.0;
    final screenWidth = MediaQuery.of(context).size.width;
    final offset = (index * itemWidth) - ((screenWidth - itemWidth) / 2);
    if (!_scrollController.hasClients) return;
    _scrollController.animateTo(
      offset.clamp(0.0, _scrollController.position.maxScrollExtent),
      duration: const Duration(milliseconds: 280),
      curve: Curves.easeOutCubic,
    );
  }

  @override
  Widget build(BuildContext context) {
    final bCubit = context.read<OrderCubit>();

    return BlocBuilder<OrderCubit, ProductStateModel>(
      builder: (context, state) {
        final tabs = [
          'Tümü',
          Language.pending.capitalizeByWord(),
          Language.progress.capitalizeByWord(),
          Language.delivered.capitalizeByWord(),
          Language.completed.capitalizeByWord(),
          Language.declined.capitalizeByWord(),
        ];
        final count = [
          bCubit.orderList.length,
          bCubit.pending.length,
          bCubit.progress.length,
          bCubit.delivered.length,
          bCubit.completed.length,
          bCubit.declined.length,
        ];

        return Container(
          color: HomeTheme.header,
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
          child: SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            physics: const BouncingScrollPhysics(),
            controller: _scrollController,
            child: Row(
              children: List.generate(tabs.length, (index) {
                final active = state.currentIndex == index;
                return Padding(
                  padding: EdgeInsets.only(right: index == tabs.length - 1 ? 0 : 8),
                  child: Material(
                    color: active
                        ? HomeTheme.brandYellow
                        : HomeTheme.bg,
                    borderRadius: BorderRadius.circular(20),
                    child: InkWell(
                      onTap: () {
                        bCubit.changeCurrentIndex(index);
                        _scrollToIndex(index);
                      },
                      borderRadius: BorderRadius.circular(20),
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 14,
                          vertical: 8,
                        ),
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(20),
                          border: active
                              ? null
                              : Border.all(color: HomeTheme.headerBorder),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Text(
                              tabs[index],
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight:
                                    active ? FontWeight.w700 : FontWeight.w500,
                                color: active
                                    ? HomeTheme.textDark
                                    : HomeTheme.textMuted,
                              ),
                            ),
                            const SizedBox(width: 4),
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 6,
                                vertical: 2,
                              ),
                              decoration: BoxDecoration(
                                color: active
                                    ? HomeTheme.textDark.withValues(alpha: 0.08)
                                    : HomeTheme.headerBorder
                                        .withValues(alpha: 0.6),
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: Text(
                                '${count[index]}',
                                style: TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w700,
                                  color: active
                                      ? HomeTheme.textDark
                                      : HomeTheme.textMuted,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                );
              }),
            ),
          ),
        );
      },
    );
  }
}
