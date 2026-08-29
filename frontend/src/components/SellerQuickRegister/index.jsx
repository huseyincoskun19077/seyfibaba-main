"use client";

import { useEffect, useMemo, useState } from "react";
import Image from "next/image";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { toast } from "react-toastify";
import { useSelector } from "react-redux";
import InputCom from "../Helpers/InputCom";
import LoginLayout from "../Auth/Login/LoginLayout";
import LoaderStyleOne from "../Helpers/Loaders/LoaderStyleOne";
import {
  useGetPublicSellerRegisterStatesQuery,
  usePublicSellerRegisterMutation,
} from "@/redux/features/sellerRegister/apiSlice";
import { useLazyGetCityListApiQuery } from "@/redux/features/locations/apiSlice";
import { dedupeTurkishLocations } from "@/utils/dedupeTurkishLocations";
import LegalConsentCheckboxes, { allRequiredChecked } from "@/components/Legal/LegalConsentCheckboxes";
import {
  SELLER_REGISTER_OPTIONAL_CONSENTS,
  SELLER_REGISTER_REQUIRED_CONSENTS,
} from "@/config/legalDocuments";
import { recordLegalConsents } from "@/api/recordLegalConsents";

const IMAGE_FALLBACK = "/assets/images/server-error.png";

const LoginShape = () => (
  <svg width="172" height="29" viewBox="0 0 172 29" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M1 5.08742C17.6667 19.0972 30.5 31.1305 62.5 27.2693C110.617 21.4634 150 -10.09 171 5.08727" stroke="#FCBF49" />
  </svg>
);

const selectClassName =
  "h-[50px] w-full rounded-md border border-qgray-border bg-white px-4 text-sm text-qblack focus:outline-none focus:ring-0";

function PhoneInput({ value, onChange }) {
  const displayValue = String(value || "").replace(/^\+90/, "");

  return (
    <div className="input-com w-full h-full">
      <label className="input-label capitalize block mb-2 text-qgray text-[13px] font-normal">
        Telefon Numarası*
      </label>
      <div className="h-[50px] rounded-md bg-white border border-qgray-border flex items-center overflow-hidden">
        <div className="h-full px-3 flex items-center gap-2 border-r border-qgray-border bg-[#f7f7f7]">
          <Image width={18} height={12} src="/assets/images/countries/TR.svg" alt="TR" />
          <span className="text-qblack text-sm font-medium">+90</span>
        </div>
        <input
          name="phone"
          type="tel"
          inputMode="numeric"
          placeholder="5XXXXXXXXX"
          value={displayValue}
          onChange={(e) => {
            const digits = String(e.target.value || "").replace(/\D/g, "").slice(0, 10);
            onChange(`+90${digits}`);
          }}
          className="flex-1 h-full bg-transparent text-qblack placeholder:text-qgray px-4 text-sm focus:outline-none"
        />
      </div>
    </div>
  );
}

export default function SellerQuickRegister() {
  const router = useRouter();
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const categories = websiteSetup?.payload?.productCategories || [];
  const imgThumb =
    websiteSetup?.payload?.image_content?.login_image || IMAGE_FALLBACK;

  const [form, setForm] = useState({
    shop_name: "",
    contact_name: "",
    phone: "+90",
    email: "",
    state_id: "",
    city_id: "",
    category_ids: [],
  });
  const [success, setSuccess] = useState(null);

  const { data: statesData, isLoading: statesLoading } =
    useGetPublicSellerRegisterStatesQuery();
  const [register, { isLoading }] = usePublicSellerRegisterMutation();
  const [consentValues, setConsentValues] = useState({});
  const requiredConsentsAccepted = allRequiredChecked(
    SELLER_REGISTER_REQUIRED_CONSENTS,
    consentValues
  );
  const [fetchCities, { data: citiesData, isFetching: citiesLoading }] =
    useLazyGetCityListApiQuery();

  const states = useMemo(
    () => dedupeTurkishLocations(statesData?.states || []),
    [statesData]
  );
  const cities = useMemo(
    () => dedupeTurkishLocations(citiesData?.cities || []),
    [citiesData]
  );

  useEffect(() => {
    if (!form.state_id) {
      return;
    }
    fetchCities({ stateId: form.state_id });
  }, [form.state_id, fetchCities]);

  const loginPhoneHint = useMemo(() => {
    const digits = form.phone.replace(/\D/g, "");
    if (digits.length >= 10) {
      return digits.slice(-10);
    }
    return "telefon numaranızın son 10 hanesi";
  }, [form.phone]);

  const updateField = (field, value) => {
    setForm((prev) => {
      const next = { ...prev, [field]: value };
      if (field === "state_id") {
        next.city_id = "";
      }
      return next;
    });
  };

  const handleInputChange = (event) => {
    const { name, value } = event.target;
    updateField(name, value);
  };

  const toggleCategory = (categoryId) => {
    setForm((prev) => {
      const id = Number(categoryId);
      const exists = prev.category_ids.includes(id);

      return {
        ...prev,
        category_ids: exists
          ? prev.category_ids.filter((item) => item !== id)
          : [...prev.category_ids, id],
      };
    });
  };

  const handleSubmit = async (event) => {
    event.preventDefault();

    if (!requiredConsentsAccepted) {
      toast.error("Satıcı kaydı için zorunlu yasal metinleri kabul etmelisiniz.");
      return;
    }

    const legalConsents = [
      ...SELLER_REGISTER_REQUIRED_CONSENTS.flatMap((item, index) => {
        const key = item.key || item.slug || `consent-${index}`;
        if (!consentValues[key]) return [];
        const slugs = item.slugs || (item.slug ? [item.slug] : []);
        return slugs.map((slug) => ({ slug, status: true }));
      }),
      ...SELLER_REGISTER_OPTIONAL_CONSENTS.filter((item) => consentValues[item.key]).map(
        (item) => ({ slug: item.slug, status: true })
      ),
    ];

    const payload = {
      shop_name: form.shop_name.trim(),
      contact_name: form.contact_name.trim(),
      phone: form.phone.trim(),
      email: form.email.trim() || null,
      state_id: form.state_id ? Number(form.state_id) : null,
      city_id: form.city_id ? Number(form.city_id) : null,
      category_ids: form.category_ids.map(Number),
      legal_consents: legalConsents,
    };

    try {
      const response = await register(payload).unwrap();
      try {
        await recordLegalConsents({
          consents: legalConsents,
          context: "seller_register",
        });
      } catch {
        // backend also records consents
      }
      setSuccess(response.data || response);
      toast.success(response.message || "Kayıt oluşturuldu.");
    } catch (error) {
      const message =
        error?.data?.errors?.legal_consents?.[0] ||
        error?.data?.message ||
        error?.data?.errors?.phone?.[0] ||
        "Kayıt oluşturulamadı. Lütfen bilgileri ve yasal onayları kontrol edin.";
      toast.error(message);
    }
  };

  if (success) {
    return (
      <LoginLayout imgThumb={imgThumb}>
        <div className="w-full">
          <div className="title-area flex flex-col justify-center items-center relative text-center mb-7">
            <h2 className="text-[34px] font-bold leading-[74px] text-qblack">Kayıt Tamamlandı</h2>
            <div className="shape -mt-6">
              <LoginShape />
            </div>
          </div>

          <div className="rounded-xl border border-qgray-border bg-[#FAFAFA] p-5 text-sm leading-7 text-qgray">
            <p className="mb-3">
              Hoş geldiniz, <strong className="text-qblack">{success.shop_name}</strong>!
            </p>
            <p className="mb-3">
              Tek kullanımlık şifreniz SMS ile gönderildi. Satıcı girişinde kullanıcı adı
              olarak telefon numaranızın son 10 hanesini, şifre olarak SMS&apos;teki kodu
              kullanın.
            </p>
            <p className="mb-1">
              Kullanıcı adı: <strong className="text-qblack">{loginPhoneHint}</strong>
            </p>
            <p>
              SMS durumu:{" "}
              <strong className="text-qblack">
                {success.sms_sent ? "Gönderildi" : "Gönderilemedi"}
              </strong>
            </p>
          </div>

          <div className="mt-6 space-y-3">
            <Link
              href="/satici-giris"
              className="black-btn flex h-[50px] w-full items-center justify-center bg-purple text-sm font-semibold text-white"
            >
              Satıcı Girişi Yap
            </Link>
            <button
              type="button"
              onClick={() => router.push("/")}
              className="flex h-[50px] w-full items-center justify-center border border-qgray-border text-sm font-semibold text-qblack"
            >
              Anasayfaya Dön
            </button>
          </div>
        </div>
      </LoginLayout>
    );
  }

  const allConsentItems = [
    ...SELLER_REGISTER_REQUIRED_CONSENTS,
    ...SELLER_REGISTER_OPTIONAL_CONSENTS,
  ];

  return (
    <LoginLayout imgThumb={imgThumb} scrollable>
      <div className="w-full">
        <div className="title-area flex flex-col justify-center items-center relative text-center mb-4">
          <h2 className="text-[26px] sm:text-[34px] font-bold leading-tight text-qblack">Satıcı Ol</h2>
          <div className="shape -mt-3">
            <LoginShape />
          </div>
          <p className="mt-1.5 text-sm text-[#555]">Hızlı kayıt — SMS ile giriş bilgileriniz gönderilir</p>
        </div>

        <form onSubmit={handleSubmit} className="input-area">
          <div className="input-item mb-4">
            <InputCom
              label="Firma / Dükkan Adı*"
              placeholder="Örn. Güzellik Merkezi"
              name="shop_name"
              type="text"
              inputClasses="h-[50px]"
              inputHandler={handleInputChange}
              value={form.shop_name}
            />
          </div>

          <div className="input-item mb-4">
            <InputCom
              label="Yetkili Ad Soyad*"
              placeholder="Adınız Soyadınız"
              name="contact_name"
              type="text"
              inputClasses="h-[50px]"
              inputHandler={handleInputChange}
              value={form.contact_name}
            />
          </div>

          <div className="input-item mb-4">
            <PhoneInput value={form.phone} onChange={(phone) => updateField("phone", phone)} />
          </div>

          <div className="input-item mb-4">
            <InputCom
              label="E-posta (opsiyonel)"
              placeholder="ornek@firma.com"
              name="email"
              type="email"
              inputClasses="h-[50px]"
              inputHandler={handleInputChange}
              value={form.email}
            />
          </div>

          <div className="grid gap-4 sm:grid-cols-2 mb-4">
            <div>
              <label className="input-label capitalize block mb-2 text-qgray text-[13px] font-normal">
                İl
              </label>
              <select
                value={form.state_id}
                onChange={(e) => updateField("state_id", e.target.value)}
                className={selectClassName}
                disabled={statesLoading}
              >
                <option value="">Seçiniz</option>
                {states.map((state) => (
                  <option key={state.id} value={state.id}>
                    {state.name}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className="input-label capitalize block mb-2 text-qgray text-[13px] font-normal">
                İlçe
              </label>
              <select
                value={form.city_id}
                onChange={(e) => updateField("city_id", e.target.value)}
                className={selectClassName}
                disabled={!form.state_id || citiesLoading}
              >
                <option value="">{!form.state_id ? "Önce il seçin" : "Seçiniz"}</option>
                {cities.map((city) => (
                  <option key={city.id} value={city.id}>
                    {city.name}
                  </option>
                ))}
              </select>
            </div>
          </div>

          <div className="input-item mb-4">
            <label className="input-label capitalize block mb-2 text-qgray text-[13px] font-normal">
              Satış Yapacağınız Kategoriler (opsiyonel)
            </label>
            <div className="max-h-[112px] overflow-y-auto rounded-md border border-qgray-border bg-white p-2.5">
              {categories.length > 0 ? (
                <div className="space-y-2">
                  {categories.map((category) => {
                    const checked = form.category_ids.includes(Number(category.id));

                    return (
                      <label
                        key={category.id}
                        className={`flex cursor-pointer items-center gap-3 rounded-md border px-3 py-2.5 transition ${
                          checked
                            ? "border-qyellow bg-[#FFFBF0]"
                            : "border-transparent hover:bg-[#FAFAFA]"
                        }`}
                      >
                        <input
                          type="checkbox"
                          checked={checked}
                          onChange={() => toggleCategory(category.id)}
                          className="h-4 w-4 rounded border-qgray-border text-qyellow focus:ring-qyellow"
                        />
                        <span className="text-sm text-qblack">{category.name}</span>
                      </label>
                    );
                  })}
                </div>
              ) : (
                <p className="text-sm text-qgraytwo">Kategori listesi yüklenemedi.</p>
              )}
            </div>
            {form.category_ids.length > 0 && (
              <p className="mt-2 text-xs text-qgray">
                {form.category_ids.length} kategori seçildi
              </p>
            )}
          </div>

          <LegalConsentCheckboxes
            items={allConsentItems}
            values={consentValues}
            onChange={(key, value) => setConsentValues((prev) => ({ ...prev, [key]: value }))}
            required
            title="Yasal Onaylar"
            compact
            className="mb-3"
          />

          <div className="h-24 lg:hidden" aria-hidden="true" />

          <div className="fixed inset-x-0 bottom-0 z-40 border-t border-gray-100 bg-white px-4 py-3 shadow-[0_-6px_20px_rgba(0,0,0,0.08)] lg:static lg:z-auto lg:border-0 lg:p-0 lg:shadow-none">
            <div className="mx-auto w-full max-w-[572px] lg:max-w-none">
              <button
                type="submit"
                disabled={isLoading}
                className="flex h-[50px] w-full items-center justify-center rounded-lg bg-[#27AE60] text-base font-semibold text-white shadow-sm transition hover:bg-[#219653] disabled:cursor-wait disabled:opacity-80"
              >
                {isLoading ? <LoaderStyleOne /> : "Kaydı Oluştur"}
              </button>

              <p className="mt-2 text-center text-sm text-[#666]">
                Zaten satıcı hesabınız var mı?{" "}
                <Link href="/satici-giris" className="font-600 text-qyellow">
                  Satıcı girişi
                </Link>
              </p>
            </div>
          </div>
        </form>
      </div>
    </LoginLayout>
  );
}
