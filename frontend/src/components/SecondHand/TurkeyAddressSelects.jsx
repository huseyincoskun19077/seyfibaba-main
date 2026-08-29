"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import { TURKEY_CITIES } from "@/data/turkey-cities";

/** Kaynak: https://github.com/hsndmr/turkiye-city-county-district-neighborhood (data.json) */
const REMOTE_DATA_URL =
  "https://raw.githubusercontent.com/hsndmr/turkiye-city-county-district-neighborhood/main/data.json";
const LOCAL_DATA_URL = "/data/tr-turkiye-address.json";

function buildFallbackTree() {
  return TURKEY_CITIES.map((c) => ({
    name: c.label,
    counties: c.districts.map((d) => ({ name: d, districts: [] })),
  }));
}

const FALLBACK_TREE = buildFallbackTree();

let cachedTree = null;
let loadPromise = null;

function treeHasMahalleData(tree) {
  return tree.some((c) =>
    (c.counties || []).some((co) =>
      (co.districts || []).some((d) => (d.neighborhoods || []).length > 0)
    )
  );
}

function loadTree() {
  if (cachedTree) return Promise.resolve(cachedTree);
  if (loadPromise) return loadPromise;
  loadPromise = (async () => {
    try {
      let res = await fetch(LOCAL_DATA_URL, { cache: "force-cache" });
      if (!res.ok) {
        res = await fetch(REMOTE_DATA_URL, { cache: "force-cache" });
      }
      if (res.ok) {
        const json = await res.json();
        const tree = Array.isArray(json) ? json : json?.cities || json?.data || [];
        if (tree.length > 0) {
          cachedTree = tree;
          return tree;
        }
      }
    } catch {
      // Yerel dosya veya uzak kaynak yoksa gömülü il/ilçe listesine düş
    }
    cachedTree = FALLBACK_TREE;
    return cachedTree;
  })();
  return loadPromise;
}

const emptyValue = () => ({ province: "", district: "", locality: "", neighborhood: "" });

/** Veri ağacında mahalle ebeveyni (API'de locality alanında saklanır). */
const MAHKEY_SEP = "\x1e";

/**
 * İl → ilçe → mahalle (mahalle, veri setindeki alt bölge + mahalle çiftiyle eşlenir; ayrı "bölge" seçimi yok)
 * @param {{ value?: { province?: string; district?: string; locality?: string; neighborhood?: string }; onChange: (v: ReturnType<typeof emptyValue>) => void; disabled?: boolean; className?: string }} props
 */
export default function TurkeyAddressSelects({ value, onChange, disabled = false, className = "" }) {
  const v = value || emptyValue();
  const [tree, setTree] = useState([]);
  const [hasMahalleData, setHasMahalleData] = useState(false);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;
    loadTree()
      .then((t) => {
        if (!cancelled) {
          setTree(t || []);
          setHasMahalleData(treeHasMahalleData(t || []));
        }
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, []);

  const city = tree.find((c) => c.name === v.province) || null;
  const counties = city?.counties || [];
  const county = counties.find((x) => x.name === v.district) || null;

  const flatMahalle = useMemo(() => {
    const districts = county?.districts || [];
    const pairs = [];
    for (const d of districts) {
      for (const n of d.neighborhoods || []) {
        pairs.push({ localityName: d.name, neighborhoodName: n.name });
      }
    }
    const counts = pairs.reduce((acc, p) => {
      acc[p.neighborhoodName] = (acc[p.neighborhoodName] || 0) + 1;
      return acc;
    }, {});
    return pairs.map((p) => ({
      key: `${p.localityName}${MAHKEY_SEP}${p.neighborhoodName}`,
      label: counts[p.neighborhoodName] > 1 ? `${p.neighborhoodName} (${p.localityName})` : p.neighborhoodName,
      localityName: p.localityName,
      neighborhoodName: p.neighborhoodName,
    }));
  }, [county]);

  const mahalleSelectValue =
    v.locality && v.neighborhood ? `${v.locality}${MAHKEY_SEP}${v.neighborhood}` : "";

  const emit = useCallback(
    (next) => {
      onChange({ ...emptyValue(), ...next });
    },
    [onChange]
  );

  const wrap = "w-full h-11 px-3 border border-qgray-border rounded-md text-sm bg-white disabled:opacity-50";

  if (loading) {
    return <div className={`text-sm text-qgray ${className}`}>Adres listesi yükleniyor…</div>;
  }

  const legacyMahalleInList = mahalleSelectValue && flatMahalle.some((m) => m.key === mahalleSelectValue);
  const showMahalle = hasMahalleData || Boolean(v.neighborhood);

  return (
    <div className={`grid gap-3 ${showMahalle ? "md:grid-cols-3" : "md:grid-cols-2"} ${className}`}>
      <div>
        <label className="block text-xs text-qgray mb-1">İl</label>
        <select
          className={wrap}
          disabled={disabled}
          value={v.province}
          onChange={(e) => {
            const province = e.target.value;
            emit({ province, district: "", locality: "", neighborhood: "" });
          }}
        >
          <option value="">Seçin</option>
          {tree.map((c) => (
            <option key={c.name} value={c.name}>
              {c.name}
            </option>
          ))}
        </select>
      </div>
      <div>
        <label className="block text-xs text-qgray mb-1">İlçe</label>
        <select
          className={wrap}
          disabled={disabled || !v.province}
          value={v.district}
          onChange={(e) => {
            const district = e.target.value;
            emit({ province: v.province, district, locality: "", neighborhood: "" });
          }}
        >
          <option value="">Seçin</option>
          {counties.map((co) => (
            <option key={co.name} value={co.name}>
              {co.name}
            </option>
          ))}
        </select>
      </div>
      {showMahalle ? (
        <div>
          <label className="block text-xs text-qgray mb-1">Mahalle</label>
          <select
            className={wrap}
            disabled={disabled || !v.district}
            value={mahalleSelectValue}
            onChange={(e) => {
              const raw = e.target.value;
              if (!raw) {
                emit({ province: v.province, district: v.district, locality: "", neighborhood: "" });
                return;
              }
              const i = raw.indexOf(MAHKEY_SEP);
              if (i === -1) return;
              const locality = raw.slice(0, i);
              const neighborhood = raw.slice(i + MAHKEY_SEP.length);
              emit({ province: v.province, district: v.district, locality, neighborhood });
            }}
          >
            <option value="">Seçin</option>
            {mahalleSelectValue && !legacyMahalleInList && (
              <option value={mahalleSelectValue}>{v.neighborhood} (kayıtlı)</option>
            )}
            {flatMahalle.map((m) => (
              <option key={m.key} value={m.key}>
                {m.label}
              </option>
            ))}
          </select>
        </div>
      ) : null}
    </div>
  );
}
