"use client";

import Link from "next/link";
import { useSelector } from "react-redux";
import { resolveProductImageUrl } from "@/utils/productImage";
import GooglePlay from "@/components/Helpers/icons/GooglePlay";
import AppleStore from "@/components/Helpers/icons/AppleStore";

function typeLabel(type) {
  if (type === "berber") return "Berber";
  if (type === "guzellik") return "Güzellik merkezi";
  return "Kuaför";
}

function waLink(phone) {
  const digits = String(phone || "").replace(/\D/g, "");
  if (!digits) return null;
  const normalized = digits.startsWith("90") ? digits : digits.startsWith("0") ? `90${digits.slice(1)}` : `90${digits}`;
  return `https://wa.me/${normalized}`;
}

function mapsLink(salon) {
  if (salon.address_lat != null && salon.address_lng != null) {
    return `https://www.google.com/maps?q=${salon.address_lat},${salon.address_lng}`;
  }
  if (salon.address) {
    return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(salon.address)}`;
  }
  return null;
}

function ClosedState({ title, message }) {
  return (
    <div className="max-w-lg mx-auto rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm">
      <p className="text-xs font-700 uppercase tracking-wide text-[#6D28D9]">Seyfibaba Salon</p>
      <h1 className="mt-3 text-2xl font-800 text-qblack">{title}</h1>
      <p className="mt-3 text-sm leading-6 text-qgray">{message}</p>
      <Link
        href="/"
        className="inline-flex mt-6 h-10 items-center rounded-xl bg-qyellow px-4 text-sm font-700 text-qblack"
      >
        Seyfibaba’ya dön
      </Link>
    </div>
  );
}

export default function SalonWebsiteClient({ data }) {
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const download = websiteSetup?.payload?.flashSaleSidebarBanner;
  const playStoreUrl = download?.play_store?.trim();
  const appStoreUrl = download?.app_store?.trim();

  if (!data || data.status === "not_found") {
    return (
      <ClosedState
        title="Salon sitesi bulunamadı"
        message="Link hatalı olabilir veya salon henüz web sitesini açmamış olabilir."
      />
    );
  }

  if (data.status === "subscription_inactive") {
    return (
      <ClosedState
        title={data.salon_name || "Salon sitesi pasif"}
        message={data.message || "Abonelik / erişim kapalı olduğu için site şu an yayında değil."}
      />
    );
  }

  if (data.status === "closed") {
    return (
      <ClosedState
        title={data.salon_name || "Site kapalı"}
        message={data.message || "Salon sahibi web sitesini geçici olarak kapattı."}
      />
    );
  }

  const salon = data.salon || {};
  const flags = data.flags || {};
  const services = data.services || [];
  const calendar = data.calendar;
  const book = data.book || {};
  const logo = resolveProductImageUrl(salon.logo_image);
  const phoneHref = salon.phone ? `tel:${String(salon.phone).replace(/\s/g, "")}` : null;
  const whatsapp = waLink(salon.whatsapp || salon.phone);
  const instagram = salon.instagram
    ? `https://instagram.com/${String(salon.instagram).replace(/^@/, "")}`
    : null;
  const mapHref = mapsLink(salon);
  const hours = `${String(salon.open_hour ?? 9).padStart(2, "0")}:00 – ${String(salon.close_hour ?? 21).padStart(2, "0")}:00`;

  return (
    <div className="max-w-2xl mx-auto">
      <div className="rounded-2xl border border-gray-100 bg-white overflow-hidden shadow-sm">
        <div className="bg-gradient-to-br from-[#f8f5ff] to-white px-5 pt-6 pb-5">
          <div className="flex items-start gap-4">
            <div className="h-16 w-16 rounded-2xl bg-white border border-gray-100 overflow-hidden flex items-center justify-center shrink-0">
              {logo ? (
                // eslint-disable-next-line @next/next/no-img-element
                <img src={logo} alt={salon.name || "Salon"} className="h-full w-full object-cover" />
              ) : (
                <span className="text-xl font-800 text-[#6D28D9]">
                  {(salon.name || "S").slice(0, 1).toUpperCase()}
                </span>
              )}
            </div>
            <div className="min-w-0">
              <p className="text-xs font-700 uppercase tracking-wide text-[#6D28D9]">
                {typeLabel(salon.type)}
                {salon.district || salon.province
                  ? ` · ${[salon.district, salon.province].filter(Boolean).join(", ")}`
                  : ""}
              </p>
              <h1 className="mt-1 text-2xl font-800 text-qblack break-words">{salon.name}</h1>
              {salon.profile_text ? (
                <p className="mt-2 text-sm leading-6 text-[#4b5563]">{salon.profile_text}</p>
              ) : null}
            </div>
          </div>

          <div className="mt-5 flex flex-wrap gap-2">
            {phoneHref ? (
              <a href={phoneHref} className="inline-flex h-10 items-center rounded-xl bg-qblack px-4 text-sm font-700 text-white">
                Telefon
              </a>
            ) : null}
            {whatsapp ? (
              <a href={whatsapp} target="_blank" rel="noreferrer" className="inline-flex h-10 items-center rounded-xl bg-[#25D366] px-4 text-sm font-700 text-white">
                WhatsApp
              </a>
            ) : null}
            {instagram ? (
              <a href={instagram} target="_blank" rel="noreferrer" className="inline-flex h-10 items-center rounded-xl bg-gradient-to-r from-[#f58529] via-[#dd2a7b] to-[#8134af] px-4 text-sm font-700 text-white">
                Instagram
              </a>
            ) : null}
          </div>
        </div>

        <div className="px-5 py-5 space-y-6">
          <section>
            <h2 className="text-sm font-800 text-qblack">Çalışma saatleri</h2>
            <p className="mt-1 text-sm text-qgray">{hours}</p>
          </section>

          {(salon.address || mapHref) ? (
            <section>
              <h2 className="text-sm font-800 text-qblack">Adres</h2>
              {salon.address ? <p className="mt-1 text-sm text-[#4b5563]">{salon.address}</p> : null}
              {mapHref ? (
                <a
                  href={mapHref}
                  target="_blank"
                  rel="noreferrer"
                  className="inline-flex mt-3 h-10 items-center rounded-xl border border-gray-200 px-4 text-sm font-700 text-qblack"
                >
                  Haritada aç
                </a>
              ) : null}
            </section>
          ) : null}

          <section>
            <h2 className="text-sm font-800 text-qblack">Hizmetler</h2>
            {services.length === 0 ? (
              <p className="mt-2 text-sm text-qgray">Henüz hizmet eklenmemiş.</p>
            ) : (
              <ul className="mt-3 divide-y divide-gray-100 border border-gray-100 rounded-xl overflow-hidden">
                {services.map((svc) => (
                  <li key={svc.id} className="flex items-center justify-between gap-3 px-4 py-3 bg-white">
                    <div>
                      <p className="text-sm font-700 text-qblack">{svc.name}</p>
                      <p className="text-xs text-qgray">{svc.duration_minutes} dk</p>
                    </div>
                    {flags.show_prices && svc.price != null ? (
                      <p className="text-sm font-800 text-qblack whitespace-nowrap">
                        {Number(svc.price).toLocaleString("tr-TR")} ₺
                      </p>
                    ) : null}
                  </li>
                ))}
              </ul>
            )}
          </section>

          {flags.show_calendar && calendar?.days ? (
            <section>
              <h2 className="text-sm font-800 text-qblack">Takvim (dolu saatler)</h2>
              <p className="mt-1 text-xs text-qgray">Yalnızca dolu / kapalı aralıklar görünür.</p>
              <div className="mt-3 space-y-3">
                {calendar.days.map((day) => (
                  <div key={day.date} className="rounded-xl border border-gray-100 p-3">
                    <div className="flex items-baseline justify-between gap-2">
                      <p className="text-sm font-700 text-qblack">{day.label}</p>
                      <span className="text-xs text-qgray">{day.date}</span>
                    </div>
                    {(day.slots || []).length === 0 ? (
                      <p className="mt-2 text-xs text-qgray">Boş görünüyor</p>
                    ) : (
                      <div className="mt-2 flex flex-wrap gap-2">
                        {day.slots.map((slot, idx) => (
                          <span
                            key={`${day.date}-${idx}`}
                            className={`inline-flex rounded-lg px-2.5 py-1 text-xs font-700 ${
                              slot.kind === "closed"
                                ? "bg-gray-100 text-qgray"
                                : "bg-[#f3e8ff] text-[#6D28D9]"
                            }`}
                          >
                            {slot.start}–{slot.end}
                          </span>
                        ))}
                      </div>
                    )}
                  </div>
                ))}
              </div>
            </section>
          ) : null}

          <section className="rounded-2xl bg-[#f8f5ff] p-4">
            <h2 className="text-sm font-800 text-qblack">Randevu al</h2>
            <p className="mt-2 text-sm leading-6 text-[#4b5563]">
              {book.app_hint || "Randevu için Seyfibaba uygulamasını indirin."}
            </p>
            {book.join_code ? (
              <p className="mt-2 text-sm font-700 text-[#6D28D9]">
                Berber kodu: {book.join_code}
              </p>
            ) : null}
            <div className="mt-4 flex flex-wrap gap-3">
              {playStoreUrl ? (
                <a href={playStoreUrl} target="_blank" rel="noreferrer" aria-label="Google Play">
                  <GooglePlay />
                </a>
              ) : null}
              {appStoreUrl ? (
                <a href={appStoreUrl} target="_blank" rel="noreferrer" aria-label="App Store">
                  <AppleStore />
                </a>
              ) : null}
            </div>
          </section>

          {data.qr_url ? (
            <section className="text-center border-t border-gray-100 pt-5">
              <h2 className="text-sm font-800 text-qblack">QR kod</h2>
              <p className="mt-1 text-xs text-qgray">Yazdırıp dükkana asabilirsiniz</p>
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img
                src={data.qr_url}
                alt="Salon site QR"
                className="mx-auto mt-3 h-40 w-40 rounded-xl border border-gray-100 bg-white p-2"
              />
            </section>
          ) : null}
        </div>
      </div>
    </div>
  );
}
