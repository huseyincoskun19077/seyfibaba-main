"use client";
import { useMemo, useState } from "react";
import { toast } from "react-toastify";
import auth from "@/utils/auth";
import { buildProductPath } from "@/utils/url";
import { useSendProductInquiryApiMutation } from "@/redux/features/contact/apiSlice";
import InputCom from "../Helpers/InputCom";

function normalizeWhatsappDigits(value) {
  let digits = String(value || "08503035073").replace(/\D+/g, "");
  if (!digits) return "908503035073";
  if (digits.startsWith("0") && digits.length === 11) {
    digits = `90${digits.slice(1)}`;
  } else if (digits.length === 10) {
    digits = `90${digits}`;
  }
  return digits;
}

function isFurnitureInquiryProduct(product) {
  if (product?.allow_furniture_inquiry) return true;
  const haystack = [
    product?.category?.name,
    product?.category?.slug,
    product?.sub_category?.name,
    product?.sub_category?.slug,
  ]
    .filter(Boolean)
    .join(" ")
    .toLocaleLowerCase("tr-TR");
  return haystack.includes("mobilya");
}

function sessionUser() {
  const session = auth();
  if (!session || typeof session !== "object") return {};
  const user = session.user && typeof session.user === "object" ? session.user : session;
  return {
    name: user.name || user.full_name || "",
    email: user.email || "",
    phone: user.phone || user.phone_number || "",
  };
}

export default function ProductFurnitureInquiry({ product }) {
  const [open, setOpen] = useState(false);
  const [formData, setFormData] = useState(() => ({
    ...sessionUser(),
    message: "",
  }));
  const [errors, setErrors] = useState(null);

  const [sendProductInquiryApi, { isLoading }] =
    useSendProductInquiryApiMutation();

  const enabled = isFurnitureInquiryProduct(product);
  const whatsappDigits = useMemo(
    () => normalizeWhatsappDigits(product?.furniture_inquiry_whatsapp),
    [product?.furniture_inquiry_whatsapp]
  );

  const productUrl = useMemo(() => {
    if (typeof window === "undefined") return "";
    return `${window.location.origin}${buildProductPath(product?.slug)}`;
  }, [product?.slug]);

  const whatsappUrl = useMemo(() => {
    const text = [
      "Merhaba, kuaför mobilyası hakkında bilgi almak istiyorum.",
      "",
      `Ürün: ${product?.name || ""}`,
      productUrl ? `Link: ${productUrl}` : "",
    ]
      .filter(Boolean)
      .join("\n");
    return `https://wa.me/${whatsappDigits}?text=${encodeURIComponent(text)}`;
  }, [product?.name, productUrl, whatsappDigits]);

  if (!enabled || !product?.id) return null;

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async () => {
    await sendProductInquiryApi({
      data: {
        name: formData.name,
        email: formData.email,
        phone: formData.phone,
        message: formData.message,
        product_id: product.id,
      },
      success: (data, statusCode) => {
        if (statusCode === 200 || statusCode === 201) {
          setFormData((prev) => ({ ...prev, message: "" }));
          setErrors(null);
          setOpen(false);
          toast.success(
            data?.notification ||
              "Bilgi talebiniz alındı. En kısa sürede sizinle iletişime geçeceğiz."
          );
        }
      },
      error: (error) => {
        setErrors(error?.data?.errors || null);
        toast.error(
          error?.data?.notification ||
            error?.data?.message ||
            "Talep gönderilemedi. Lütfen bilgilerinizi kontrol edin."
        );
      },
    });
  };

  return (
    <div data-aos="fade-up" className="mb-7 rounded-xl border border-[#d9ead3] bg-[#f6fff4] p-4">
      <p className="text-sm font-semibold text-qblack">
        Bu ürün hakkında bilgi alın
      </p>
      <p className="mt-1 text-[13px] leading-6 text-qgray">
        Kuaför mobilyalarında ölçü, renk ve teslimat için WhatsApp’tan yazabilir
        veya destek talebi oluşturabilirsiniz.
      </p>
      <div className="mt-3 flex flex-col gap-2 sm:flex-row">
        <a
          href={whatsappUrl}
          target="_blank"
          rel="noopener noreferrer"
          className="inline-flex h-[44px] flex-1 items-center justify-center rounded-md bg-[#25D366] px-4 text-sm font-semibold text-white"
        >
          WhatsApp ile yazın
        </a>
        <button
          type="button"
          onClick={() => {
            setFormData((prev) => ({ ...sessionUser(), message: prev.message }));
            setOpen(true);
          }}
          className="inline-flex h-[44px] flex-1 items-center justify-center rounded-md border border-qblack bg-white px-4 text-sm font-semibold text-qblack"
        >
          Destek talebi oluştur
        </button>
      </div>

      {open && (
        <div
          className="fixed inset-0 z-[80] flex items-center justify-center bg-black/40 px-4"
          onClick={() => setOpen(false)}
        >
          <div
            className="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl"
            onClick={(e) => e.stopPropagation()}
          >
            <h3 className="text-lg font-semibold text-qblack">
              Destek talebi
            </h3>
            <p className="mt-1 text-[13px] leading-6 text-qgray">
              {product?.name} için bilgi talebiniz Seyfibaba destek ekibine iletilir.
            </p>
            <div className="mt-4 space-y-3">
              <InputCom
                label="Ad Soyad*"
                name="name"
                placeholder="Adınız"
                value={formData.name}
                inputHandler={handleChange}
                error={!!errors?.name}
                inputClasses="h-[50px]"
              />
              <InputCom
                label="Telefon*"
                name="phone"
                placeholder="05xx xxx xx xx"
                value={formData.phone}
                inputHandler={handleChange}
                error={!!errors?.phone}
                inputClasses="h-[50px]"
              />
              <InputCom
                label="E-posta"
                name="email"
                placeholder="opsiyonel"
                value={formData.email}
                inputHandler={handleChange}
                error={!!errors?.email}
                inputClasses="h-[50px]"
              />
              <div>
                <label className="mb-2 inline-block text-[13px] font-normal capitalize text-qgray">
                  Mesaj
                </label>
                <textarea
                  name="message"
                  rows={4}
                  value={formData.message}
                  onChange={handleChange}
                  placeholder="Ölçü, renk veya teslimat hakkında sormak istediğiniz detay"
                  className="w-full rounded border border-qgray-border px-3 py-2 text-sm outline-none focus:ring-0"
                />
              </div>
            </div>
            <div className="mt-4 flex gap-2">
              <button
                type="button"
                onClick={() => setOpen(false)}
                className="h-[42px] flex-1 rounded-md border border-qgray-border text-sm font-semibold text-qblack"
              >
                Vazgeç
              </button>
              <button
                type="button"
                disabled={isLoading}
                onClick={handleSubmit}
                className="black-btn h-[42px] flex-1 text-sm font-semibold disabled:opacity-60"
              >
                {isLoading ? "Gönderiliyor..." : "Talebi gönder"}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
