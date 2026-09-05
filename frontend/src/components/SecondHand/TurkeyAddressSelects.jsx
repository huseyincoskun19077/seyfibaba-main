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

/** İl/ilçe/mahalle isim karşılaştırması (büyük-küçük harf + Türkçe karakter). */
export function normalizeTrPlaceName(value = "") {
  return String(value || "")
    .trim()
    .toLocaleLowerCase("tr-TR")
    .replace(/ı/g, "i")
    .replace(/İ/g, "i")
    .replace(/ğ/g, "g")
    .replace(/ü/g, "u")
    .replace(/ş/g, "s")
    .replace(/ö/g, "o")
    .replace(/ç/g, "c")
    .replace(/\s+/g, " ")
    .replace(/\s*mah\.?$/i, "")
    .trim();
}

function titleTr(value = "") {
  const cleaned = String(value || "")
    .trim()
    .replace(/\s*MAH\.?$/i, "")
    .replace(/\s+/g, " ");
  if (!cleaned) return "";
  return cleaned
    .toLocaleLowerCase("tr-TR")
    .replace(/(^|[\s/-])(\S)/g, (_, p, c) => p + c.toLocaleUpperCase("tr-TR"));
}

function findByNormalizedName(list, name, getName = (item) => item?.name) {
  const target = normalizeTrPlaceName(name);
  if (!target || !Array.isArray(list)) return null;
  return (
    list.find((item) => normalizeTrPlaceName(getName(item)) === target) || null
  );
}

function treeHasMahalleData(tree) {
  return tree.some((c) =>
    (c.counties || []).some((co) =>
      (co.districts || []).some((d) => (d.neighborhoods || []).length > 0)
    )
  );
}

function resolveProvinceAndCounty(tree, province, district) {
  let city = findByNormalizedName(tree, province);
  let county = city
    ? findByNormalizedName(city.counties || [], district)
    : null;

  if (!county && district) {
    for (const c of tree) {
      const found = findByNormalizedName(c.counties || [], district);
      if (found) {
        city = c;
        county = found;
        break;
      }
    }
  }

  return { city, county };
}

function loadTree() {
  if (cachedTree) return Promise.resolve(cachedTree);
  if (loadPromise) return loadPromise;
  loadPromise = (async () => {
    try {
      let res = await fetch(REMOTE_DATA_URL, { cache: "force-cache" });
      if (!res.ok) {
        res = await fetch(LOCAL_DATA_URL, { cache: "force-cache" });
      }
      if (res.ok) {
        const json = await res.json();
        const tree = Array.isArray(json) ? json : json?.cities || json?.data || [];
        if (tree.length > 0 && treeHasMahalleData(tree)) {
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

const emptyValue = () => ({
  province: "",
  district: "",
  locality: "",
  neighborhood: "",
});

/** Veri ağacında mahalle ebeveyni (API'de locality alanında saklanır). */
const MAHKEY_SEP = "\x1e";

/**
 * İl → ilçe → mahalle
 * @param {{ value?: object; onChange: Function; disabled?: boolean; className?: string; onlyNeighborhood?: boolean }} props
 */
export default function TurkeyAddressSelects({
  value,
  onChange,
  disabled = false,
  className = "",
  onlyNeighborhood = false,
}) {
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

  const { city, county } = useMemo(
    () => resolveProvinceAndCounty(tree, v.province, v.district),
    [tree, v.province, v.district]
  );
  const counties = city?.counties || [];

  const flatMahalle = useMemo(() => {
    const districts = county?.districts || [];
    const pairs = [];
    for (const d of districts) {
      for (const n of d.neighborhoods || []) {
        pairs.push({ localityName: d.name, neighborhoodName: n.name });
      }
    }
    const counts = pairs.reduce((acc, p) => {
      const key = normalizeTrPlaceName(p.neighborhoodName);
      acc[key] = (acc[key] || 0) + 1;
      return acc;
    }, {});
    return pairs
      .map((p) => {
        const labelBase = titleTr(p.neighborhoodName);
        const needsLocality =
          counts[normalizeTrPlaceName(p.neighborhoodName)] > 1;
        return {
          key: `${p.localityName}${MAHKEY_SEP}${p.neighborhoodName}`,
          label: needsLocality
            ? `${labelBase} (${titleTr(p.localityName)})`
            : labelBase,
          localityName: p.localityName,
          neighborhoodName: p.neighborhoodName,
          displayName: labelBase,
        };
      })
      .sort((a, b) => a.label.localeCompare(b.label, "tr"));
  }, [county]);

  const mahalleSelectValue = useMemo(() => {
    if (!v.neighborhood) return "";
    if (v.locality) {
      const key = `${v.locality}${MAHKEY_SEP}${v.neighborhood}`;
      if (flatMahalle.some((m) => m.key === key)) return key;
    }
    const byName = flatMahalle.find(
      (m) =>
        normalizeTrPlaceName(m.neighborhoodName) ===
          normalizeTrPlaceName(v.neighborhood) ||
        normalizeTrPlaceName(m.displayName) ===
          normalizeTrPlaceName(v.neighborhood)
    );
    return byName?.key || "";
  }, [flatMahalle, v.locality, v.neighborhood]);

  const emit = useCallback(
    (next) => {
      onChange({ ...emptyValue(), ...next });
    },
    [onChange]
  );

  const wrap =
    "w-full h-11 px-3 border border-qgray-border rounded-md text-sm bg-white disabled:opacity-50";

  if (loading) {
    return (
      <div className={`text-sm text-qgray ${className}`}>
        Adres listesi yükleniyor…
      </div>
    );
  }

  const legacyMahalleInList =
    Boolean(v.neighborhood) &&
    !mahalleSelectValue &&
    flatMahalle.every(
      (m) =>
        normalizeTrPlaceName(m.neighborhoodName) !==
        normalizeTrPlaceName(v.neighborhood)
    );

  const districtReady = Boolean(v.district);
  const showMahalleDropdown = hasMahalleData;

  const mahalleSelect = showMahalleDropdown ? (
    <div>
      <label className="block text-xs text-qgray mb-1">Mahalle</label>
      <select
        className={wrap}
        disabled={disabled || !districtReady}
        value={mahalleSelectValue}
        onChange={(e) => {
          const raw = e.target.value;
          if (!raw) {
            emit({
              province: v.province,
              district: v.district,
              locality: "",
              neighborhood: "",
            });
            return;
          }
          const legacyPrefix = `legacy${MAHKEY_SEP}`;
          if (raw.startsWith(legacyPrefix)) {
            emit({
              province: v.province,
              district: v.district,
              locality: "",
              neighborhood: raw.slice(legacyPrefix.length),
            });
            return;
          }
          const i = raw.indexOf(MAHKEY_SEP);
          if (i === -1) return;
          const locality = raw.slice(0, i);
          const neighborhood = raw.slice(i + MAHKEY_SEP.length);
          const display = titleTr(neighborhood);
          emit({
            province: v.province,
            district: v.district,
            locality,
            neighborhood: display || neighborhood,
          });
        }}
      >
        <option value="">
          {!districtReady
            ? "Önce ilçe seçin"
            : flatMahalle.length
              ? "Mahalle seçin"
              : "Bu ilçe için mahalle bulunamadı"}
        </option>
        {v.neighborhood && legacyMahalleInList && (
          <option value={`legacy${MAHKEY_SEP}${v.neighborhood}`}>
            {titleTr(v.neighborhood)} (kayıtlı)
          </option>
        )}
        {flatMahalle.map((m) => (
          <option key={m.key} value={m.key}>
            {m.label}
          </option>
        ))}
      </select>
      {!districtReady && (
        <p className="text-xs text-qgraytwo mt-1">Önce il ve ilçe seçin.</p>
      )}
      {districtReady && flatMahalle.length === 0 && (
        <p className="text-xs text-qgraytwo mt-1">
          Bu ilçe için mahalle listesi yüklenemedi.
        </p>
      )}
    </div>
  ) : (
    <div>
      <label className="block text-xs text-qgray mb-1">Mahalle</label>
      <input
        className={wrap}
        disabled={disabled || !districtReady}
        placeholder={districtReady ? "Mahalle adı" : "Önce ilçe seçin"}
        value={v.neighborhood || ""}
        onChange={(e) =>
          emit({
            province: v.province,
            district: v.district,
            locality: "",
            neighborhood: e.target.value,
          })
        }
      />
    </div>
  );

  if (onlyNeighborhood) {
    return <div className={className}>{mahalleSelect}</div>;
  }

  return (
    <div
      className={`grid gap-3 ${
        showMahalleDropdown ? "md:grid-cols-3" : "md:grid-cols-2"
      } ${className}`}
    >
      <div>
        <label className="block text-xs text-qgray mb-1">İl</label>
        <select
          className={wrap}
          disabled={disabled}
          value={
            findByNormalizedName(tree, v.province)?.name || v.province || ""
          }
          onChange={(e) => {
            const province = e.target.value;
            emit({ province, district: "", locality: "", neighborhood: "" });
          }}
        >
          <option value="">Seçin</option>
          {tree.map((c) => (
            <option key={c.name} value={c.name}>
              {titleTr(c.name)}
            </option>
          ))}
        </select>
      </div>
      <div>
        <label className="block text-xs text-qgray mb-1">İlçe</label>
        <select
          className={wrap}
          disabled={disabled || !v.province}
          value={
            findByNormalizedName(counties, v.district)?.name ||
            v.district ||
            ""
          }
          onChange={(e) => {
            const district = e.target.value;
            emit({
              province: v.province,
              district,
              locality: "",
              neighborhood: "",
            });
          }}
        >
          <option value="">Seçin</option>
          {counties.map((co) => (
            <option key={co.name} value={co.name}>
              {titleTr(co.name)}
            </option>
          ))}
        </select>
      </div>
      {mahalleSelect}
    </div>
  );
}
