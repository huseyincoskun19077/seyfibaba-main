"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import apiRoutes from "@/appConfig/apiRoutes";
import auth from "@/utils/auth";
import { CHECKOUT_REQUIRED_CONSENTS } from "@/config/legalDocuments";

/**
 * Sipariş öncesi dinamik Ön Bilgilendirme + Mesafeli Satış onayları.
 */
export default function CheckoutDynamicLegalConsents({
  values = {},
  onChange,
  shippingAddressId,
  billingAddressId,
  shippingAddress,
  billingAddress,
  shippingCharge = 0,
  paymentMethod = "",
  cartItems = [],
  className = "",
}) {
  const [docs, setDocs] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [modal, setModal] = useState({ open: false, key: "", title: "", html: "" });

  const itemsPayload = useMemo(() => {
    if (!Array.isArray(cartItems) || cartItems.length === 0) return null;
    return cartItems.map((row) => {
      const product = row?.product || row;
      const variants = row?.variants || [];
      return {
        product_id: Number(row?.product_id || product?.id || 0),
        qty: Number(row?.qty || 1),
        unit_price: Number(
          row?.unit_price ??
            product?.offer_price ??
            product?.price ??
            0
        ),
        variants: (Array.isArray(variants) ? variants : []).map((v) => {
          const vi = v?.variant_item || v?.variantItem || v;
          return {
            variant_item_id: Number(v?.variant_item_id || vi?.id || 0),
            variant_name:
              vi?.product_variant_name || vi?.variant_name || "Seçenek",
            variant_value: vi?.name || "",
            variant_price: Number(vi?.price || v?.variant_price || 0),
          };
        }),
      };
    });
  }, [cartItems]);

  const fetchContracts = useCallback(async () => {
    setLoading(true);
    setError("");
    try {
      const token = auth()?.access_token;
      const body = {
        shipping_address_id: shippingAddressId || undefined,
        billing_address_id: billingAddressId || undefined,
        shipping_address: shippingAddress || undefined,
        billing_address: billingAddress || undefined,
        shipping_charge: Number(shippingCharge) || 0,
        payment_method: paymentMethod || undefined,
        items: token ? undefined : itemsPayload || undefined,
      };
      const res = await fetch(apiRoutes.checkoutLegalContracts, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
          ...(token ? { Authorization: `Bearer ${token}` } : {}),
        },
        body: JSON.stringify(body),
      });
      const data = await res.json();
      if (!res.ok) {
        throw new Error(data?.message || "Sözleşme yüklenemedi");
      }
      setDocs(data);
    } catch (e) {
      setDocs(null);
      setError(e?.message || "Sözleşme yüklenemedi");
    } finally {
      setLoading(false);
    }
  }, [
    shippingAddressId,
    billingAddressId,
    shippingAddress,
    billingAddress,
    shippingCharge,
    paymentMethod,
    itemsPayload,
  ]);

  useEffect(() => {
    fetchContracts();
  }, [fetchContracts]);

  const openDoc = (item) => {
    const slug = item.slug;
    const bundle =
      slug === "distance-sales"
        ? docs?.distance_sales
        : docs?.pre_information;
    setModal({
      open: true,
      key: item.key,
      title: bundle?.title || item.linkLabel,
      html: bundle?.html || "<p>Metin henüz hazır değil. Adres ve sepet bilgilerini kontrol edin.</p>",
    });
  };

  return (
    <div className={className} role="group" aria-label="Yasal onay kutuları">
      <p className="text-sm font-semibold text-[#1D1D1D] mb-2.5">
        Yasal Onaylar
      </p>
      {loading ? (
        <p className="text-xs text-slate-500 mb-2">Sözleşmeler hazırlanıyor…</p>
      ) : null}
      {error ? (
        <p className="text-xs text-red-600 mb-2">
          {error}{" "}
          <button type="button" className="underline" onClick={fetchContracts}>
            Tekrar dene
          </button>
        </p>
      ) : null}
      <div className="space-y-3">
        {CHECKOUT_REQUIRED_CONSENTS.map((item) => {
          const key = item.key;
          const checked = !!values[key];
          return (
            <div
              key={key}
              className={`rounded-xl border p-3.5 transition-colors ${
                checked
                  ? "border-green-500 bg-green-50/80"
                  : "border-gray-200 bg-white"
              }`}
            >
              <label className="flex items-start gap-3 cursor-pointer">
                <input
                  type="checkbox"
                  className="mt-1"
                  checked={checked}
                  onChange={(e) => onChange(key, e.target.checked)}
                />
                <span className="text-sm text-[#1D1D1D] leading-snug">
                  <button
                    type="button"
                    className="font-semibold text-[#04334a] underline underline-offset-2"
                    onClick={(e) => {
                      e.preventDefault();
                      e.stopPropagation();
                      openDoc(item);
                    }}
                  >
                    {item.linkLabel}
                  </button>
                  {item.label}
                  <span className="text-red-600">*</span>
                </span>
              </label>
            </div>
          );
        })}
      </div>

      {modal.open ? (
        <div
          className="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
          onClick={() => setModal((m) => ({ ...m, open: false }))}
        >
          <div
            className="bg-white rounded-2xl max-w-3xl w-full max-h-[85vh] flex flex-col shadow-xl"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="flex items-center justify-between gap-3 px-4 py-3 border-b">
              <h3 className="font-bold text-[#04334a] text-base">{modal.title}</h3>
              <button
                type="button"
                className="text-sm font-semibold px-3 py-1.5 rounded bg-slate-100"
                onClick={() => setModal((m) => ({ ...m, open: false }))}
              >
                Kapat
              </button>
            </div>
            <div
              className="p-4 overflow-y-auto prose prose-sm max-w-none"
              dangerouslySetInnerHTML={{ __html: modal.html }}
            />
            <div className="px-4 py-3 border-t">
              <button
                type="button"
                className="w-full h-11 rounded-lg bg-qyellow text-qblack font-semibold"
                onClick={() => {
                  if (modal.key) onChange(modal.key, true);
                  setModal((m) => ({ ...m, open: false }));
                }}
              >
                Okudum, onaylıyorum
              </button>
            </div>
          </div>
        </div>
      ) : null}
    </div>
  );
}
