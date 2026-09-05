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
    required this.tcController,
    required this.postalController,
    required this.taxNumberController,
    required this.taxOfficeController,
    required this.companyController,
    required this.isEInvoice,
    required this.onInvoiceTypeChanged,
    required this.onEInvoiceChanged,
    this.showIntro = true,
    this.embedded = false,
  });

  final String invoiceType;
  final TextEditingController tcController;
  final TextEditingController postalController;
  final TextEditingController taxNumberController;
  final TextEditingController taxOfficeController;
  final TextEditingController companyController;
  final bool isEInvoice;
  final ValueChanged<String> onInvoiceTypeChanged;
  final ValueChanged<bool> onEInvoiceChanged;
  final bool showIntro;
  final bool embedded;

  @override
  State<BuyerInvoiceForm> createState() => BuyerInvoiceFormState();
}

class BuyerInvoiceFormState extends State<BuyerInvoiceForm> {
  static const individual = 'individual';
  static const corporate = 'corporate';

  String? _tcError;
  String? _postalError;
  String? _taxNumberError;
  String? _taxOfficeError;
  String? _companyError;

  bool get _isCorporate => widget.invoiceType == corporate;

  static String digitsOnly(String value, int max) {
    final digits = value.replaceAll(RegExp(r'\D'), '');
    return digits.substring(0, digits.length.clamp(0, max));
  }

  /// Backend BuyerInvoiceService::isValidTcKimlik ile aynı algoritma.
  static bool isValidTcKimlik(String tc) {
    if (!RegExp(r'^[1-9][0-9]{10}$').hasMatch(tc)) return false;
    final d = tc.split('').map(int.parse).toList();
    final oddSum = d[0] + d[2] + d[4] + d[6] + d[8];
    final evenSum = d[1] + d[3] + d[5] + d[7];
    var digit10 = ((oddSum * 7) - evenSum) % 10;
    if (digit10 < 0) digit10 += 10;
    if (d[9] != digit10) return false;
    final digit11 = d.sublist(0, 10).fold<int>(0, (a, b) => a + b) % 10;
    return d[10] == digit11;
  }

  /// Submit öncesi güncel fatura alanları (controller kaynağı).
  ({
    String invoiceType,
    String tcIdentity,
    String taxNumber,
    String taxOffice,
    String companyName,
    bool isEInvoice,
    String postalCode,
  }) readValues() {
    if (_isCorporate) {
      return (
        invoiceType: corporate,
        tcIdentity: '',
        taxNumber: digitsOnly(widget.taxNumberController.text, 11),
        taxOffice: widget.taxOfficeController.text.trim(),
        companyName: widget.companyController.text.trim(),
        isEInvoice: widget.isEInvoice,
        postalCode: digitsOnly(widget.postalController.text, 5),
      );
    }
    return (
      invoiceType: individual,
      tcIdentity: digitsOnly(widget.tcController.text, 11),
      taxNumber: '',
      taxOffice: '',
      companyName: '',
      isEInvoice: false,
      postalCode: digitsOnly(widget.postalController.text, 5),
    );
  }

  /// Alanları doğrula; hatalıysa kırmızı hata göster. Geçerliyse true.
  bool validateAndHighlight() {
    final values = readValues();
    final errors = buyerInvoiceFieldErrors(
      invoiceType: values.invoiceType,
      tcIdentity: values.tcIdentity,
      taxNumber: values.taxNumber,
      taxOffice: values.taxOffice,
      companyName: values.companyName,
      postalCode: values.postalCode,
    );
    setState(() {
      _tcError = errors['tc_identity'];
      _postalError = errors['postal_code'];
      _taxNumberError = errors['tax_number'];
      _taxOfficeError = errors['tax_office'];
      _companyError = errors['company_name'];
    });
    return errors.isEmpty;
  }

  void applyServerErrors(Map<String, String> errors) {
    setState(() {
      _tcError = errors['tc_identity'];
      _postalError = errors['postal_code'];
      _taxNumberError = errors['tax_number'];
      _taxOfficeError = errors['tax_office'];
      _companyError = errors['company_name'];
    });
  }

  void _clearFieldError(String key) {
    setState(() {
      if (key == 'tc') _tcError = null;
      if (key == 'postal') _postalError = null;
      if (key == 'tax') _taxNumberError = null;
      if (key == 'office') _taxOfficeError = null;
      if (key == 'company') _companyError = null;
    });
  }

  InputDecoration _decoration({
    required String label,
    required String hint,
    String? errorText,
  }) {
    return InputDecoration(
      labelText: label,
      hintText: hint,
      errorText: errorText,
      errorMaxLines: 2,
      enabledBorder: errorText != null
          ? OutlineInputBorder(
              borderSide: const BorderSide(color: Colors.red, width: 1.4),
              borderRadius: BorderRadius.circular(5),
            )
          : null,
      focusedBorder: errorText != null
          ? OutlineInputBorder(
              borderSide: const BorderSide(color: Colors.red, width: 1.6),
              borderRadius: BorderRadius.circular(5),
            )
          : null,
      errorBorder: OutlineInputBorder(
        borderSide: const BorderSide(color: Colors.red, width: 1.4),
        borderRadius: BorderRadius.circular(5),
      ),
      focusedErrorBorder: OutlineInputBorder(
        borderSide: const BorderSide(color: Colors.red, width: 1.6),
        borderRadius: BorderRadius.circular(5),
      ),
    );
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
                onTap: () {
                  setState(() {
                    _tcError = null;
                    _postalError = null;
                    _taxNumberError = null;
                    _taxOfficeError = null;
                    _companyError = null;
                  });
                  widget.onInvoiceTypeChanged(individual);
                },
              ),
              _typeRadio(
                label: 'Kurumsal / Şahıs Firması',
                selected: _isCorporate,
                onTap: () {
                  setState(() {
                    _tcError = null;
                    _postalError = null;
                    _taxNumberError = null;
                    _taxOfficeError = null;
                    _companyError = null;
                  });
                  widget.onInvoiceTypeChanged(corporate);
                },
              ),
            ],
          ),
          const SizedBox(height: 12),
          if (!_isCorporate) ...[
            TextFormField(
              controller: widget.tcController,
              keyboardType: TextInputType.number,
              textInputAction: TextInputAction.next,
              decoration: _decoration(
                label: 'TC Kimlik No *',
                hint: '11 haneli TC Kimlik No',
                errorText: _tcError,
              ),
              onChanged: (value) {
                _clearFieldError('tc');
                final digits = digitsOnly(value, 11);
                if (digits != value) {
                  widget.tcController.value = TextEditingValue(
                    text: digits,
                    selection: TextSelection.collapsed(offset: digits.length),
                  );
                }
              },
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: widget.postalController,
              keyboardType: TextInputType.number,
              decoration: _decoration(
                label: 'Posta Kodu *',
                hint: 'Örn: 34000',
                errorText: _postalError,
              ),
              onChanged: (value) {
                _clearFieldError('postal');
                final digits = digitsOnly(value, 5);
                if (digits != value) {
                  widget.postalController.value = TextEditingValue(
                    text: digits,
                    selection: TextSelection.collapsed(offset: digits.length),
                  );
                }
              },
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
              controller: widget.taxNumberController,
              keyboardType: TextInputType.number,
              decoration: _decoration(
                label: 'VKN/TCKN *',
                hint: 'VKN/TCKN Giriniz',
                errorText: _taxNumberError,
              ),
              onChanged: (value) {
                _clearFieldError('tax');
                final digits = digitsOnly(value, 11);
                if (digits != value) {
                  widget.taxNumberController.value = TextEditingValue(
                    text: digits,
                    selection: TextSelection.collapsed(offset: digits.length),
                  );
                }
              },
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: widget.taxOfficeController,
              decoration: _decoration(
                label: 'Vergi Dairesi *',
                hint: 'Vergi Dairesi Giriniz',
                errorText: _taxOfficeError,
              ),
              onChanged: (_) => _clearFieldError('office'),
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: widget.companyController,
              decoration: _decoration(
                label: 'Firma Adı *',
                hint: 'Firma Adı Giriniz',
                errorText: _companyError,
              ),
              onChanged: (_) => _clearFieldError('company'),
            ),
            const SizedBox(height: 4),
            CheckboxListTile(
              contentPadding: EdgeInsets.zero,
              value: widget.isEInvoice,
              activeColor: HomeTheme.brandYellow,
              title: const Text('E-fatura mükellefiyim'),
              controlAffinity: ListTileControlAffinity.leading,
              onChanged: (value) =>
                  widget.onEInvoiceChanged(value ?? false),
            ),
          ],
        ],
      ),
    );
  }
}

/// Alan bazlı hatalar (key → mesaj). Boş map = geçerli.
Map<String, String> buyerInvoiceFieldErrors({
  required String invoiceType,
  required String tcIdentity,
  required String taxNumber,
  required String taxOffice,
  String companyName = '',
  String postalCode = '',
}) {
  final errors = <String, String>{};
  final isCorporate = invoiceType == 'corporate';
  if (isCorporate) {
    final tax = BuyerInvoiceFormState.digitsOnly(taxNumber, 11);
    if (tax.isEmpty) {
      errors['tax_number'] = 'VKN/TCKN zorunludur.';
    } else if (!(tax.length == 10 || tax.length == 11)) {
      errors['tax_number'] = 'VKN 10, TCKN 11 haneli olmalıdır.';
    } else if (tax.length == 11 &&
        !BuyerInvoiceFormState.isValidTcKimlik(tax)) {
      errors['tax_number'] = 'Girdiğiniz TCKN geçersiz.';
    }
    if (taxOffice.trim().isEmpty) {
      errors['tax_office'] = 'Vergi dairesi zorunludur.';
    }
    if (companyName.trim().isEmpty) {
      errors['company_name'] = 'Firma adı zorunludur.';
    }
    return errors;
  }

  final tc = BuyerInvoiceFormState.digitsOnly(tcIdentity, 11);
  if (tc.isEmpty) {
    errors['tc_identity'] = 'TC Kimlik No zorunludur.';
  } else if (!RegExp(r'^[1-9][0-9]{10}$').hasMatch(tc)) {
    errors['tc_identity'] = 'Geçerli bir 11 haneli TC Kimlik No girin.';
  } else if (!BuyerInvoiceFormState.isValidTcKimlik(tc)) {
    errors['tc_identity'] = 'Girdiğiniz TC Kimlik No geçersiz.';
  }

  final postal = BuyerInvoiceFormState.digitsOnly(postalCode, 5);
  if (postal.isEmpty) {
    errors['postal_code'] = 'Posta kodu zorunludur.';
  } else if (!RegExp(r'^[0-9]{5}$').hasMatch(postal)) {
    errors['postal_code'] = '5 haneli geçerli bir posta kodu girin.';
  }
  return errors;
}

String? validateBuyerInvoice({
  required String invoiceType,
  required String tcIdentity,
  required String taxNumber,
  required String taxOffice,
  String companyName = '',
  String postalCode = '',
}) {
  final errors = buyerInvoiceFieldErrors(
    invoiceType: invoiceType,
    tcIdentity: tcIdentity,
    taxNumber: taxNumber,
    taxOffice: taxOffice,
    companyName: companyName,
    postalCode: postalCode,
  );
  if (errors.isEmpty) return null;
  // Önce TC / vergi no hatası, sonra diğerleri
  return errors['tc_identity'] ??
      errors['tax_number'] ??
      errors['postal_code'] ??
      errors['tax_office'] ??
      errors['company_name'] ??
      errors.values.first;
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
