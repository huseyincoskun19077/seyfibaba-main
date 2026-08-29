"use client";

import { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { getSecondHandListingSeoPath } from "@/api/secondHandPublic";
import apiRoutes from "@/appConfig/apiRoutes";
import { fetchSecondHandListings } from "@/api/secondHandPublic";

const KEY = "c2c_interest_v1";

function imageSrc(imageId) {
  if (!imageId) return null;
  return `${apiRoutes.secondHandListingImage}${imageId}`;
}

function formatTry(value) {
  const n = Number(value);
  if (Number.isNaN(n)) return "—";
  return new Intl.NumberFormat("tr-TR", {
    style: "currency",
    currency: "TRY",
    maximumFractionDigits: 0,
  }).format(n);
}

function readInterest() {
  try {
    const raw = localStorage.getItem(KEY);
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
}

function pickTopKey(map) {
  if (!map) return null;
  let bestK = null;
  let bestV = -Infinity;
  for (const [k, v] of Object.entries(map)) {
    const n = Number(v || 0);
    if (n > bestV) {
      bestV = n;
      bestK = k;
    }
  }
  return bestK;
}

function avg(nums) {
  const arr = (nums || []).map((x) => Number(x)).filter((n) => Number.isFinite(n) && n > 0);
  if (arr.length === 0) return null;
  return arr.reduce((s, n) => s + n, 0) / arr.length;
}

/**
 * "Senin için" blok: kullanıcıya göre öneri (kategori bazlı).
 * @param {{ enabled?: boolean, conditionOptions?: Record<string,string> }} props
 */
export default function SecondHandForYouBlock({ enabled = true, conditionOptions = {} }) {
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(false);
  const [resetNonce, setResetNonce] = useState(0);

  const pref = useMemo(() => {
    if (!enabled) return null;
    if (typeof window === "undefined") return null;
    const data = readInterest();
    const cat = pickTopKey(data?.cats);
    const cityId = pickTopKey(data?.cities);
    const condition = pickTopKey(data?.conditions);
    const priceAvg = avg(data?.prices);
    return { cat, cityId, condition, priceAvg };
  }, [enabled, resetNonce]);

  useEffect(() => {
    if (!enabled) return;
    if (!pref?.cat && !pref?.cityId) return;
    let cancelled = false;
    setLoading(true);
    (async () => {
      try {
        const query = { sort: "new", page: 1 };
        // Önce child_category_id dene; data gelmezse sub/category fallback yapacağız.
        if (pref.cat) query.child_category_id = pref.cat;
        if (pref.cityId) query.city_id = pref.cityId;
        if (pref.condition) query.condition = pref.condition;
        if (pref.priceAvg && Number.isFinite(pref.priceAvg)) {
          query.min_price = Math.max(0, Math.round(pref.priceAvg * 0.7));
          query.max_price = Math.max(0, Math.round(pref.priceAvg * 1.3));
        }
        let data = await fetchSecondHandListings(query);
        let next = data?.listings?.data || [];
        if (next.length === 0 && pref.cat) {
          // fallback: sub -> category
          data = await fetchSecondHandListings({
            sort: "new",
            page: 1,
            sub_category_id: pref.cat,
            city_id: pref.cityId || undefined,
            condition: pref.condition || undefined,
            min_price: query.min_price,
            max_price: query.max_price,
          });
          next = data?.listings?.data || [];
        }
        if (next.length === 0 && pref.cat) {
          data = await fetchSecondHandListings({
            sort: "new",
            page: 1,
            category_id: pref.cat,
            city_id: pref.cityId || undefined,
            condition: pref.condition || undefined,
            min_price: query.min_price,
            max_price: query.max_price,
          });
          next = data?.listings?.data || [];
        }
        if (next.length === 0 && (pref.condition || pref.priceAvg)) {
          // fallback: sadece condition/price band ile dene
          data = await fetchSecondHandListings({
            sort: "new",
            page: 1,
            condition: pref.condition || undefined,
            min_price: query.min_price,
            max_price: query.max_price,
          });
          next = data?.listings?.data || [];
        }
        if (!cancelled) setItems(next.slice(0, 6));
      } catch {
        if (!cancelled) setItems([]);
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [enabled, pref?.cat, pref?.cityId, pref?.condition, pref?.priceAvg]);

  if (!enabled) return null;
  if (!pref?.cat && !pref?.cityId && !pref?.condition && !pref?.priceAvg) return null;
  if (!loading && (!items || items.length === 0)) return null;

  return (
    <div className="mb-6">
      <div className="flex items-center justify-between mb-3">
        <h3 className="text-sm font-800 text-qblack">Senin için</h3>
        <div className="flex items-center gap-3">
          <button
            type="button"
            onClick={() => {
              try {
                localStorage.removeItem(KEY);
              } catch {
                // ignore
              }
              setItems([]);
              setResetNonce((n) => n + 1);
            }}
            className="text-xs text-qgray underline hover:text-qblack"
          >
            Sıfırla
          </button>
          <Link href="/ikinci-el?sort=new" className="text-xs text-qgray underline hover:text-qblack">
            Yeni düşenler
          </Link>
        </div>
      </div>
      {loading ? (
        <div className="text-xs text-qgray">Yükleniyor…</div>
      ) : (
        <div className="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-5">
          {items.map((item) => {
            const thumb = item.images?.length ? imageSrc(item.images[0].id) : null;
            const conditionLabel = conditionOptions[item.condition] || item.condition;
            return (
              <Link
                key={item.id}
                href={`/ikinci-el/${getSecondHandListingSeoPath(item)}`}
                className="group border border-gray-200 rounded-2xl overflow-hidden bg-white hover:shadow-lg transition-shadow ring-1 ring-black/[0.02]"
              >
                <div className="aspect-[4/3] bg-qgray-border relative">
                  {thumb ? (
                    // eslint-disable-next-line @next/next/no-img-element
                    <img src={thumb} alt="" className="w-full h-full object-cover group-hover:scale-[1.02] transition-transform" />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center text-qgray text-sm">Görsel yok</div>
                  )}
                </div>
                <div className="p-4">
                  <h2 className="text-qblack font-600 text-sm line-clamp-2 min-h-[40px] mb-2">{item.title}</h2>
                  <div className="flex flex-wrap gap-2 text-xs text-qgray mb-2">
                    {item.city?.name && <span>{item.city.name}</span>}
                    <span className="text-qblack font-500">{conditionLabel}</span>
                  </div>
                  <div className="text-lg font-bold text-qblack">{formatTry(item.price)}</div>
                </div>
              </Link>
            );
          })}
        </div>
      )}
    </div>
  );
}

