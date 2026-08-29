import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_countdown_timer/index.dart';


import '../../../utils/utils.dart';
import '../../animated_splash_screen/controller/translate_cubit/translate_cubit.dart';
import '../../animated_splash_screen/controller/translate_cubit/translate_state_model.dart';
import 'custom_count_down.dart';

class ImplementedCountDown extends StatelessWidget {
  const ImplementedCountDown({super.key, required this.time});

  final CurrentRemainingTime time;

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<TranslateCubit, TranslateStateModel>(
      builder: (context, state) {
        return Row(
          mainAxisAlignment: MainAxisAlignment.spaceEvenly,
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            CustomCircularCountDownProgress(
              maxValue: time.days! + 10,
              title: Utils.formText(context, 'Days'),
              value: time.days!,
              key: UniqueKey(),
              color: const Color(0xffEB5757),
            ),
            Container(
              height: 1,
              width: 20,
              color: Colors.white.withValues(alpha: 0.7),
            ),
            CustomCircularCountDownProgress(
              maxValue: 24,
              title: Utils.formText(context, 'Hours'),
              value: time.hours!,
              key: UniqueKey(),
              color: const Color(0xff2F80ED),
            ),
            Container(
              height: 1,
              width: 20,
              color: Colors.white.withValues(alpha: 0.7),
            ),
            CustomCircularCountDownProgress(
              maxValue: 60,
              title: Utils.formText(context, 'Minutes'),
              value: time.min!,
              key: UniqueKey(),
              color: const Color(0xff219653),
            ),
            Container(
              height: 1,
              width: 20,
              color: Colors.white.withValues(alpha: 0.7),
            ),
            CustomCircularCountDownProgress(
              maxValue: 60,
              title: Utils.formText(context, 'Seconds'),
              value: time.sec!,
              key: UniqueKey(),
              color: const Color(0xffEB5757),
            ),
          ],
        );
      },
    );
  }
}
