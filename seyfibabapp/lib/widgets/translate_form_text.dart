import 'package:flutter/material.dart';

class TranslateWidget extends StatelessWidget {
  const TranslateWidget({
    super.key,
    required this.future,
    required this.hintText,
    required this.builder,
  });

  final Future<String> future;
  final String hintText;
  final Widget Function(BuildContext context, String translatedHint) builder;

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<String>(
      future: future,
      /* builder: (context, snapshot) {
        String? translatedHint = snapshot.hasData ? snapshot.data : hintText;
        return builder(
          context,
          translatedHint ?? hintText,
        ); // Call the builder with translated hint
      },*/

      builder: (context, snapshot) {
        final translatedText =
            snapshot.hasData && snapshot.data?.isNotEmpty == true
                ? snapshot.data
                : hintText;
        // final translatedHint = snapshot.hasData ? snapshot.data : hintText;
        return builder(context, translatedText ?? hintText);
      },
    );
  }
}
