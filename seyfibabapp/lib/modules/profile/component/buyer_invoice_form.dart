import 'package:flutter/material.dart';

import '../../../utils/constants.dart';
import '../../../widgets/custom_text.dart';
import '../../home/widgets/home_theme.dart';

class BuyerInvoiceForm extends StatefulWidget {
  const BuyerInvoiceForm({
    super.key,
    required this.invoiceType,
    required this.tcIdentity,
    required this.taxNumber,
    required this.taxOffice,
    required this.onChanged,
    this.showIntro = true,
  });

  final String invoiceType;
  final String tcIdentity;
  final String taxNumber;
  final String taxOffice;
  final void Function({
    required String invoiceType,
    required String tcIdentity,
    required String taxNumber,
    required String taxOffice,
  }) onChanged;
  final bool showIntro;

  @override
  State<BuyerInvoiceForm> createState() => _BuyerInvoiceFormState();
}

class _BuyerInvoiceFormState extends State<BuyerInvoiceForm> {
  static const _individual = 'individual';
  static const _corporate = 'corporate';

  bool get _isCorporate => widget.invoiceType == _corporate;

  String? validate() => validateBuyerInvoice(
        invoiceType: widget.invoiceType,
        tcIdentity: widget.tcIdentity,
        taxNumber: widget.taxNumber,
        taxOffice: widget.taxOffice,
      );

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.fromLTRB(20, 8, 20, 8),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: whiteColor,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: borderColor),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const CustomText(
            text: 'Fatura Bilgileri',
            fontWeight: FontWeight.w700,
            fontSize: 16,
          ),
          if (widget.showIntro) ...[
            const SizedBox(height: 6),
            Text(
              'Satıcının e-fatura / e-arşiv kesmesi için zorunludur.',
              style: TextStyle(fontSize: 13, color: Colors.grey.shade700, height: 1.35),
            ),
          ],
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              ChoiceChip(
                label: const Text('Bireysel (TC)'),
                selected: !_isCorporate,
                onSelected: (_) => widget.onChanged(
                  invoiceType: _individual,
                  tcIdentity: widget.tcIdentity,
                  taxNumber: '',
                  taxOffice: '',
                ),
                selectedColor: HomeTheme.brandYellow,
              ),
              ChoiceChip(
                label: const Text('Kurumsal (Vergi)'),
                selected: _isCorporate,
                onSelected: (_) => widget.onChanged(
                  invoiceType: _corporate,
                  tcIdentity: '',
                  taxNumber: widget.taxNumber,
                  taxOffice: widget.taxOffice,
                ),
                selectedColor: HomeTheme.brandYellow,
              ),
            ],
          ),
          const SizedBox(height: 12),
          if (!_isCorporate)
            TextFormField(
              initialValue: widget.tcIdentity,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'TC Kimlik No',
                hintText: '11 haneli TC Kimlik No',
              ),
              onChanged: (value) => widget.onChanged(
                invoiceType: _individual,
                tcIdentity: value.replaceAll(RegExp(r'\D'), '').substring(
                      0,
                      value.replaceAll(RegExp(r'\D'), '').length.clamp(0, 11),
                    ),
                taxNumber: '',
                taxOffice: '',
              ),
            )
          else ...[
            TextFormField(
              initialValue: widget.taxNumber,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'Vergi Numarası',
                hintText: '10 haneli vergi no',
              ),
              onChanged: (value) => widget.onChanged(
                invoiceType: _corporate,
                tcIdentity: '',
                taxNumber: value.replaceAll(RegExp(r'\D'), '').substring(
                      0,
                      value.replaceAll(RegExp(r'\D'), '').length.clamp(0, 10),
                    ),
                taxOffice: widget.taxOffice,
              ),
            ),
            const SizedBox(height: 12),
            TextFormField(
              initialValue: widget.taxOffice,
              decoration: const InputDecoration(
                labelText: 'Vergi Dairesi',
                hintText: 'Örn: Kadıköy',
              ),
              onChanged: (value) => widget.onChanged(
                invoiceType: _corporate,
                tcIdentity: '',
                taxNumber: widget.taxNumber,
                taxOffice: value,
              ),
            ),
          ],
        ],
      ),
    );
  }
}

String? validateBuyerInvoice({
  required String invoiceType,
  required String tcIdentity,
  required String taxNumber,
  required String taxOffice,
}) {
  final isCorporate = invoiceType == 'corporate';
  if (isCorporate) {
    if (taxNumber.trim().isEmpty) {
      return 'Kurumsal fatura için vergi numarası zorunludur.';
    }
    if (taxOffice.trim().isEmpty) {
      return 'Kurumsal fatura için vergi dairesi zorunludur.';
    }
    return null;
  }
  if (tcIdentity.trim().isEmpty) {
    return 'Bireysel fatura için TC Kimlik No zorunludur.';
  }
  if (!RegExp(r'^[1-9][0-9]{10}$').hasMatch(tcIdentity.trim())) {
    return 'Geçerli bir 11 haneli TC Kimlik No girin.';
  }
  return null;
}
