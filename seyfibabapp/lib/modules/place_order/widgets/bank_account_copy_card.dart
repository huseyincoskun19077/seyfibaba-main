import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../utils/constants.dart';
import '../../../utils/language_string.dart';
import '../../../utils/utils.dart';
import '../../../widgets/custom_text.dart';

class BankAccountFields {
  const BankAccountFields({
    required this.accountName,
    required this.iban,
    required this.ibanDisplay,
  });

  final String accountName;
  final String iban;
  final String ibanDisplay;

  static BankAccountFields parse(String raw) {
    final text = raw.trim();
    if (text.isEmpty) {
      return const BankAccountFields(
        accountName: '',
        iban: '',
        ibanDisplay: '',
      );
    }

    String accountName = '';
    String ibanDisplay = '';

    for (final line in text.split(RegExp(r'\r?\n'))) {
      final trimmed = line.trim();
      if (trimmed.isEmpty) continue;

      final ibanMatch = RegExp(
        r'(?:IBAN\s*[:：]?\s*)?(TR[\d\s]{10,})',
        caseSensitive: false,
      ).firstMatch(trimmed);
      if (ibanMatch != null && ibanDisplay.isEmpty) {
        ibanDisplay = ibanMatch.group(1)!.replaceAll(RegExp(r'\s+'), ' ').trim();
        continue;
      }

      final nameMatch = RegExp(
        r'^(?:Hesap\s*Sahibi|Hesap\s*Ad[ıi]|Ad\s*Soyad|Account\s*Holder|Account\s*Name)\s*[:：]\s*(.+)$',
        caseSensitive: false,
      ).firstMatch(trimmed);
      if (nameMatch != null && accountName.isEmpty) {
        accountName = nameMatch.group(1)!.trim();
      }
    }

    if (ibanDisplay.isEmpty) {
      final loose = RegExp(r'TR[\d\s]{10,}', caseSensitive: false).firstMatch(text);
      if (loose != null) {
        ibanDisplay = loose.group(0)!.replaceAll(RegExp(r'\s+'), ' ').trim();
      }
    }

    final iban = ibanDisplay.replaceAll(RegExp(r'\s+'), '').toUpperCase();
    return BankAccountFields(
      accountName: accountName,
      iban: iban,
      ibanDisplay: ibanDisplay.toUpperCase(),
    );
  }
}

/// Havale hesap bilgisi: IBAN ve hesap adı tek tıkla kopyalanır.
class BankAccountCopyCard extends StatelessWidget {
  const BankAccountCopyCard({
    super.key,
    required this.accountInfo,
    this.footer,
  });

  final String accountInfo;
  final String? footer;

  Future<void> _copy(BuildContext context, String value, String label) async {
    final text = value.trim();
    if (text.isEmpty) return;
    await Clipboard.setData(ClipboardData(text: text));
    if (context.mounted) {
      Utils.showSnackBar(context, '$label ${Language.copiedToClipboard}');
    }
  }

  @override
  Widget build(BuildContext context) {
    final fields = BankAccountFields.parse(accountInfo);

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: yellowColor.withOpacity(0.15),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: yellowColor.withOpacity(0.5)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const CustomText(
            text: 'Banka Hesap Bilgileri',
            fontWeight: FontWeight.w700,
          ),
          if (fields.accountName.isNotEmpty) ...[
            const SizedBox(height: 12),
            _CopyRow(
              label: 'Hesap Adı',
              value: fields.accountName,
              onCopy: () => _copy(context, fields.accountName, 'Hesap adı'),
            ),
          ],
          if (fields.iban.isNotEmpty) ...[
            const SizedBox(height: 10),
            _CopyRow(
              label: 'IBAN',
              value: fields.ibanDisplay,
              onCopy: () => _copy(context, fields.iban, 'IBAN'),
            ),
          ],
          const SizedBox(height: 12),
          CustomText(
            text: accountInfo,
            isTranslate: false,
            fontSize: 14,
            color: paragraphColor,
          ),
          if (footer != null && footer!.trim().isNotEmpty) ...[
            const SizedBox(height: 8),
            CustomText(
              text: footer!,
              fontSize: 12,
              color: textGreyColor,
            ),
          ],
        ],
      ),
    );
  }
}

class _CopyRow extends StatelessWidget {
  const _CopyRow({
    required this.label,
    required this.value,
    required this.onCopy,
  });

  final String label;
  final String value;
  final VoidCallback onCopy;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: whiteColor,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: borderColor),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                CustomText(
                  text: label,
                  fontSize: 12,
                  color: textGreyColor,
                  fontWeight: FontWeight.w600,
                ),
                const SizedBox(height: 4),
                CustomText(
                  text: value,
                  isTranslate: false,
                  fontSize: 14,
                  fontWeight: FontWeight.w700,
                ),
              ],
            ),
          ),
          IconButton(
            tooltip: 'Kopyala',
            onPressed: onCopy,
            icon: const Icon(Icons.copy_rounded, size: 20),
            color: deepGreenColor,
          ),
        ],
      ),
    );
  }
}
