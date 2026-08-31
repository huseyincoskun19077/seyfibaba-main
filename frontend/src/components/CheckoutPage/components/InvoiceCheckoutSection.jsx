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
  };
}

export function validateInvoiceForm(invoice) {
  const type = invoice.invoice_type || INVOICE_INDIVIDUAL;
  if (type === INVOICE_CORPORATE) {
    if (!String(invoice.tax_number || "").trim()) {
      return "Kurumsal fatura için vergi numarası zorunludur.";
    }
    if (!String(invoice.tax_office || "").trim()) {
      return "Kurumsal fatura için vergi dairesi zorunludur.";
    }
    return null;
  }
  if (!String(invoice.tc_identity || "").trim()) {
    return "Bireysel fatura için TC Kimlik No zorunludur.";
  }
  if (!/^[1-9][0-9]{10}$/.test(String(invoice.tc_identity).replace(/\D/g, ""))) {
    return "Geçerli bir 11 haneli TC Kimlik No girin.";
  }
  return null;
}

export default function InvoiceCheckoutSection({ invoice, onChange, className = "" }) {
  const isCorporate = invoice.invoice_type === INVOICE_CORPORATE;

  return (
    <div className={`bg-white border border-qgray-border rounded-md p-5 ${className}`}>
      <h3 className="text-lg font-semibold text-qblack mb-1">Fatura Bilgileri</h3>
      <p className="text-sm text-qgraytwo mb-4">
        Satıcının e-fatura / e-arşiv kesmesi için zorunludur. Bilgileriniz yalnızca fatura amaçlı kullanılır.
      </p>

      <div className="flex flex-wrap gap-3 mb-5">
        <label className="inline-flex items-center gap-2 cursor-pointer">
          <input
            type="radio"
            name="invoice_type"
            checked={!isCorporate}
            onChange={() => onChange({ ...invoice, invoice_type: INVOICE_INDIVIDUAL })}
          />
          <span className="text-sm text-qblack">Bireysel (TC Kimlik)</span>
        </label>
        <label className="inline-flex items-center gap-2 cursor-pointer">
          <input
            type="radio"
            name="invoice_type"
            checked={isCorporate}
            onChange={() => onChange({ ...invoice, invoice_type: INVOICE_CORPORATE })}
          />
          <span className="text-sm text-qblack">Kurumsal (Vergi No)</span>
        </label>
      </div>

      {!isCorporate ? (
        <InputCom
          label="TC Kimlik No"
          placeholder="11 haneli TC Kimlik No"
          type="text"
          inputClasses="h-[50px]"
          value={invoice.tc_identity}
          inputHandler={(e) =>
            onChange({
              ...invoice,
              tc_identity: e.target.value.replace(/\D/g, "").slice(0, 11),
            })
          }
        />
      ) : (
        <div className="grid md:grid-cols-2 gap-4">
          <InputCom
            label="Vergi Numarası"
            placeholder="10 haneli vergi no"
            type="text"
            inputClasses="h-[50px]"
            value={invoice.tax_number}
            inputHandler={(e) =>
              onChange({
                ...invoice,
                tax_number: e.target.value.replace(/\D/g, "").slice(0, 10),
              })
            }
          />
          <InputCom
            label="Vergi Dairesi"
            placeholder="Örn: Kadıköy"
            type="text"
            inputClasses="h-[50px]"
            value={invoice.tax_office}
            inputHandler={(e) => onChange({ ...invoice, tax_office: e.target.value })}
          />
        </div>
      )}
    </div>
  );
}
