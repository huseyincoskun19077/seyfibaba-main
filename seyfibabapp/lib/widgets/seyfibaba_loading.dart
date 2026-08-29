import 'dart:async';
import 'dart:math';

import 'package:flutter/material.dart';

import '../utils/constants.dart';
import '../utils/language_string.dart';

/// Seyfibaba’ya özgü yükleme mesajları (Getir tarzı marka dokunuşu).
class SeyfibabaLoadingPhrases {
  SeyfibabaLoadingPhrases._();

  static final _random = Random();

  static List<String> get all => [
        Language.seyfibabaLoading1,
        Language.seyfibabaLoading2,
        Language.seyfibabaLoading3,
        Language.seyfibabaLoading4,
        Language.seyfibabaLoading5,
        Language.seyfibabaLoading6,
        Language.seyfibabaLoading7,
      ];

  static String pick() {
    final phrases = all.where((e) => e.trim().isNotEmpty).toList();
    if (phrases.isEmpty) return Language.pleaseWaitAMoment;
    return phrases[_random.nextInt(phrases.length)];
  }
}

/// Modern, markaya özel yükleme kutusu — diyalog ve tam ekran için.
class SeyfibabaLoadingCard extends StatefulWidget {
  const SeyfibabaLoadingCard({
    super.key,
    this.compact = false,
  });

  final bool compact;

  @override
  State<SeyfibabaLoadingCard> createState() => _SeyfibabaLoadingCardState();
}

class _SeyfibabaLoadingCardState extends State<SeyfibabaLoadingCard> {
  late String _phrase;
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _phrase = SeyfibabaLoadingPhrases.pick();
    _timer = Timer.periodic(const Duration(milliseconds: 2200), (_) {
      if (!mounted) return;
      setState(() => _phrase = SeyfibabaLoadingPhrases.pick());
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final pad = widget.compact ? 20.0 : 28.0;

    return Container(
      constraints: BoxConstraints(
        minWidth: widget.compact ? 220 : 260,
        maxWidth: 320,
      ),
      padding: EdgeInsets.symmetric(horizontal: pad, vertical: pad - 4),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.08),
            blurRadius: 24,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 54,
            height: 54,
            decoration: BoxDecoration(
              color: yellowColor.withValues(alpha: 0.18),
              shape: BoxShape.circle,
            ),
            child: const Padding(
              padding: EdgeInsets.all(12),
              child: CircularProgressIndicator(
                strokeWidth: 2.8,
                color: yellowColor,
              ),
            ),
          ),
          const SizedBox(height: 16),
          Text(
            'SEYFIBABA',
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w800,
              letterSpacing: 1.6,
              color: yellowColor.withValues(alpha: 0.95),
            ),
          ),
          const SizedBox(height: 8),
          AnimatedSwitcher(
            duration: const Duration(milliseconds: 350),
            transitionBuilder: (child, anim) => FadeTransition(
              opacity: anim,
              child: SlideTransition(
                position: Tween<Offset>(
                  begin: const Offset(0, 0.12),
                  end: Offset.zero,
                ).animate(anim),
                child: child,
              ),
            ),
            child: Text(
              _phrase,
              key: ValueKey(_phrase),
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontSize: 15,
                fontWeight: FontWeight.w600,
                height: 1.35,
                color: Color(0xFF222222),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
