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

function digitsOnly(value, max) {
  return String(value || "")
    .replace(/\D/g, "")
    .slice(0, max);
}

export function validateInvoiceForm(invoice) {
  const type = invoice.invoice_type || INVOICE_INDIVIDUAL;

  if (type === INVOICE_CORPORATE) {
    const tax = digitsOnly(invoice.tax_number, 11);
    if (!tax) {
      return "Kurumsal fatura için VKN/TCKN zorunludur.";
    }
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

function TypeToggle({ isCorporate, onChange }) {
  const base =
    "flex-1 min-w-[120px] px-4 py-2.5 text-sm font-medium border rounded-md transition-colors text-center";
  const active = "border-qyellow text-qyellow bg-qyellowlow/10";
  const inactive = "border-qgray-border text-qblack bg-white hover:border-qyellow/60";

  return (
    <div className="flex flex-wrap gap-2 mb-4">
      <button
        type="button"
        className={`${base} ${!isCorporate ? active : inactive}`}
        onClick={() => onChange(INVOICE_INDIVIDUAL)}
      >
        Bireysel
      </button>
      <button
        type="button"
        className={`${base} ${isCorporate ? active : inactive}`}
        onClick={() => onChange(INVOICE_CORPORATE)}
      >
        Kurumsal
      </button>
    </div>
  );
}

export default function InvoiceCheckoutSection({ invoice, onChange, className = "" }) {
  const isCorporate = invoice.invoice_type === INVOICE_CORPORATE;

  const setType = (invoice_type) => {
    onChange({
      ...invoice,
      invoice_type,
      ...(invoice_type === INVOICE_INDIVIDUAL
        ? { tax_number: "", tax_office: "", company_name: "", is_e_invoice: false }
        : { tc_identity: "", postal_code: invoice.postal_code || "" }),
    });
  };

  return (
    <div className={`bg-white border border-qgray-border rounded-md p-5 ${className}`}>
      <h3 className="text-lg font-semibold text-qblack mb-1">Fatura Türü</h3>
      <p className="text-sm text-qgraytwo mb-4">
        Satıcının e-fatura / e-arşiv kesmesi için zorunludur. Bilgileriniz yalnızca fatura amaçlı kullanılır.
      </p>

      <TypeToggle isCorporate={isCorporate} onChange={setType} />

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

          <div className="grid md:grid-cols-2 gap-4">
            <InputCom
              label="VKN/TCKN *"
              placeholder="VKN/TCKN Giriniz"
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
          </div>

          <div className="grid md:grid-cols-[1fr_auto] gap-4 items-end">
            <InputCom
              label="Firma Adı *"
              placeholder="Firma Adı Giriniz"
              type="text"
              name="company_name"
              inputClasses="h-[50px]"
              value={invoice.company_name}
              inputHandler={(e) =>
                onChange({ ...invoice, company_name: e.target.value })
              }
            />
            <label className="inline-flex items-center gap-2 cursor-pointer pb-3 select-none whitespace-nowrap">
              <input
                type="checkbox"
                className="h-5 w-5 accent-qyellow"
                checked={Boolean(invoice.is_e_invoice)}
                onChange={(e) =>
                  onChange({ ...invoice, is_e_invoice: e.target.checked })
                }
              />
              <span className="text-sm text-qblack font-medium">
                E-fatura mükellefiyim
              </span>
            </label>
          </div>
        </div>
      )}
    </div>
  );
}
