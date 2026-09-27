"use client";

import { useEffect, useState } from "react";
import { toast } from "react-toastify";
import apiRoutes from "@/appConfig/apiRoutes";
import auth from "@/utils/auth";

const TYPE_OPTIONS = [
  { value: "female_hairdresser", label: "Kadın kuaförü" },
  { value: "male_hairdresser", label: "Erkek kuaförü" },
  { value: "barber", label: "Berber" },
  { value: "beauty_salon", label: "Güzellik salonu" },
  { value: "nail_art", label: "Tırnak / Manikür" },
  { value: "other", label: "Diğer" },
];

const STATUS_OPTIONS = [
  { value: "own_shop", label: "Kendi salonum var" },
  { value: "opening_soon", label: "Yakında açacağım" },
  { value: "employed_in_salon", label: "Bir salonda çalışıyorum" },
  { value: "planning", label: "Planlıyorum" },
];

/**
 * Profil: İşletme türü (çoklu) + kişiselleştirme / geçmiş.
 */
export default function BusinessTypeTab({ profileInfo }) {
  const [types, setTypes] = useState([]);
  const [other, setOther] = useState("");
  const [status, setStatus] = useState("own_shop");
  const [enabled, setEnabled] = useState(true);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    const p = profileInfo?.personInfo || profileInfo || {};
    const multi = Array.isArray(p.business_types) ? p.business_types : [];
    if (multi.length) setTypes(multi);
    else if (p.business_type) setTypes([p.business_type]);
    setOther(p.business_type_other || "");
    setStatus(p.business_status || "own_shop");
    setEnabled(p.personalization_enabled !== false);
  }, [profileInfo]);

  const toggleType = (value) => {
    setTypes((prev) =>
      prev.includes(value) ? prev.filter((v) => v !== value) : [...prev, value]
    );
  };

  const token = typeof auth === "function" ? auth()?.access_token : auth?.access_token;

    const save = async () => {
    if (!types.length) {
      toast.error("En az bir işletme türü seçin.");
      return;
    }
    setSaving(true);
    try {
      const res = await fetch(apiRoutes.updateBuyerPersonalization, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({
          business_types: types,
          business_type: types[0],
          business_type_other: other,
          business_status: status,
          personalization_enabled: enabled,
        }),
      });
      const data = await res.json();
      if (!res.ok) {
        toast.error(data?.message || "Kaydedilemedi.");
        return;
      }
      toast.success(data?.notification || "Kaydedildi.");
    } catch {
      toast.error("Bağlantı hatası.");
    } finally {
      setSaving(false);
    }
  };

  const clearHistory = async () => {
    try {
      const res = await fetch(apiRoutes.clearBrowseHistory, {
        method: "POST",
        headers: { Authorization: `Bearer ${token}` },
      });
      const data = await res.json();
      toast.success(data?.notification || "Geçmiş silindi.");
    } catch {
      toast.error("Geçmiş silinemedi.");
    }
  };

  return (
    <div className="w-full bg-white p-5 md:p-8 rounded">
      <h2 className="text-xl font-700 text-qblack mb-2">İşletme türü</h2>
      <p className="text-sm text-qgray mb-4">
        Birden fazla seçebilirsiniz. Ana sayfa önerilerini etkiler; diğer alanlara erişiminizi kısıtlamaz.
      </p>
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-4">
        {TYPE_OPTIONS.map((opt) => (
          <label key={opt.value} className="flex items-center gap-2 border rounded-lg px-3 py-2 cursor-pointer">
            <input
              type="checkbox"
              checked={types.includes(opt.value)}
              onChange={() => toggleType(opt.value)}
            />
            <span>{opt.label}</span>
          </label>
        ))}
      </div>
      {types.includes("other") ? (
        <input
          className="w-full border rounded-lg px-3 py-2 mb-4"
          placeholder="Diğer işletme türünü yazın"
          value={other}
          onChange={(e) => setOther(e.target.value)}
        />
      ) : null}
      <label className="block text-sm font-600 mb-1">Salon durumu</label>
      <select
        className="w-full border rounded-lg px-3 py-2 mb-4"
        value={status}
        onChange={(e) => setStatus(e.target.value)}
      >
        {STATUS_OPTIONS.map((o) => (
          <option key={o.value} value={o.value}>
            {o.label}
          </option>
        ))}
      </select>
      <label className="flex items-center gap-2 mb-4">
        <input type="checkbox" checked={enabled} onChange={(e) => setEnabled(e.target.checked)} />
        Kişiselleştirilmiş önerileri göster
      </label>
      <div className="flex flex-wrap gap-2">
        <button
          type="button"
          disabled={saving}
          onClick={save}
          className="bg-qblack text-white px-4 py-2 rounded-lg text-sm"
        >
          {saving ? "Kaydediliyor…" : "Kaydet"}
        </button>
        <button type="button" onClick={clearHistory} className="border px-4 py-2 rounded-lg text-sm">
          Ürün görüntüleme geçmişini sil
        </button>
      </div>
    </div>
  );
}
