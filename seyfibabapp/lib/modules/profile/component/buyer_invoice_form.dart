import 'package:flutter/material.dart';

import '../../../utils/constants.dart';
import '../../../utils/language_string.dart';
import '../../../widgets/custom_text.dart';
import '../../cart/component/address_card_component.dart';
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
    this.embedded = false,
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
  final bool embedded;

  @override
  State<BuyerInvoiceForm> createState() => _BuyerInvoiceFormState();
}

class _BuyerInvoiceFormState extends State<BuyerInvoiceForm> {
  static const _individual = 'individual';
  static const _corporate = 'corporate';

  late final TextEditingController _tcCtrl;
  late final TextEditingController _postalCtrl;
  late final TextEditingController _taxCtrl;
  late final TextEditingController _taxOfficeCtrl;
  late final TextEditingController _companyCtrl;

  bool get _isCorporate => widget.invoiceType == _corporate;

  @override
  void initState() {
    super.initState();
    _tcCtrl = TextEditingController(text: widget.tcIdentity);
    _postalCtrl = TextEditingController(text: widget.postalCode);
    _taxCtrl = TextEditingController(text: widget.taxNumber);
    _taxOfficeCtrl = TextEditingController(text: widget.taxOffice);
    _companyCtrl = TextEditingController(text: widget.companyName);
  }

  @override
  void didUpdateWidget(covariant BuyerInvoiceForm oldWidget) {
    super.didUpdateWidget(oldWidget);
    _sync(_tcCtrl, widget.tcIdentity);
    _sync(_postalCtrl, widget.postalCode);
    _sync(_taxCtrl, widget.taxNumber);
    _sync(_taxOfficeCtrl, widget.taxOffice);
    _sync(_companyCtrl, widget.companyName);
  }

  void _sync(TextEditingController ctrl, String value) {
    if (ctrl.text != value) {
      ctrl.text = value;
      ctrl.selection = TextSelection.collapsed(offset: value.length);
    }
  }

  @override
  void dispose() {
    _tcCtrl.dispose();
    _postalCtrl.dispose();
    _taxCtrl.dispose();
    _taxOfficeCtrl.dispose();
    _companyCtrl.dispose();
    super.dispose();
  }

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

  Widget _typeRadio({
    required String label,
    required bool selected,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 4, horizontal: 2),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              selected ? Icons.radio_button_checked : Icons.radio_button_off,
              size: 20,
              color: selected ? HomeTheme.brandYellow : Colors.grey.shade600,
            ),
            const SizedBox(width: 8),
            Flexible(
              child: Text(
                label,
                style: const TextStyle(fontSize: 14, color: blackColor),
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: widget.embedded
          ? EdgeInsets.zero
          : const EdgeInsets.fromLTRB(20, 8, 20, 8),
      padding: widget.embedded ? EdgeInsets.zero : const EdgeInsets.all(16),
      decoration: widget.embedded
          ? null
          : BoxDecoration(
              color: whiteColor,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: borderColor),
            ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const CustomText(
            text: 'Fatura Tipi',
            fontWeight: FontWeight.w600,
            fontSize: 14,
          ),
          if (widget.showIntro) ...[
            const SizedBox(height: 6),
            Text(
              'Satıcının e-fatura / e-arşiv kesmesi için zorunludur.',
              style: TextStyle(
                  fontSize: 13, color: Colors.grey.shade700, height: 1.35),
            ),
          ],
          const SizedBox(height: 10),
          Wrap(
            spacing: 20,
            runSpacing: 8,
            children: [
              _typeRadio(
                label: 'Bireysel',
                selected: !_isCorporate,
                onTap: () => _emit(
                  invoiceType: _individual,
                  taxNumber: '',
                  taxOffice: '',
                  companyName: '',
                  isEInvoice: false,
                ),
              ),
              _typeRadio(
                label: 'Kurumsal / Şahıs Firması',
                selected: _isCorporate,
                onTap: () => _emit(
                  invoiceType: _corporate,
                  tcIdentity: '',
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          if (!_isCorporate) ...[
            TextFormField(
              controller: _tcCtrl,
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
              controller: _postalCtrl,
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
              controller: _taxCtrl,
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
              controller: _taxOfficeCtrl,
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
              controller: _companyCtrl,
              decoration: const InputDecoration(
                labelText: 'Firma Adı *',
                hintText: 'Firma Adı Giriniz',
              ),
              onChanged: (value) => _emit(
                invoiceType: _corporate,
                companyName: value,
              ),
            ),
            const SizedBox(height: 4),
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

bool addressTypeIsHome(String type) =>
    !AddressCardComponent.isOfficeType(type);

String addressTypeValue(bool isHome) => isHome ? 'home' : 'office';

/// Ev / Ofis seçimi — web AddressesTab ile aynı.
class AddressPlaceTypeSelector extends StatelessWidget {
  const AddressPlaceTypeSelector({
    super.key,
    required this.isHome,
    required this.onChanged,
  });

  final bool isHome;
  final ValueChanged<bool> onChanged;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const CustomText(
          text: 'Adres Tipi',
          fontWeight: FontWeight.w600,
          fontSize: 14,
        ),
        const SizedBox(height: 8),
        Row(
          children: [
            Expanded(
              child: _chip(
                label: Language.addressTypeHome,
                selected: isHome,
                onTap: () => onChanged(true),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _chip(
                label: Language.addressTypeOffice,
                selected: !isHome,
                onTap: () => onChanged(false),
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _chip({
    required String label,
    required bool selected,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(
          color: selected
              ? HomeTheme.brandYellow.withOpacity(0.12)
              : whiteColor,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(
            color: selected ? HomeTheme.brandYellow : borderColor,
          ),
        ),
        alignment: Alignment.center,
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              selected ? Icons.check_circle : Icons.circle_outlined,
              size: 18,
              color: selected ? HomeTheme.brandYellow : Colors.grey.shade600,
            ),
            const SizedBox(width: 6),
            Text(
              label,
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w600,
                color: blackColor,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
