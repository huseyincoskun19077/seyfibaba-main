import 'package:flutter/material.dart';

import 'seyfibaba_loading.dart';

class LoadingWidget extends StatelessWidget {
  const LoadingWidget({super.key, this.color});

  final Color? color;

  @override
  Widget build(BuildContext context) {
    return const Center(
      child: SeyfibabaLoadingCard(compact: true),
    );
  }
}
