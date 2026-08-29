"use client";

import { useEffect } from "react";

const KEY = "c2c_interest_v1";

function safeRead() {
  try {
    const raw = localStorage.getItem(KEY);
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
}

function safeWrite(value) {
  try {
    localStorage.setItem(KEY, JSON.stringify(value));
  } catch {
    // ignore
  }
}

/**
 * Listing detail sayfasında kullanıcı ilgisini takip eder (kategori bazlı).
 * @param {{ listing: any }} props
 */
export default function SecondHandTrackInterest({ listing }) {
  useEffect(() => {
    if (!listing) return;
    const cat = listing.child_category_id || listing.sub_category_id || listing.category_id;
    const cityId = listing.city_id || listing.city?.id;
    const condition = listing.condition;
    const price = Number(listing.price);
    if (!cat && !cityId && !condition && !Number.isFinite(price)) return;

    const cur = safeRead() || {};
    const next = { ...cur };

    // simple scores
    if (cat) {
      next.cats = next.cats || {};
      const k = String(cat);
      next.cats[k] = Number(next.cats[k] || 0) + 1;
    }
    if (cityId) {
      next.cities = next.cities || {};
      const k = String(cityId);
      next.cities[k] = Number(next.cities[k] || 0) + 1;
    }

    if (condition) {
      next.conditions = next.conditions || {};
      const k = String(condition);
      next.conditions[k] = Number(next.conditions[k] || 0) + 1;
    }

    if (Number.isFinite(price) && price > 0) {
      // son 10 fiyatı tut (basit band hesaplamak için)
      const arr = Array.isArray(next.prices) ? next.prices : [];
      const trimmed = arr.slice(-9);
      next.prices = [...trimmed, price];
    }

    next.updated_at = Date.now();
    safeWrite(next);
  }, [listing]);

  return null;
}

