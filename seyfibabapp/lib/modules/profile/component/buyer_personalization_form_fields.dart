import 'package:flutter/material.dart';

import '../../../utils/constants.dart';
import '../../home/widgets/home_theme.dart';
import '../model/buyer_personalization_constants.dart';

class BuyerPersonalizationFormFields extends StatelessWidget {
  const BuyerPersonalizationFormFields({
    super.key,
    required this.shopName,
    required this.businessType,
    required this.businessTypeOther,
    required this.businessStatus,
    required this.onShopNameChanged,
    required this.onBusinessTypeChanged,
    required this.onBusinessTypeOtherChanged,
    required this.onBusinessStatusChanged,
    this.showIntro = true,
  });

  final String shopName;
  final String businessType;
  final String businessTypeOther;
  final String businessStatus;
  final ValueChanged<String> onShopNameChanged;
  final ValueChanged<String> onBusinessTypeChanged;
  final ValueChanged<String> onBusinessTypeOtherChanged;
  final ValueChanged<String> onBusinessStatusChanged;
  final bool showIntro;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (showIntro) ...[
          Text(
            BuyerPersonalizationCopy.introTitle,
            style: const TextStyle(
              fontSize: 22,
              fontWeight: FontWeight.w800,
              color: blackColor,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            BuyerPersonalizationCopy.introBody,
            style: TextStyle(
              fontSize: 14,
              height: 1.45,
              color: Colors.grey.shade700,
            ),
          ),
          const SizedBox(height: 10),
          _infoBox(BuyerPersonalizationCopy.whyWeAsk),
          const SizedBox(height: 22),
        ],
        _sectionTitle(BuyerPersonalizationCopy.shopNameTitle),
        const SizedBox(height: 6),
        Text(
          BuyerPersonalizationCopy.shopNameHelper,
          style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
        ),
        const SizedBox(height: 10),
        TextFormField(
          initialValue: shopName,
          onChanged: onShopNameChanged,
          decoration: InputDecoration(
            hintText: BuyerPersonalizationCopy.shopNameHint,
            filled: true,
            fillColor: Colors.white,
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(color: Colors.grey.shade300),
            ),
          ),
        ),
        const SizedBox(height: 22),
        _sectionTitle(BuyerPersonalizationCopy.businessTypeTitle),
        const SizedBox(height: 6),
        Text(
          BuyerPersonalizationCopy.businessTypeHelper,
          style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
        ),
        const SizedBox(height: 10),
        _choiceWrap(
          options: BuyerBusinessType.options,
          selected: businessType,
          onSelected: onBusinessTypeChanged,
        ),
        if (businessType == BuyerBusinessType.other) ...[
          const SizedBox(height: 10),
          TextFormField(
            initialValue: businessTypeOther,
            onChanged: onBusinessTypeOtherChanged,
            decoration: InputDecoration(
              hintText: BuyerPersonalizationCopy.otherHint,
              filled: true,
              fillColor: Colors.white,
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
              ),
            ),
          ),
        ],
        const SizedBox(height: 22),
        _sectionTitle(BuyerPersonalizationCopy.businessStatusTitle),
        const SizedBox(height: 6),
        Text(
          BuyerPersonalizationCopy.businessStatusHelper,
          style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
        ),
        const SizedBox(height: 10),
        _choiceWrap(
          options: BuyerBusinessStatus.options,
          selected: businessStatus,
          onSelected: onBusinessStatusChanged,
        ),
      ],
    );
  }

  Widget _sectionTitle(String text) {
    return Text(
      text,
      style: const TextStyle(
        fontSize: 16,
        fontWeight: FontWeight.w700,
        color: blackColor,
      ),
    );
  }

  Widget _infoBox(String text) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: HomeTheme.brandYellow.withValues(alpha: 0.18),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: HomeTheme.brandYellow.withValues(alpha: 0.45)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.info_outline, size: 18, color: Colors.grey.shade800),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              text,
              style: TextStyle(fontSize: 13, height: 1.4, color: Colors.grey.shade800),
            ),
          ),
        ],
      ),
    );
  }

  Widget _choiceWrap({
    required Map<String, String> options,
    required String selected,
    required ValueChanged<String> onSelected,
  }) {
    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: options.entries.map((entry) {
        final isSelected = selected == entry.key;
        return ChoiceChip(
          label: Text(entry.value),
          selected: isSelected,
          onSelected: (_) => onSelected(entry.key),
          selectedColor: HomeTheme.brandYellow,
          labelStyle: TextStyle(
            fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
            color: blackColor,
          ),
          backgroundColor: Colors.white,
          side: BorderSide(
            color: isSelected ? HomeTheme.brandYellow : Colors.grey.shade300,
          ),
        );
      }).toList(),
    );
  }
}
