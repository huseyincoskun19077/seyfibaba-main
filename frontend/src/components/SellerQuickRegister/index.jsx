"use client";

import { useEffect, useMemo, useState } from "react";
import Image from "next/image";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { toast } from "react-toastify";
import {
  useGetPublicSellerRegisterStatesQuery,
  usePublicSellerRegisterMutation,
} from "@/redux/features/sellerRegister/apiSlice";
import { useLazyGetCityListApiQuery } from "@/redux/features/locations/apiSlice";
import { dedupeTurkishLocations } from "@/utils/dedupeTurkishLocations";
import LegalConsentCheckboxes, {
  allRequiredChecked,
} from "@/components/Legal/LegalConsentCheckboxes";
import {
  SELLER_REGISTER_OPTIONAL_CONSENTS,
  SELLER_REGISTER_REQUIRED_CONSENTS,
} from "@/config/legalDocuments";
import { recordLegalConsents } from "@/api/recordLegalConsents";
import { hasMarketingConsent } from "@/components/Helpers/Consent";
import LoaderStyleOne from "../Helpers/Loaders/LoaderStyleOne";

const COMPANY_TYPES = [
  { value: "sahis", label: "Şahıs Şirketi" },
  { value: "limited", label: "Limited Şirket" },
  { value: "anonim", label: "Anonim Şirket" },
  { value: "diger", label: "Diğer" },
];

const SELLER_PROFILES = [
  { value: "uretici", label: "Üretici" },
  { value: "yetkili_bayi", label: "Yetkili Bayi" },
  { value: "toptanci", label: "Toptancı" },
];

const fieldLabel =
  "mb-1.5 block text-[12px] font-700 uppercase tracking-wide text-[#04334a]";
const fieldInput =
  "h-[46px] w-full rounded-md border-0 bg-[#F4F6F7] px-3.5 text-sm text-[#04334a] placeholder:text-[#04334a]/40 focus:outline-none focus:ring-2 focus:ring-[#FCBF49]/60";
const fieldSelect =
  "h-[46px] w-full rounded-md border-0 bg-[#F4F6F7] px-3.5 text-sm text-[#04334a] focus:outline-none focus:ring-2 focus:ring-[#FCBF49]/60";
const fieldTextarea =
  "min-h-[96px] w-full rounded-md border-0 bg-[#F4F6F7] px-3.5 py-3 text-sm text-[#04334a] placeholder:text-[#04334a]/40 focus:outline-none focus:ring-2 focus:ring-[#FCBF49]/60 resize-y";

function PhoneInput({ value, onChange }) {
  const displayValue = String(value || "").replace(/^\+90/, "");

  return (
    <div>
      <label className={fieldLabel}>
        İletişim Telefon <span className="text-[#E11D48]">*</span>
      </label>
      <div className="flex h-[46px] items-center overflow-hidden rounded-md bg-[#F4F6F7] focus-within:ring-2 focus-within:ring-[#FCBF49]/60">
        <div className="flex h-full items-center gap-2 border-r border-[#04334a]/10 px-3">
          <Image width={18} height={12} src="/assets/images/countries/TR.svg" alt="TR" />
          <span className="text-sm font-600 text-[#04334a]">+90</span>
        </div>
        <input
          name="phone"
          type="tel"
          inputMode="numeric"
          placeholder="5XXXXXXXXX"
          value={displayValue}
          onChange={(e) => {
            const digits = String(e.target.value || "")
              .replace(/\D/g, "")
              .slice(0, 10);
            onChange(`+90${digits}`);
          }}
          className="h-full flex-1 bg-transparent px-3 text-sm text-[#04334a] placeholder:text-[#04334a]/40 focus:outline-none"
          required
        />
      </div>
    </div>
  );
}

function Field({
  label,
  name,
  required = false,
  type = "text",
  value,
  onChange,
  placeholder = "",
  as = "input",
}) {
  return (
    <div className={as === "textarea" ? "sm:col-span-2" : ""}>
      <label className={fieldLabel} htmlFor={name}>
        {label}
        {required ? <span className="text-[#E11D48]"> *</span> : null}
      </label>
      {as === "textarea" ? (
        <textarea
          id={name}
          name={name}
          value={value}
          onChange={onChange}
          placeholder={placeholder}
          className={fieldTextarea}
          required={required}
        />
      ) : (
        <input
          id={name}
          name={name}
          type={type}
          value={value}
          onChange={onChange}
          placeholder={placeholder}
          className={fieldInput}
          required={required}
        />
      )}
    </div>
  );
}

const initialForm = {
  contact_name: "",
  phone: "+90",
  email: "",
  company_type: "",
  tax_number: "",
  tax_office: "",
  state_id: "",
  city_id: "",
  kep_address: "",
  hq_address: "",
  shop_name: "",
  reference_code: "",
  marketplaces: "",
  integrators: "",
  brands_portfolio: "",
  cargo_prefs: "",
  seller_profiles: [],
  stock_continuous: "",
};

export default function SellerQuickRegister() {
  const router = useRouter();
  const [form, setForm] = useState(initialForm);
  const [success, setSuccess] = useState(null);
  const [consentValues, setConsentValues] = useState({});

  const { data: statesData, isLoading: statesLoading } =
    useGetPublicSellerRegisterStatesQuery();
  const [register, { isLoading }] = usePublicSellerRegisterMutation();
  const [fetchCities, { data: citiesData, isFetching: citiesLoading }] =
    useLazyGetCityListApiQuery();

  const requiredConsentsAccepted = allRequiredChecked(
    SELLER_REGISTER_REQUIRED_CONSENTS,
    consentValues
  );

  const states = useMemo(
    () => dedupeTurkishLocations(statesData?.states || []),
    [statesData]
  );
  const cities = useMemo(
    () => dedupeTurkishLocations(citiesData?.cities || []),
    [citiesData]
  );

  useEffect(() => {
    if (!form.state_id) return;
    fetchCities({ stateId: form.state_id });
  }, [form.state_id, fetchCities]);

  const loginPhoneHint = useMemo(() => {
    const digits = form.phone.replace(/\D/g, "");
    if (digits.length >= 10) return digits.slice(-10);
    return "telefon numaranızın son 10 hanesi";
  }, [form.phone]);

  const updateField = (field, value) => {
    setForm((prev) => {
      const next = { ...prev, [field]: value };
      if (field === "state_id") next.city_id = "";
      return next;
    });
  };

  const handleInputChange = (event) => {
    const { name, value } = event.target;
    updateField(name, value);
  };

  const toggleProfile = (value) => {
    setForm((prev) => {
      const exists = prev.seller_profiles.includes(value);
      return {
        ...prev,
        seller_profiles: exists
          ? prev.seller_profiles.filter((item) => item !== value)
          : [...prev.seller_profiles, value],
      };
    });
  };

  const handleSubmit = async (event) => {
    event.preventDefault();

    if (!requiredConsentsAccepted) {
      toast.error("Satıcı kaydı için zorunlu yasal metinleri kabul etmelisiniz.");
      return;
    }

    const phoneDigits = form.phone.replace(/\D/g, "");
    if (phoneDigits.slice(-10).length < 10) {
      toast.error("Geçerli bir telefon numarası girin.");
      return;
    }

    const legalConsents = [
      ...SELLER_REGISTER_REQUIRED_CONSENTS.flatMap((item, index) => {
        const key = item.key || item.slug || `consent-${index}`;
        if (!consentValues[key]) return [];
        const slugs = item.slugs || (item.slug ? [item.slug] : []);
        return slugs.map((slug) => ({ slug, status: true }));
      }),
      ...SELLER_REGISTER_OPTIONAL_CONSENTS.filter(
        (item) => consentValues[item.key]
      ).map((item) => ({ slug: item.slug, status: true })),
    ];

    const payload = {
      shop_name: form.shop_name.trim(),
      contact_name: form.contact_name.trim(),
      phone: form.phone.trim(),
      email: form.email.trim() || null,
      state_id: form.state_id ? Number(form.state_id) : null,
      city_id: form.city_id ? Number(form.city_id) : null,
      company_type: form.company_type || null,
      tax_number: form.tax_number.trim() || null,
      tax_office: form.tax_office.trim() || null,
      kep_address: form.kep_address.trim() || null,
      hq_address: form.hq_address.trim() || null,
      reference_code: form.reference_code.trim() || null,
      marketplaces: form.marketplaces.trim() || null,
      integrators: form.integrators.trim() || null,
      brands_portfolio: form.brands_portfolio.trim() || null,
      cargo_prefs: form.cargo_prefs.trim() || null,
      seller_profiles: form.seller_profiles,
      stock_continuous: form.stock_continuous || null,
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
        // backend also records
      }
      setSuccess(response.data || response);
      toast.success(response.message || "Başvurunuz alındı.");
      try {
        if (hasMarketingConsent()) {
          const ReactPixel = (await import("react-facebook-pixel")).default;
          ReactPixel.track("Lead", {
            content_name: "seller_register",
            content_category: "seller",
          });
          ReactPixel.track("CompleteRegistration", {
            content_name: "seller_register",
            status: true,
          });
        }
      } catch {
        // Pixel yoksa sessiz geç
      }
    } catch (error) {
      const message =
        error?.data?.errors?.legal_consents?.[0] ||
        error?.data?.message ||
        error?.data?.errors?.phone?.[0] ||
        "Başvuru gönderilemedi. Lütfen bilgileri ve yasal onayları kontrol edin.";
      toast.error(message);
    }
  };

  const allConsentItems = [...SELLER_REGISTER_REQUIRED_CONSENTS];

  if (success) {
    return (
      <div className="w-full bg-[#F3F4F6] py-10 sm:py-14">
        <div className="container-x mx-auto max-w-2xl">
          <div className="rounded-2xl border border-[#04334a]/10 bg-white p-6 sm:p-10 text-center shadow-sm">
            <h1 className="text-2xl font-800 text-[#04334a]">Başvurunuz Alındı</h1>
            <div className="mx-auto mt-2 h-1 w-16 rounded-full bg-[#FCBF49]" />
            <p className="mt-5 text-sm leading-7 text-[#04334a]/75">
              Hoş geldiniz, <strong className="text-[#04334a]">{success.shop_name}</strong>.
              Tek kullanımlık şifreniz SMS ile gönderildi. Satıcı girişinde kullanıcı adı
              olarak <strong>{loginPhoneHint}</strong> kullanın.
            </p>
            <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
              <Link
                href="/satici-giris"
                className="inline-flex h-12 items-center justify-center rounded-lg bg-[#04334a] px-6 text-sm font-700 text-white hover:bg-[#032736]"
              >
                Satıcı Girişi Yap
              </Link>
              <button
                type="button"
                onClick={() => router.push("/")}
                className="inline-flex h-12 items-center justify-center rounded-lg border border-[#04334a]/20 bg-white px-6 text-sm font-700 text-[#04334a] hover:bg-[#F4F6F7]"
              >
                Anasayfaya Dön
              </button>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="w-full bg-[#F3F4F6] py-8 sm:py-12">
      <div className="container-x mx-auto max-w-5xl">
        <div className="rounded-2xl border border-[#04334a]/8 bg-white px-4 py-6 shadow-sm sm:px-8 sm:py-10 lg:px-12">
          <div className="mb-8 text-center">
            <h1 className="text-[22px] font-800 leading-tight text-[#04334a] sm:text-[28px]">
              Satıcı olmak için başvurun
            </h1>
            <div className="mx-auto mt-2 h-1 w-20 rounded-full bg-[#FCBF49]" />
            <Link
              href="/yardim"
              className="mt-4 inline-flex items-center gap-2 rounded-full border border-[#04334a]/15 bg-white px-4 py-2 text-xs font-600 text-[#04334a] transition hover:border-[#FCBF49] hover:bg-[#FFF8E8]"
            >
              <svg
                width="14"
                height="14"
                viewBox="0 0 24 24"
                fill="none"
                aria-hidden="true"
              >
                <circle cx="12" cy="12" r="9" stroke="currentColor" strokeWidth="1.8" />
                <path
                  d="M12 7v5l3 2"
                  stroke="currentColor"
                  strokeWidth="1.8"
                  strokeLinecap="round"
                />
              </svg>
              Sıkça Sorulan Sorular (SSS)
            </Link>
          </div>

          <form onSubmit={handleSubmit} className="space-y-5">
            <div className="grid gap-4 sm:grid-cols-2">
              <Field
                label="Yetkili Kişi"
                name="contact_name"
                required
                value={form.contact_name}
                onChange={handleInputChange}
                placeholder="Ad Soyad"
              />
              <PhoneInput
                value={form.phone}
                onChange={(phone) => updateField("phone", phone)}
              />
              <Field
                label="E-posta"
                name="email"
                type="email"
                value={form.email}
                onChange={handleInputChange}
                placeholder="ornek@firma.com"
              />
              <div>
                <label className={fieldLabel} htmlFor="company_type">
                  Firma Tipi <span className="text-[#E11D48]">*</span>
                </label>
                <select
                  id="company_type"
                  name="company_type"
                  value={form.company_type}
                  onChange={handleInputChange}
                  className={fieldSelect}
                  required
                >
                  <option value="">Lütfen seçiniz</option>
                  {COMPANY_TYPES.map((item) => (
                    <option key={item.value} value={item.value}>
                      {item.label}
                    </option>
                  ))}
                </select>
              </div>
              <Field
                label="Vergi No"
                name="tax_number"
                value={form.tax_number}
                onChange={handleInputChange}
              />
              <Field
                label="Vergi Dairesi"
                name="tax_office"
                value={form.tax_office}
                onChange={handleInputChange}
              />
              <div>
                <label className={fieldLabel} htmlFor="state_id">
                  İl <span className="text-[#E11D48]">*</span>
                </label>
                <select
                  id="state_id"
                  value={form.state_id}
                  onChange={(e) => updateField("state_id", e.target.value)}
                  className={fieldSelect}
                  disabled={statesLoading}
                  required
                >
                  <option value="">Lütfen Seçiniz</option>
                  {states.map((state) => (
                    <option key={state.id} value={state.id}>
                      {state.name}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className={fieldLabel} htmlFor="city_id">
                  İlçe <span className="text-[#E11D48]">*</span>
                </label>
                <select
                  id="city_id"
                  value={form.city_id}
                  onChange={(e) => updateField("city_id", e.target.value)}
                  className={fieldSelect}
                  disabled={!form.state_id || citiesLoading}
                  required
                >
                  <option value="">
                    {!form.state_id ? "Önce il seçin" : "Lütfen Seçiniz"}
                  </option>
                  {cities.map((city) => (
                    <option key={city.id} value={city.id}>
                      {city.name}
                    </option>
                  ))}
                </select>
              </div>
              <Field
                label="KEP Adresi (Tacirler için zorunludur)"
                name="kep_address"
                value={form.kep_address}
                onChange={handleInputChange}
                placeholder="ornek@hs01.kep.tr"
              />
              <Field
                label="Merkez Adresi"
                name="hq_address"
                required
                value={form.hq_address}
                onChange={handleInputChange}
              />
              <Field
                label="Cari Firma Adı"
                name="shop_name"
                required
                value={form.shop_name}
                onChange={handleInputChange}
              />
              <Field
                label="Referans Kodu"
                name="reference_code"
                value={form.reference_code}
                onChange={handleInputChange}
              />
            </div>

            <Field
              label="Halihazırda satış yaptığınız pazaryerleri ve portallar hangileri?"
              name="marketplaces"
              value={form.marketplaces}
              onChange={handleInputChange}
            />
            <Field
              label="Tercih ettiğiniz entegratör firma ve uygulamaları hangileri?"
              name="integrators"
              value={form.integrators}
              onChange={handleInputChange}
            />
            <Field
              label="Portföyünüzde yer alan markalar hangileri?"
              name="brands_portfolio"
              as="textarea"
              value={form.brands_portfolio}
              onChange={handleInputChange}
            />
            <Field
              label="Çalışmayı tercih ettiğiniz kargo firmaları hangileri?"
              name="cargo_prefs"
              value={form.cargo_prefs}
              onChange={handleInputChange}
            />

            <div>
              <p className={fieldLabel}>Satıcı Profili</p>
              <div className="mt-1 flex flex-wrap gap-4">
                {SELLER_PROFILES.map((item) => {
                  const checked = form.seller_profiles.includes(item.value);
                  return (
                    <label
                      key={item.value}
                      className="inline-flex cursor-pointer items-center gap-2 text-sm text-[#04334a]"
                    >
                      <input
                        type="checkbox"
                        checked={checked}
                        onChange={() => toggleProfile(item.value)}
                        className="h-4 w-4 rounded border-[#04334a]/30 text-[#FCBF49] focus:ring-[#FCBF49]"
                      />
                      {item.label}
                    </label>
                  );
                })}
              </div>
            </div>

            <div>
              <p className={fieldLabel}>
                Satmak istediğiniz markalar stoklarınızda devamlı bulunuyor mu?
              </p>
              <div className="mt-1 flex gap-6">
                {[
                  { value: "yes", label: "Evet" },
                  { value: "no", label: "Hayır" },
                ].map((item) => (
                  <label
                    key={item.value}
                    className="inline-flex cursor-pointer items-center gap-2 text-sm text-[#04334a]"
                  >
                    <input
                      type="radio"
                      name="stock_continuous"
                      value={item.value}
                      checked={form.stock_continuous === item.value}
                      onChange={handleInputChange}
                      className="h-4 w-4 border-[#04334a]/30 text-[#04334a] focus:ring-[#FCBF49]"
                    />
                    {item.label}
                  </label>
                ))}
              </div>
            </div>

            <LegalConsentCheckboxes
              items={allConsentItems}
              values={consentValues}
              onChange={(key, value) =>
                setConsentValues((prev) => ({ ...prev, [key]: value }))
              }
              required
              title=""
              compact
              className="pt-2"
            />

            <div className="pt-2 pb-2 text-center">
              <button
                type="submit"
                disabled={isLoading}
                className="inline-flex h-12 min-w-[220px] items-center justify-center rounded-lg bg-[#04334a] px-10 text-sm font-800 uppercase tracking-wide text-white transition hover:bg-[#032736] disabled:cursor-wait disabled:opacity-70"
              >
                {isLoading ? <LoaderStyleOne /> : "Başvur"}
              </button>
              <p className="mt-3 text-sm text-[#04334a]/60">
                Zaten satıcı hesabınız var mı?{" "}
                <Link href="/satici-giris" className="font-700 text-[#04334a] underline underline-offset-2 hover:text-[#FCBF49]">
                  Satıcı girişi
                </Link>
              </p>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}
