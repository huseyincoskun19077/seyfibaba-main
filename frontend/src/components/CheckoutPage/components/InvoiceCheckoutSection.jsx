"use client";

import InputCom from "@/components/Helpers/InputCom";

export const INVOICE_INDIVIDUAL = "individual";
export const INVOICE_CORPORATE = "corporate";

export function defaultInvoiceState(profileInvoice = {}) {
  return {
    invoice_type: profileInvoice.invoice_type || INVOICE_INDIVIDUAL,
    tc_identity: profileInvoice.tc_identity || "",
    tax_number: profileInvoice.tax_number || "",
    tax_office: profileInvoice.tax_office || "",
    company_name: profileInvoice.company_name || "",
    is_e_invoice: Boolean(profileInvoice.is_e_invoice),
    postal_code: profileInvoice.postal_code || profileInvoice.zip_code || "",
  };
}

export function invoiceFromAddress(address) {
  if (!address) return defaultInvoiceState();
  return defaultInvoiceState({
    invoice_type: address.invoice_type,
    tc_identity: address.tc_identity,
    tax_number: address.tax_number,
    tax_office: address.tax_office,
    company_name: address.company_name,
    is_e_invoice: address.is_e_invoice,
    postal_code: address.postal_code || address.zip_code,
  });
}

function digitsOnly(value, max) {
  return String(value || "")
    .replace(/\D/g, "")
    .slice(0, max);
}

export function validateInvoiceForm(invoice) {
  const type = invoice.invoice_type || INVOICE_INDIVIDUAL;

  if (type === INVOICE_CORPORATE) {
    const tax = digitsOnly(invoice.tax_number, 11);
    if (!tax) return "Kurumsal fatura için VKN/TCKN zorunludur.";
    if (!(tax.length === 10 || tax.length === 11)) {
      return "VKN 10, TCKN 11 haneli olmalıdır.";
    }
    if (tax.length === 11 && !/^[1-9][0-9]{10}$/.test(tax)) {
      return "Geçerli bir TCKN girin.";
    }
    if (!String(invoice.tax_office || "").trim()) {
      return "Kurumsal fatura için vergi dairesi zorunludur.";
    }
    if (!String(invoice.company_name || "").trim()) {
      return "Kurumsal fatura için firma adı zorunludur.";
    }
    return null;
  }

  if (!String(invoice.tc_identity || "").trim()) {
    return "Bireysel fatura için TC Kimlik No zorunludur.";
  }
  if (!/^[1-9][0-9]{10}$/.test(digitsOnly(invoice.tc_identity, 11))) {
    return "Geçerli bir 11 haneli TC Kimlik No girin.";
  }
  const postal = digitsOnly(invoice.postal_code, 5);
  if (!postal || postal.length !== 5) {
    return "Bireysel fatura için 5 haneli posta kodu zorunludur.";
  }
  return null;
}

/**
 * Adres formunun içine gömülen fatura alanları (ayrı blok değil).
 */
export function AddressInvoiceFields({ invoice, onChange }) {
  const isCorporate = invoice.invoice_type === INVOICE_CORPORATE;

  const setType = (invoice_type) => {
    onChange({
      ...invoice,
      invoice_type,
      ...(invoice_type === INVOICE_INDIVIDUAL
        ? { tax_number: "", tax_office: "", company_name: "", is_e_invoice: false }
        : { tc_identity: "" }),
    });
  };

  return (
    <div className="mb-6">
      <p className="text-sm font-medium text-qblack mb-3">Fatura Tipi</p>
      <div className="flex flex-wrap gap-5 mb-4">
        <label className="inline-flex items-center gap-2 cursor-pointer select-none">
          <input
            type="radio"
            name="address_invoice_type"
            className="accent-qyellow"
            checked={!isCorporate}
            onChange={() => setType(INVOICE_INDIVIDUAL)}
          />
          <span className="text-sm text-qblack">Bireysel</span>
        </label>
        <label className="inline-flex items-center gap-2 cursor-pointer select-none">
          <input
            type="radio"
            name="address_invoice_type"
            className="accent-qyellow"
            checked={isCorporate}
            onChange={() => setType(INVOICE_CORPORATE)}
          />
          <span className="text-sm text-qblack">Kurumsal / Şahıs Firması</span>
        </label>
      </div>

      {!isCorporate ? (
        <div className="grid md:grid-cols-2 gap-4">
          <InputCom
            label="TC Kimlik No *"
            placeholder="11 haneli TC Kimlik No"
            type="text"
            name="tc_identity"
            inputClasses="h-[50px]"
            value={invoice.tc_identity}
            inputHandler={(e) =>
              onChange({
                ...invoice,
                tc_identity: digitsOnly(e.target.value, 11),
              })
            }
          />
          <InputCom
            label="Posta Kodu *"
            placeholder="Örn: 34000"
            type="text"
            name="postal_code"
            inputClasses="h-[50px]"
            value={invoice.postal_code}
            inputHandler={(e) =>
              onChange({
                ...invoice,
                postal_code: digitsOnly(e.target.value, 5),
              })
            }
          />
        </div>
      ) : (
        <div className="space-y-4">
          <div className="flex gap-2 items-start rounded-md border border-sky-200 bg-sky-50 px-3 py-2.5 text-sm text-sky-900">
            <span className="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-sky-500 text-[11px] font-bold text-white">
              i
            </span>
            <p>Şahıs şirketi iseniz TCKN girmeniz önerilir.</p>
          </div>
          <InputCom
            label="Firma / Şirket Adı *"
            placeholder="Firma Adı Giriniz"
            type="text"
            name="company_name"
            inputClasses="h-[50px]"
            value={invoice.company_name}
            inputHandler={(e) =>
              onChange({ ...invoice, company_name: e.target.value })
            }
          />
          <div className="grid md:grid-cols-2 gap-4">
            <InputCom
              label="Vergi Dairesi *"
              placeholder="Vergi Dairesi Giriniz"
              type="text"
              name="tax_office"
              inputClasses="h-[50px]"
              value={invoice.tax_office}
              inputHandler={(e) =>
                onChange({ ...invoice, tax_office: e.target.value })
              }
            />
            <InputCom
              label="VKN / TCKN *"
              placeholder="VKN veya TCKN Giriniz"
              type="text"
              name="tax_number"
              inputClasses="h-[50px]"
              value={invoice.tax_number}
              inputHandler={(e) =>
                onChange({
                  ...invoice,
                  tax_number: digitsOnly(e.target.value, 11),
                })
              }
            />
          </div>
          <label className="inline-flex items-center gap-2 cursor-pointer select-none">
            <input
              type="checkbox"
              className="h-4 w-4 accent-qyellow"
              checked={Boolean(invoice.is_e_invoice)}
              onChange={(e) =>
                onChange({ ...invoice, is_e_invoice: e.target.checked })
              }
            />
            <span className="text-sm text-qblack">E-fatura mükellefiyim</span>
          </label>
        </div>
      )}
    </div>
  );
}

/** @deprecated Checkout'ta ayrı blok kullanılmıyor; AddressInvoiceFields tercih edin. */
export default function InvoiceCheckoutSection({ invoice, onChange, className = "" }) {
  return (
    <div className={`bg-white border border-qgray-border rounded-md p-5 ${className}`}>
      <AddressInvoiceFields invoice={invoice} onChange={onChange} />
    </div>
  );
}
