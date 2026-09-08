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
  const normalized = digits.startsWith("90")
    ? digits
    : digits.startsWith("0")
      ? `90${digits.slice(1)}`
      : `90${digits}`;
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
    <div className="mx-auto max-w-lg px-4 py-16">
      <div className="rounded-3xl border border-black/5 bg-white p-10 text-center shadow-[0_20px_60px_rgba(0,0,0,0.08)]">
        <p className="text-[11px] font-700 uppercase tracking-[0.22em] text-[#b45309]">
          Seyfibaba Salon
        </p>
        <h1 className="mt-4 text-3xl font-800 tracking-tight text-[#111]">{title}</h1>
        <p className="mt-4 text-sm leading-7 text-[#6b7280]">{message}</p>
        <Link
          href="/"
          className="mt-8 inline-flex h-11 items-center rounded-full bg-qyellow px-6 text-sm font-700 text-qblack"
        >
          Seyfibaba’ya dön
        </Link>
      </div>
    </div>
  );
}

function SectionTitle({ eyebrow, title, subtitle }) {
  return (
    <div className="mb-5">
      {eyebrow ? (
        <p className="text-[11px] font-700 uppercase tracking-[0.2em] text-[#b45309]">
          {eyebrow}
        </p>
      ) : null}
      <h2 className="mt-1 text-2xl font-800 tracking-tight text-[#111]">{title}</h2>
      {subtitle ? (
        <p className="mt-2 text-sm leading-6 text-[#6b7280]">{subtitle}</p>
      ) : null}
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
        message={
          data.message ||
          "Abonelik / erişim kapalı olduğu için site şu an yayında değil."
        }
      />
    );
  }

  if (data.status === "closed") {
    return (
      <ClosedState
        title={data.salon_name || "Site kapalı"}
        message={
          data.message || "Salon sahibi web sitesini geçici olarak kapattı."
        }
      />
    );
  }

  const salon = data.salon || {};
  const flags = data.flags || {};
  const services = data.services || [];
  const staff = data.staff || [];
  const calendar = data.calendar;
  const book = data.book || {};
  const logo = resolveProductImageUrl(salon.logo_image);
  const cover = resolveProductImageUrl(salon.cover_image);
  const phoneHref = salon.phone
    ? `tel:${String(salon.phone).replace(/\s/g, "")}`
    : null;
  const whatsapp = waLink(salon.whatsapp || salon.phone);
  const instagram = salon.instagram
    ? `https://instagram.com/${String(salon.instagram).replace(/^@/, "")}`
    : null;
  const mapHref = mapsLink(salon);
  const hours = `${String(salon.open_hour ?? 9).padStart(2, "0")}:00 – ${String(
    salon.close_hour ?? 21
  ).padStart(2, "0")}:00`;
  const place = [salon.district, salon.province].filter(Boolean).join(", ");

  return (
    <div className="relative overflow-hidden bg-[#f6f3ee]">
      <style jsx global>{`
        @keyframes salonFadeUp {
          from { opacity: 0; transform: translateY(16px); }
          to { opacity: 1; transform: translateY(0); }
        }
        .salon-fade-up { animation: salonFadeUp 0.65s ease-out both; }
        .salon-fade-up-delay { animation: salonFadeUp 0.75s ease-out 0.12s both; }
        .salon-fade-up-late { animation: salonFadeUp 0.8s ease-out 0.22s both; }
      `}</style>
      {/* Hero */}
      <section className="relative min-h-[52vh] w-full overflow-hidden bg-[#171411]">
        {cover ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img
            src={cover}
            alt=""
            className="absolute inset-0 h-full w-full object-cover opacity-55"
          />
        ) : (
          <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_#3a2a1a_0%,_#171411_55%,_#0d0b0a_100%)]" />
        )}
        <div className="absolute inset-0 bg-gradient-to-t from-[#0d0b0a] via-[#0d0b0a]/65 to-transparent" />
        <div className="absolute inset-0 bg-[linear-gradient(120deg,rgba(180,83,9,0.18),transparent_45%)]" />

        <div className="relative mx-auto flex min-h-[52vh] max-w-5xl flex-col justify-end px-4 pb-10 pt-24 sm:px-6">
          <div className="salon-fade-up flex items-end gap-5">
            <div className="h-24 w-24 shrink-0 overflow-hidden rounded-3xl border border-white/20 bg-white/10 shadow-2xl backdrop-blur-sm sm:h-28 sm:w-28">
              {logo ? (
                // eslint-disable-next-line @next/next/no-img-element
                <img
                  src={logo}
                  alt={salon.name || "Salon"}
                  className="h-full w-full object-cover"
                />
              ) : (
                <div className="flex h-full w-full items-center justify-center text-3xl font-800 text-qyellow">
                  {(salon.name || "S").slice(0, 1).toUpperCase()}
                </div>
              )}
            </div>
            <div className="min-w-0 pb-1">
              <p className="text-[11px] font-700 uppercase tracking-[0.24em] text-qyellow">
                {typeLabel(salon.type)}
                {place ? ` · ${place}` : ""}
              </p>
              <h1 className="mt-2 text-4xl font-800 leading-tight tracking-tight text-white sm:text-5xl">
                {salon.name}
              </h1>
              {salon.profile_text ? (
                <p className="mt-3 max-w-2xl text-sm leading-7 text-white/75 sm:text-base">
                  {salon.profile_text}
                </p>
              ) : null}
            </div>
          </div>

          <div className="salon-fade-up-delay mt-8 flex flex-wrap gap-3">
            {phoneHref ? (
              <a
                href={phoneHref}
                className="inline-flex h-12 items-center rounded-full bg-qyellow px-6 text-sm font-800 text-qblack shadow-lg shadow-black/30"
              >
                Telefon et
              </a>
            ) : null}
            {whatsapp ? (
              <a
                href={whatsapp}
                target="_blank"
                rel="noreferrer"
                className="inline-flex h-12 items-center rounded-full bg-[#25D366] px-6 text-sm font-800 text-white"
              >
                WhatsApp
              </a>
            ) : null}
            {instagram ? (
              <a
                href={instagram}
                target="_blank"
                rel="noreferrer"
                className="inline-flex h-12 items-center rounded-full border border-white/25 bg-white/10 px-6 text-sm font-700 text-white backdrop-blur"
              >
                Instagram
              </a>
            ) : null}
            <a
              href="#randevu"
              className="inline-flex h-12 items-center rounded-full border border-white/20 bg-white/5 px-6 text-sm font-700 text-white backdrop-blur"
            >
              Randevu al
            </a>
          </div>
        </div>
      </section>

      <div className="relative mx-auto max-w-5xl px-4 py-10 sm:px-6 sm:py-14">
        <div className="salon-fade-up-late grid gap-6 lg:grid-cols-[1.4fr_0.9fr]">
          {/* Left column */}
          <div className="space-y-6">
            {staff.length > 0 ? (
              <section className="rounded-[28px] border border-black/5 bg-[#fffdf9] p-6 shadow-[0_18px_50px_rgba(20,17,15,0.06)] sm:p-8">
                <SectionTitle
                  eyebrow="Ekip"
                  title="Personellerimiz"
                  subtitle={
                    flags.show_staff_appointments
                      ? "Her ustanın dolu saatleri aşağıda. Müşteri isimleri paylaşılmaz."
                      : "Salonumuzun ustaları. Randevu detayı için uygulamadan bağlanın."
                  }
                />
                <div className="space-y-5">
                  {staff.map((member) => {
                    const photo = resolveProductImageUrl(member.photo);
                    return (
                      <article
                        key={member.id}
                        className="overflow-hidden rounded-2xl border border-black/[0.04] bg-gradient-to-br from-white to-[#faf7f2]"
                      >
                        <div className="flex items-center gap-4 border-b border-black/[0.04] px-4 py-4">
                          <div className="h-14 w-14 overflow-hidden rounded-2xl bg-[#1a1512] text-qyellow">
                            {photo ? (
                              // eslint-disable-next-line @next/next/no-img-element
                              <img
                                src={photo}
                                alt={member.name}
                                className="h-full w-full object-cover"
                              />
                            ) : (
                              <div className="flex h-full w-full items-center justify-center text-lg font-800">
                                {(member.name || "?").slice(0, 1).toUpperCase()}
                              </div>
                            )}
                          </div>
                          <div>
                            <h3 className="text-lg font-800 text-[#14110f]">
                              {member.name}
                            </h3>
                            <p className="text-xs text-[#6b7280]">
                              {member.show_appointments
                                ? "Dolu saatler görünür"
                                : "Randevu için uygulamayı kullanın"}
                            </p>
                          </div>
                        </div>

                        {member.show_appointments ? (
                          <div className="px-4 py-4">
                            {(member.appointments || []).length === 0 ? (
                              <p className="text-sm text-[#6b7280]">
                                Önümüzdeki günlerde dolu randevu görünmüyor.
                              </p>
                            ) : (
                              <div className="space-y-3">
                                {member.appointments.map((day) => (
                                  <div key={`${member.id}-${day.date}`}>
                                    <div className="mb-2 flex items-baseline justify-between">
                                      <p className="text-sm font-800 text-[#14110f]">
                                        {day.label}
                                      </p>
                                      <span className="text-[11px] text-[#9ca3af]">
                                        {day.date}
                                      </span>
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                      {(day.slots || []).map((slot, idx) => (
                                        <span
                                          key={`${day.date}-${idx}`}
                                          className={`inline-flex rounded-full px-3 py-1.5 text-xs font-700 ${
                                            slot.kind === "closed"
                                              ? "bg-[#f3f4f6] text-[#6b7280]"
                                              : "bg-[#1a1512] text-qyellow"
                                          }`}
                                        >
                                          {slot.start}–{slot.end}
                                        </span>
                                      ))}
                                    </div>
                                  </div>
                                ))}
                              </div>
                            )}
                          </div>
                        ) : null}
                      </article>
                    );
                  })}
                </div>
              </section>
            ) : null}

            <section className="rounded-[28px] border border-black/5 bg-[#fffdf9] p-6 shadow-[0_18px_50px_rgba(20,17,15,0.06)] sm:p-8">
              <SectionTitle
                eyebrow="Menü"
                title="Hizmetler"
                subtitle={
                  flags.show_prices
                    ? "Süre ve fiyat bilgisi"
                    : "Fiyatlar kapalı — isim ve süre gösterilir"
                }
              />
              {services.length === 0 ? (
                <p className="text-sm text-[#6b7280]">Henüz hizmet eklenmemiş.</p>
              ) : (
                <ul className="divide-y divide-black/[0.05]">
                  {services.map((svc) => (
                    <li
                      key={svc.id}
                      className="flex items-center justify-between gap-4 py-4 first:pt-0 last:pb-0"
                    >
                      <div>
                        <p className="text-base font-800 text-[#14110f]">
                          {svc.name}
                        </p>
                        <p className="mt-0.5 text-xs text-[#6b7280]">
                          {svc.duration_minutes} dakika
                        </p>
                      </div>
                      {flags.show_prices && svc.price != null ? (
                        <p className="whitespace-nowrap text-base font-800 text-[#b45309]">
                          {Number(svc.price).toLocaleString("tr-TR")} ₺
                        </p>
                      ) : (
                        <span className="text-xs font-700 uppercase tracking-wide text-[#9ca3af]">
                          Süre
                        </span>
                      )}
                    </li>
                  ))}
                </ul>
              )}
            </section>

            {flags.show_calendar && calendar?.days ? (
              <section className="rounded-[28px] border border-black/5 bg-[#fffdf9] p-6 shadow-[0_18px_50px_rgba(20,17,15,0.06)] sm:p-8">
                <SectionTitle
                  eyebrow="Takvim"
                  title="Salon doluluk"
                  subtitle="Yalnızca dolu / kapalı aralıklar. Kimlik bilgisi yok."
                />
                <div className="grid gap-3 sm:grid-cols-2">
                  {calendar.days.map((day) => (
                    <div
                      key={day.date}
                      className="rounded-2xl border border-black/[0.04] bg-white p-4"
                    >
                      <div className="flex items-baseline justify-between gap-2">
                        <p className="font-800 text-[#14110f]">{day.label}</p>
                        <span className="text-[11px] text-[#9ca3af]">
                          {day.date}
                        </span>
                      </div>
                      {(day.slots || []).length === 0 ? (
                        <p className="mt-3 text-xs text-[#6b7280]">Boş görünüyor</p>
                      ) : (
                        <div className="mt-3 flex flex-wrap gap-2">
                          {day.slots.map((slot, idx) => (
                            <span
                              key={`${day.date}-${idx}`}
                              className={`inline-flex rounded-full px-2.5 py-1 text-[11px] font-700 ${
                                slot.kind === "closed"
                                  ? "bg-gray-100 text-qgray"
                                  : "bg-[#fff7ed] text-[#b45309]"
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
          </div>

          {/* Right column sticky */}
          <div className="space-y-6 lg:sticky lg:top-6 lg:self-start">
            <section className="rounded-[28px] border border-black/5 bg-[#171411] p-6 text-white shadow-[0_24px_60px_rgba(0,0,0,0.25)] sm:p-7">
              <p className="text-[11px] font-700 uppercase tracking-[0.2em] text-qyellow">
                Bilgi
              </p>
              <h2 className="mt-2 text-xl font-800">Çalışma saatleri</h2>
              <p className="mt-3 text-3xl font-800 tracking-tight text-white">
                {hours}
              </p>
              {(salon.address || mapHref) && (
                <div className="mt-6 border-t border-white/10 pt-5">
                  <p className="text-[11px] font-700 uppercase tracking-[0.18em] text-white/50">
                    Adres
                  </p>
                  {salon.address ? (
                    <p className="mt-2 text-sm leading-6 text-white/80">
                      {salon.address}
                    </p>
                  ) : null}
                  {mapHref ? (
                    <a
                      href={mapHref}
                      target="_blank"
                      rel="noreferrer"
                      className="mt-4 inline-flex h-11 items-center rounded-full bg-white px-5 text-sm font-800 text-[#14110f]"
                    >
                      Haritada aç
                    </a>
                  ) : null}
                </div>
              )}
            </section>

            <section
              id="randevu"
              className="rounded-[28px] border border-[#fde68a] bg-gradient-to-br from-[#fffbeb] to-[#fff7ed] p-6 sm:p-7"
            >
              <SectionTitle
                eyebrow="Randevu"
                title="Hemen bağlan"
                subtitle={
                  book.app_hint ||
                  "Seyfibaba uygulamasından müşteri girişi ile randevu alın."
                }
              />
              {book.join_code ? (
                <div className="mb-5 rounded-2xl border border-[#fcd34d]/60 bg-white/70 px-4 py-4 text-center">
                  <p className="text-[11px] font-700 uppercase tracking-[0.18em] text-[#92400e]">
                    Berber kodu
                  </p>
                  <p className="mt-1 font-mono text-3xl font-800 tracking-[0.2em] text-[#14110f]">
                    {book.join_code}
                  </p>
                </div>
              ) : null}
              <div className="flex flex-wrap gap-3">
                {playStoreUrl ? (
                  <a
                    href={playStoreUrl}
                    target="_blank"
                    rel="noreferrer"
                    aria-label="Google Play"
                  >
                    <GooglePlay />
                  </a>
                ) : null}
                {appStoreUrl ? (
                  <a
                    href={appStoreUrl}
                    target="_blank"
                    rel="noreferrer"
                    aria-label="App Store"
                  >
                    <AppleStore />
                  </a>
                ) : null}
              </div>
            </section>

            {data.qr_url ? (
              <section className="rounded-[28px] border border-black/5 bg-white p-6 text-center shadow-[0_18px_50px_rgba(20,17,15,0.06)]">
                <p className="text-[11px] font-700 uppercase tracking-[0.2em] text-[#b45309]">
                  QR
                </p>
                <h2 className="mt-1 text-lg font-800 text-[#14110f]">
                  Dükkana as
                </h2>
                <p className="mt-1 text-xs text-[#6b7280]">
                  Müşteri telefonuyla okutsun
                </p>
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img
                  src={data.qr_url}
                  alt="Salon site QR"
                  className="mx-auto mt-4 h-44 w-44 rounded-2xl border border-black/5 bg-white p-3"
                />
              </section>
            ) : null}
          </div>
        </div>
      </div>
    </div>
  );
}
