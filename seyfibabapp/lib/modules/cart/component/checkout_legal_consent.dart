import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';

import '../../../core/router_name.dart';
import '../../../utils/constants.dart';
import '../../../utils/language_string.dart';

class CheckoutConsentLink {
  final String slug;
  final String label;

  const CheckoutConsentLink({required this.slug, required this.label});
}

class CheckoutConsentGroup {
  final String key;
  final List<CheckoutConsentLink> links;
  final String suffix;

  const CheckoutConsentGroup({
    required this.key,
    required this.links,
    required this.suffix,
  });
}

class CheckoutLegalConsentCatalog {
  static const groups = [
    CheckoutConsentGroup(
      key: 'checkout-sales',
      links: [
        CheckoutConsentLink(
          slug: 'pre-information',
          label: 'Ön Bilgilendirme Formu',
        ),
        CheckoutConsentLink(
          slug: 'distance-sales',
          label: 'Mesafeli Satış Sözleşmesi',
        ),
      ],
      suffix: ' metinlerini okudum ve kabul ediyorum.',
    ),
    CheckoutConsentGroup(
      key: 'checkout-terms',
      links: [
        CheckoutConsentLink(
          slug: 'terms',
          label: 'Şartlar ve Koşullar',
        ),
        CheckoutConsentLink(
          slug: 'privacy-policy',
          label: 'Gizlilik Politikası',
        ),
      ],
      suffix: ' metinlerini okudum ve kabul ediyorum.',
    ),
  ];

  static bool allAccepted(Map<String, bool> values) {
    for (final group in groups) {
      if (values[group.key] != true) return false;
    }
    return true;
  }
}

class CheckoutLegalConsentPanel extends StatelessWidget {
  const CheckoutLegalConsentPanel({
    super.key,
    required this.values,
    required this.onChanged,
    this.padding = const EdgeInsets.fromLTRB(20, 8, 20, 8),
  });

  final Map<String, bool> values;
  final void Function(String key, bool value) onChanged;
  final EdgeInsets padding;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: padding,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            Language.checkoutLegalTitle,
            style: const TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w700,
              color: blackColor,
            ),
          ),
          const SizedBox(height: 8),
          ...CheckoutLegalConsentCatalog.groups.map(
            (group) => _ConsentTile(
              group: group,
              checked: values[group.key] == true,
              onToggle: () => onChanged(group.key, !(values[group.key] == true)),
              onOpenDocument: (slug, title) {
                Navigator.pushNamed(
                  context,
                  RouteNames.legalDocumentScreen,
                  arguments: {'slug': slug, 'title': title},
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class _ConsentTile extends StatelessWidget {
  const _ConsentTile({
    required this.group,
    required this.checked,
    required this.onToggle,
    required this.onOpenDocument,
  });

  final CheckoutConsentGroup group;
  final bool checked;
  final VoidCallback onToggle;
  final void Function(String slug, String title) onOpenDocument;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Material(
        color: checked ? deepGreenColor.withValues(alpha: 0.08) : whiteColor,
        borderRadius: BorderRadius.circular(12),
        child: InkWell(
          onTap: onToggle,
          borderRadius: BorderRadius.circular(12),
          child: Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: checked ? deepGreenColor : borderColor,
                width: checked ? 1.5 : 1,
              ),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _ConsentCheckbox(checked: checked),
                const SizedBox(width: 10),
                Expanded(child: _ConsentText(group: group, onOpenDocument: onOpenDocument)),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _ConsentCheckbox extends StatelessWidget {
  const _ConsentCheckbox({required this.checked});

  final bool checked;

  @override
  Widget build(BuildContext context) {
    return AnimatedContainer(
      duration: const Duration(milliseconds: 180),
      width: 22,
      height: 22,
      margin: const EdgeInsets.only(top: 1),
      decoration: BoxDecoration(
        color: checked ? deepGreenColor : whiteColor,
        borderRadius: BorderRadius.circular(6),
        border: Border.all(
          color: checked ? deepGreenColor : grayBorderColor,
          width: 1.5,
        ),
      ),
      child: checked
          ? const Icon(Icons.check_rounded, size: 16, color: whiteColor)
          : null,
    );
  }
}

class _ConsentText extends StatelessWidget {
  const _ConsentText({
    required this.group,
    required this.onOpenDocument,
  });

  final CheckoutConsentGroup group;
  final void Function(String slug, String title) onOpenDocument;

  @override
  Widget build(BuildContext context) {
    final spans = <InlineSpan>[
      const TextSpan(
        text: '* ',
        style: TextStyle(color: redColor, fontWeight: FontWeight.w700),
      ),
    ];

    for (var i = 0; i < group.links.length; i++) {
      final link = group.links[i];
      if (i > 0) {
        spans.add(TextSpan(
          text: i == group.links.length - 1 ? ' ve ' : ', ',
          style: const TextStyle(color: textGreyColor, fontSize: 13, height: 1.45),
        ));
      }
      spans.add(TextSpan(
        text: link.label,
        style: const TextStyle(
          color: deepGreenColor,
          decoration: TextDecoration.underline,
          fontWeight: FontWeight.w600,
          fontSize: 13,
          height: 1.45,
        ),
        recognizer: TapGestureRecognizer()
          ..onTap = () => onOpenDocument(link.slug, link.label),
      ));
    }

    spans.add(TextSpan(
      text: group.suffix,
      style: const TextStyle(
        color: textGreyColor,
        fontSize: 13,
        height: 1.45,
      ),
    ));

    return RichText(text: TextSpan(children: spans));
  }
}
