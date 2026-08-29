"use client";
import PageTitle from "../Helpers/PageTitle";
import ServeLangItem from "../Helpers/ServeLangItem";
import ContactPhoneIco from "../Helpers/icons/ContactPhoneIco";
import ContactEmailIco from "../Helpers/icons/ContactEmailIco";
import ContactLocationIco from "../Helpers/icons/ContactLocationIco";
import ContactForm from "./ContactForm";

function normalizeWhatsapp(value, phoneFallback) {
  const raw = String(value || phoneFallback || "08503035073").trim();
  let digits = raw.replace(/\D+/g, "");
  if (!digits) return "908503035073";
  if (digits.startsWith("0") && digits.length === 11) {
    digits = `90${digits.slice(1)}`;
  } else if (digits.length === 10) {
    digits = `90${digits}`;
  }
  return digits;
}

function formatTrPhoneDisplay(digits) {
  const d = String(digits || "").replace(/\D+/g, "");
  // 908503035073 → 0850 303 5073
  if (d.startsWith("90") && d.length === 12) {
    const local = `0${d.slice(2)}`;
    return `${local.slice(0, 4)} ${local.slice(4, 7)} ${local.slice(7)}`;
  }
  if (d.startsWith("0") && d.length === 11) {
    return `${d.slice(0, 4)} ${d.slice(4, 7)} ${d.slice(7)}`;
  }
  return digits;
}

function WhatsAppIcon() {
  return (
    <svg width="36" height="36" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden>
      <path
        d="M16.004 3C8.832 3 3 8.79 3 15.91c0 2.27.61 4.48 1.77 6.43L3 29l6.86-1.73A13.05 13.05 0 0 0 16.004 29C23.176 29 29 23.21 29 16.09 29 8.97 23.176 3 16.004 3Z"
        fill="#25D366"
      />
      <path
        d="M22.35 18.92c-.28-.14-1.66-.82-1.92-.91-.26-.1-.45-.14-.64.14-.19.28-.73.91-.9 1.1-.16.19-.33.21-.61.07-.28-.14-1.18-.43-2.25-1.38-.83-.74-1.39-1.65-1.55-1.93-.16-.28-.02-.43.12-.57.13-.13.28-.33.42-.5.14-.16.19-.28.28-.47.1-.19.05-.35-.02-.5-.07-.14-.64-1.54-.88-2.11-.23-.55-.47-.48-.64-.49h-.55c-.19 0-.5.07-.76.35-.26.28-1 1-1 2.43s1.02 2.82 1.16 3.01c.14.19 2.01 3.07 4.88 4.3 1.82.79 2.54.86 3.45.72.53-.08 1.66-.68 1.9-1.33.23-.66.23-1.22.16-1.33-.07-.12-.26-.19-.54-.33Z"
        fill="#fff"
      />
    </svg>
  );
}

export default function Contact({ datas }) {
  const contact = datas?.contact || null;
  const rawMapValue = contact?.map || "";
  const addressValue = contact?.address || "";
  // WhatsApp resmi hat — telefona düşmesin (eski kişisel numara karışmasın)
  const whatsappDigits = normalizeWhatsapp(contact?.whatsapp, "08503035073");
  const whatsappUrl = `https://wa.me/${whatsappDigits}`;
  const whatsappDisplay = formatTrPhoneDisplay(whatsappDigits);

  const resolveMapUrl = (mapValue, address) => {
    const normalizedMap = String(mapValue || "").trim();

    if (normalizedMap.startsWith("<")) {
      const srcMatch = normalizedMap.match(/src=["']([^"']+)["']/i);
      if (srcMatch?.[1]) {
        return srcMatch[1];
      }
    }

    if (normalizedMap.includes("output=embed") || normalizedMap.includes("/maps/embed")) {
      return normalizedMap;
    }

    if (normalizedMap.includes("google.com/maps")) {
      const coordMatch = normalizedMap.match(/@(-?\d+\.?\d*),(-?\d+\.?\d*)/);
      if (coordMatch) {
        return `https://maps.google.com/maps?q=${coordMatch[1]},${coordMatch[2]}&z=15&output=embed`;
      }
      const placeMatch = normalizedMap.match(/place\/([^/@]+)/);
      if (placeMatch) {
        return `https://maps.google.com/maps?q=${placeMatch[1]}&z=15&output=embed`;
      }
    }

    const query = address || "İstiklal Mahallesi, Serdivan, Sakarya, Türkiye";
    return `https://maps.google.com/maps?q=${encodeURIComponent(query)}&z=15&output=embed`;
  };

  const mapUrl = resolveMapUrl(rawMapValue, addressValue);

  const contactInfo = [
    {
      icon: <ContactPhoneIco />,
      title: ServeLangItem()?.phone || "Telefon",
      value: contact?.phone || "0850 303 5073",
      href: `tel:${String(contact?.phone || "08503035073").replace(/\s+/g, "")}`,
      bgColor: "bg-[#FFEAE5]",
    },
    {
      icon: <ContactEmailIco />,
      title: ServeLangItem()?.Email || "E-posta",
      value: contact?.email || "info@seyfibaba.com",
      href: `mailto:${contact?.email || "info@seyfibaba.com"}`,
      bgColor: "bg-[#D3EFFF]",
    },
    {
      icon: <WhatsAppIcon />,
      title: "WhatsApp",
      value: whatsappDisplay,
      href: whatsappUrl,
      bgColor: "bg-[#E8F8EF]",
    },
  ];

  return (
    <>
      <div className="page-title mb-10">
        <PageTitle
          title={ServeLangItem()?.Contact_Us || "İletişim"}
          breadcrumb={[
            { name: ServeLangItem()?.home || "Ana Sayfa", path: "/" },
            { name: ServeLangItem()?.Contact_Us || "İletişim", path: "/contact" },
          ]}
        />
      </div>

      <div className="contact-wrapper w-full mb-10">
        <div className="container-x mx-auto">
          <div className="main-wrapper w-full lg:flex lg:space-x-[30px] rtl:space-x-reverse">
            <div className="lg:w-1/2 w-full">
              {contact && (
                <div>
                  <h2 className="text-[22px] font-semibold text-qblack leading-[30px] mb-1">
                    {contact.title}
                  </h2>
                  <p className="text-[15px] text-qgraytwo leading-[30px] mb-5">
                    {contact.description}
                  </p>

                  <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-[16px] mb-[30px]">
                    {contactInfo.map((info, index) => (
                      <div
                        key={index}
                        className={`min-h-[180px] flex flex-col justify-center ${info.bgColor} p-5 rounded`}
                      >
                        <div className="flex justify-center mb-3">{info.icon}</div>
                        <p className="text-[20px] text-black leading-[28px] text-center font-semibold">
                          {info.title}
                        </p>
                        {info.href ? (
                          <a
                            href={info.href}
                            target={info.title === "WhatsApp" ? "_blank" : undefined}
                            rel={info.title === "WhatsApp" ? "noopener noreferrer" : undefined}
                            className="text-[14px] text-black leading-[28px] text-center underline-offset-2 hover:underline break-all"
                          >
                            {info.value}
                          </a>
                        ) : (
                          <p className="text-[14px] text-black leading-[28px] text-center break-all">
                            {info.value}
                          </p>
                        )}
                      </div>
                    ))}
                  </div>

                  <div className="p-5 flex flex-col justify-between w-full bg-[#E7F2EC]">
                    <div className="flex space-x-5 rtl:space-x-reverse">
                      <span>
                        <ContactLocationIco />
                      </span>
                      <div>
                        <h2 className="text-[22px] font-semibold text-qblack leading-[30px] mb-2">
                          {ServeLangItem()?.Address || "Adres"}
                        </h2>
                        <p className="text-[15px] text-qblack leading-[30px]">
                          {contact.address}
                        </p>
                      </div>
                    </div>
                    <div className="w-full h-[206px] mt-5">
                      {mapUrl ? (
                        <iframe
                          title="contact-map"
                          src={mapUrl}
                          style={{ border: "0", width: "100%", height: "100%" }}
                          allowFullScreen
                          loading="lazy"
                          referrerPolicy="no-referrer-when-downgrade"
                        />
                      ) : (
                        <div className="flex h-full items-center justify-center rounded bg-white text-sm text-qgray">
                          Harita bilgisi bulunamadı
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              )}
            </div>

            <div className="flex-1 bg-white sm:p-10 p-3">
              <div className="title flex flex-col items-center">
                <h2 className="text-[34px] font-bold text-qblack">
                  {ServeLangItem()?.Get_In_Touch || "Bizimle İletişime Geçin"}
                </h2>
                <span className="-mt-5 block">
                  <svg
                    width="354"
                    height="30"
                    viewBox="0 0 354 30"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                  >
                    <path
                      d="M1 28.8027C17.6508 20.3626 63.9476 8.17089 113.509 17.8802C166.729 28.3062 341.329 42.704 353 1"
                      stroke="#FCBF49"
                      strokeWidth="2"
                      strokeLinecap="round"
                    />
                  </svg>
                </span>
              </div>
              <ContactForm />
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
