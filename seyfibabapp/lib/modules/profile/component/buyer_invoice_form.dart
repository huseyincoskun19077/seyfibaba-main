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
    this.companyName = '',
    this.isEInvoice = false,
    this.postalCode = '',
    required this.onChanged,
    this.showIntro = true,
  });

  final String invoiceType;
  final String tcIdentity;
  final String taxNumber;
  final String taxOffice;
  final String companyName;
  final bool isEInvoice;
  final String postalCode;
  final void Function({
    required String invoiceType,
    required String tcIdentity,
    required String taxNumber,
    required String taxOffice,
    required String companyName,
    required bool isEInvoice,
    required String postalCode,
  }) onChanged;
  final bool showIntro;

  @override
  State<BuyerInvoiceForm> createState() => _BuyerInvoiceFormState();
}

class _BuyerInvoiceFormState extends State<BuyerInvoiceForm> {
  static const _individual = 'individual';
  static const _corporate = 'corporate';

  bool get _isCorporate => widget.invoiceType == _corporate;

  void _emit({
    String? invoiceType,
    String? tcIdentity,
    String? taxNumber,
    String? taxOffice,
    String? companyName,
    bool? isEInvoice,
    String? postalCode,
  }) {
    widget.onChanged(
      invoiceType: invoiceType ?? widget.invoiceType,
      tcIdentity: tcIdentity ?? widget.tcIdentity,
      taxNumber: taxNumber ?? widget.taxNumber,
      taxOffice: taxOffice ?? widget.taxOffice,
      companyName: companyName ?? widget.companyName,
      isEInvoice: isEInvoice ?? widget.isEInvoice,
      postalCode: postalCode ?? widget.postalCode,
    );
  }

  String _digits(String value, int max) {
    final digits = value.replaceAll(RegExp(r'\D'), '');
    return digits.substring(0, digits.length.clamp(0, max));
  }

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
            text: 'Fatura Türü',
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
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  style: OutlinedButton.styleFrom(
                    foregroundColor: !_isCorporate ? HomeTheme.brandYellow : Colors.black87,
                    side: BorderSide(
                      color: !_isCorporate ? HomeTheme.brandYellow : borderColor,
                    ),
                    backgroundColor: !_isCorporate
                        ? HomeTheme.brandYellow.withOpacity(0.08)
                        : whiteColor,
                  ),
                  onPressed: () => _emit(
                    invoiceType: _individual,
                    taxNumber: '',
                    taxOffice: '',
                    companyName: '',
                    isEInvoice: false,
                  ),
                  child: const Text('Bireysel'),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: OutlinedButton(
                  style: OutlinedButton.styleFrom(
                    foregroundColor: _isCorporate ? HomeTheme.brandYellow : Colors.black87,
                    side: BorderSide(
                      color: _isCorporate ? HomeTheme.brandYellow : borderColor,
                    ),
                    backgroundColor: _isCorporate
                        ? HomeTheme.brandYellow.withOpacity(0.08)
                        : whiteColor,
                  ),
                  onPressed: () => _emit(
                    invoiceType: _corporate,
                    tcIdentity: '',
                  ),
                  child: const Text('Kurumsal'),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          if (!_isCorporate) ...[
            TextFormField(
              initialValue: widget.tcIdentity,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'TC Kimlik No *',
                hintText: '11 haneli TC Kimlik No',
              ),
              onChanged: (value) => _emit(
                invoiceType: _individual,
                tcIdentity: _digits(value, 11),
                taxNumber: '',
                taxOffice: '',
                companyName: '',
                isEInvoice: false,
              ),
            ),
            const SizedBox(height: 12),
            TextFormField(
              initialValue: widget.postalCode,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'Posta Kodu *',
                hintText: 'Örn: 34000',
              ),
              onChanged: (value) => _emit(
                invoiceType: _individual,
                postalCode: _digits(value, 5),
              ),
            ),
          ] else ...[
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFFEFF6FF),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: const Color(0xFFBFDBFE)),
              ),
              child: const Text(
                'Şahıs şirketi iseniz TCKN girmeniz önerilir.',
                style: TextStyle(fontSize: 13, color: Color(0xFF0C4A6E)),
              ),
            ),
            const SizedBox(height: 12),
            TextFormField(
              initialValue: widget.taxNumber,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'VKN/TCKN *',
                hintText: 'VKN/TCKN Giriniz',
              ),
              onChanged: (value) => _emit(
                invoiceType: _corporate,
                taxNumber: _digits(value, 11),
              ),
            ),
            const SizedBox(height: 12),
            TextFormField(
              initialValue: widget.taxOffice,
              decoration: const InputDecoration(
                labelText: 'Vergi Dairesi *',
                hintText: 'Vergi Dairesi Giriniz',
              ),
              onChanged: (value) => _emit(
                invoiceType: _corporate,
                taxOffice: value,
              ),
            ),
            const SizedBox(height: 12),
            TextFormField(
              initialValue: widget.companyName,
              decoration: const InputDecoration(
                labelText: 'Firma Adı *',
                hintText: 'Firma Adı Giriniz',
              ),
              onChanged: (value) => _emit(
                invoiceType: _corporate,
                companyName: value,
              ),
            ),
            const SizedBox(height: 8),
            CheckboxListTile(
              contentPadding: EdgeInsets.zero,
              value: widget.isEInvoice,
              activeColor: HomeTheme.brandYellow,
              title: const Text('E-fatura mükellefiyim'),
              controlAffinity: ListTileControlAffinity.leading,
              onChanged: (value) => _emit(
                invoiceType: _corporate,
                isEInvoice: value ?? false,
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
  String companyName = '',
  String postalCode = '',
}) {
  final isCorporate = invoiceType == 'corporate';
  if (isCorporate) {
    final tax = taxNumber.trim();
    if (tax.isEmpty) {
      return 'Kurumsal fatura için VKN/TCKN zorunludur.';
    }
    if (!(tax.length == 10 || tax.length == 11)) {
      return 'VKN 10, TCKN 11 haneli olmalıdır.';
    }
    if (tax.length == 11 && !RegExp(r'^[1-9][0-9]{10}$').hasMatch(tax)) {
      return 'Geçerli bir TCKN girin.';
    }
    if (taxOffice.trim().isEmpty) {
      return 'Kurumsal fatura için vergi dairesi zorunludur.';
    }
    if (companyName.trim().isEmpty) {
      return 'Kurumsal fatura için firma adı zorunludur.';
    }
    return null;
  }
  if (tcIdentity.trim().isEmpty) {
    return 'Bireysel fatura için TC Kimlik No zorunludur.';
  }
  if (!RegExp(r'^[1-9][0-9]{10}$').hasMatch(tcIdentity.trim())) {
    return 'Geçerli bir 11 haneli TC Kimlik No girin.';
  }
  if (!RegExp(r'^[0-9]{5}$').hasMatch(postalCode.trim())) {
    return 'Bireysel fatura için 5 haneli posta kodu zorunludur.';
  }
  return null;
}
