"use client";

import { useCallback, useEffect, useState } from "react";
import Link from "next/link";
import apiRoutes from "@/appConfig/apiRoutes";

const STORAGE_KEY = "kt_customer_segment";

const FALLBACK_SEGMENTS = [
  { slug: "kadin-kuaforu", name: "Kadın Kuaförü", short_name: "Kadın", is_primary_home: true },
  { slug: "erkek-kuaforu-berber", name: "Erkek Kuaförü ve Berber", short_name: "Erkek / Berber", is_primary_home: true },
  { slug: "guzellik-salonu", name: "Güzellik Salonu", short_name: "Güzellik", is_primary_home: true },
  { slug: "tirnak-manikur", name: "Tırnak ve Manikür", short_name: "Tırnak", is_primary_home: true },
];

export function getStoredCustomerSegment() {
  if (typeof window === "undefined") return null;
  try {
    return localStorage.getItem(STORAGE_KEY) || null;
  } catch {
    return null;
  }
}

export function setStoredCustomerSegment(slug) {
  if (typeof window === "undefined") return;
  try {
    if (!slug) localStorage.removeItem(STORAGE_KEY);
    else localStorage.setItem(STORAGE_KEY, slug);
  } catch {
    /* ignore */
  }
}

/**
 * Misafir + giriş yapan kullanıcı: müşteri alanı seçici.
 * Giriş zorunlu değil; seçim localStorage'da oturum boyunca kalır.
 */
export default function CustomerSegmentPicker({
  businessTypeKey = null,
  businessTypeKeys = null,
  onChange = null,
}) {
  const [segments, setSegments] = useState(FALLBACK_SEGMENTS);
  const [selected, setSelected] = useState(null);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      try {
        const res = await fetch(`${apiRoutes.customerSegments}?guest_home=1`, {
          credentials: "omit",
        });
        const data = await res.json();
        if (!cancelled && data?.success && Array.isArray(data.segments) && data.segments.length) {
          setSegments(data.segments);
        }
      } catch {
        /* fallback */
      }
    })();
    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    const stored = getStoredCustomerSegment();
    if (stored) {
      setSelected(stored);
      onChange?.(stored);
      return;
    }
    const keys = [
      ...(Array.isArray(businessTypeKeys) ? businessTypeKeys : []),
      ...(businessTypeKey ? [businessTypeKey] : []),
    ].filter(Boolean);
    // barber → male_hairdresser ile aynı alan
    const normalized = keys.map((k) => (k === "barber" ? "male_hairdresser" : k));
    if (normalized.length && segments.length) {
      const match = segments.find((s) => normalized.includes(s.business_type_key));
      if (match?.slug) {
        setSelected(match.slug);
        setStoredCustomerSegment(match.slug);
        onChange?.(match.slug);
      }
    }
  }, [businessTypeKey, businessTypeKeys, segments, onChange]);

  const select = useCallback(
    (slug) => {
      setSelected(slug);
      setStoredCustomerSegment(slug);
      onChange?.(slug);
    },
    [onChange]
  );

  const clear = useCallback(() => {
    setSelected(null);
    setStoredCustomerSegment(null);
    onChange?.(null);
  }, [onChange]);

  const primary = segments.filter((s) => s.is_primary_home !== false).slice(0, 4);
  const cards = primary.length >= 4 ? primary : segments.slice(0, 4);

  return (
    <section className="w-full px-3 md:px-0">
      <div className="container-x mx-auto">
        <div className="flex flex-wrap items-end justify-between gap-2 mb-3">
          <div>
            <h2 className="text-lg md:text-xl font-700 text-qblack">Salonunuza göre gezinin</h2>
            <p className="text-sm text-qgray mt-0.5">
              Giriş gerekmez. İstediğiniz alanı seçin; üstten değiştirebilirsiniz.
            </p>
          </div>
          <div className="flex items-center gap-2">
            {selected ? (
              <button
                type="button"
                onClick={clear}
                className="text-sm text-qgray hover:text-qblack underline"
              >
                Tüm Ürünler
              </button>
            ) : null}
          </div>
        </div>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-2 md:gap-3">
          {cards.map((s) => {
            const active = selected === s.slug;
            return (
              <button
                key={s.slug}
                type="button"
                onClick={() => select(s.slug)}
                className={`text-left rounded-2xl border px-3 py-4 md:px-4 md:py-5 transition ${
                  active
                    ? "border-qblack bg-qblack text-white shadow-md"
                    : "border-[#E8E8E8] bg-white hover:border-qblack/40 text-qblack"
                }`}
              >
                <span className="block text-sm md:text-base font-700 leading-snug">
                  {s.short_name || s.name}
                </span>
                <span
                  className={`block text-[11px] md:text-xs mt-1 leading-snug ${
                    active ? "text-white/80" : "text-qgray"
                  }`}
                >
                  {s.name}
                </span>
              </button>
            );
          })}
        </div>
        {selected ? (
          <div className="mt-3 flex flex-wrap gap-2 items-center text-sm">
            <span className="text-qgray">Seçili alan:</span>
            <Link
              href={`/products?segment=${encodeURIComponent(selected)}`}
              className="font-600 text-qblack underline"
            >
              Bu alandaki ürünleri gör
            </Link>
          </div>
        ) : null}
      </div>
    </section>
  );
}
