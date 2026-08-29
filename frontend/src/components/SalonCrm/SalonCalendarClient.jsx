"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useSelector } from "react-redux";
import { fetchSalonCalendar } from "@/api/salonCalendarPublic";
import GooglePlay from "@/components/Helpers/icons/GooglePlay";
import AppleStore from "@/components/Helpers/icons/AppleStore";

export default function SalonCalendarClient({ token }) {
  const [data, setData] = useState(null);
  const [missing, setMissing] = useState(false);
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const download = websiteSetup?.payload?.flashSaleSidebarBanner;
  const playStoreUrl = download?.play_store?.trim();
  const appStoreUrl = download?.app_store?.trim();

  useEffect(() => {
    let cancelled = false;
    const load = async () => {
      const next = await fetchSalonCalendar(token);
      if (cancelled) return;
      if (!next) {
        setMissing(true);
        return;
      }
      setMissing(false);
      setData(next);
    };
    load();
    const timer = setInterval(load, 45000);
    return () => {
      cancelled = true;
      clearInterval(timer);
    };
  }, [token]);

  if (missing) {
    return (
      <div className="max-w-lg mx-auto rounded-2xl border border-gray-200 bg-white p-6 text-center">
        <h1 className="text-xl font-700 text-qblack mb-2">Takvim bulunamadı</h1>
        <p className="text-sm text-qgray">
          Link geçersiz olabilir veya paylaşım kapatılmış olabilir.
        </p>
        <Link
          href="/"
          className="inline-flex mt-5 h-10 items-center rounded-xl bg-qyellow px-4 text-sm font-700 text-qblack"
        >
          Seyfibaba’ya dön
        </Link>
      </div>
    );
  }

  if (!data) {
    return (
      <div className="max-w-lg mx-auto text-center text-sm text-qgray py-16">
        Takvim yükleniyor…
      </div>
    );
  }

  const roleLabel = data.person_role === "staff" ? "Personel" : "Salon sahibi";

  return (
    <div className="max-w-xl mx-auto">
      <p className="text-xs font-700 uppercase tracking-wide text-[#6D28D9]">
        {data.salon_name}
      </p>
      <h1 className="mt-1 text-2xl font-800 text-qblack">
        {data.person_name}
      </h1>
      <p className="text-sm text-qgray mt-1">{roleLabel} takvimi · canlı</p>
      <p className="mt-4 text-sm leading-6 text-[#4b5563]">
        Yalnızca dolu saatler görünür. Kim randevu aldı, isim veya hizmet
        bilgisi paylaşılmaz. Randevu almak için Seyfibaba uygulamasını indirin.
      </p>

      <div className="mt-6 space-y-4">
        {(data.days || []).map((day) => (
          <div
            key={day.date}
            className="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm"
          >
            <div className="flex items-baseline justify-between gap-3">
              <h2 className="font-800 text-qblack">{day.label}</h2>
              <span className="text-xs text-qgray">{day.date}</span>
            </div>
            {(day.slots || []).length === 0 ? (
              <p className="mt-3 text-sm text-[#059669] font-600">
                Şu an listelenen dolu saat yok.
              </p>
            ) : (
              <ul className="mt-3 space-y-2">
                {day.slots.map((slot, i) => (
                  <li
                    key={`${slot.start}-${i}`}
                    className="flex items-center justify-between rounded-xl bg-[#F8F5FF] px-3 py-2 text-sm"
                  >
                    <span className="font-700 text-qblack">
                      {slot.start} – {slot.end}
                    </span>
                    <span className="text-xs font-700 text-[#6D28D9]">
                      {slot.kind === "closed" ? "Kapalı" : "Dolu"}
                    </span>
                  </li>
                ))}
              </ul>
            )}
          </div>
        ))}
      </div>

      <div className="mt-8 rounded-2xl bg-qyellow px-5 py-6">
        <p className="text-sm font-700 text-qblack leading-6">
          {data.book_message}
        </p>
        <div className="flex flex-wrap gap-3 mt-4">
          {playStoreUrl ? (
            <a href={playStoreUrl} target="_blank" rel="noreferrer">
              <GooglePlay />
            </a>
          ) : null}
          {appStoreUrl ? (
            <a href={appStoreUrl} target="_blank" rel="noreferrer">
              <AppleStore />
            </a>
          ) : null}
        </div>
      </div>
    </div>
  );
}
