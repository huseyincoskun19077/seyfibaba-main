"use client";

import Link from "next/link";
import { useCallback, useEffect, useState } from "react";
import apiRoutes from "@/appConfig/apiRoutes";
import auth from "@/utils/auth";

const OPTIONS = [
  { value: "", label: "Üst kural (boş)" },
  { value: "1", label: "1 — tek çekim" },
  { value: "2", label: "2 taksit" },
  { value: "3", label: "3 taksit" },
  { value: "6", label: "6 taksit" },
  { value: "9", label: "9 taksit" },
  { value: "12", label: "12 taksit" },
];

function Table({ title, hint, rows, onSave, savingKey }) {
  if (!rows?.length) return null;

  return (
    <section className="mb-8">
      <h2 className="mb-1 text-lg font-semibold text-[#04334a]">{title}</h2>
      <p className="mb-3 text-sm text-[#5c6b76]">{hint}</p>
      <div className="overflow-x-auto rounded-2xl border border-[#e3eaee] bg-white">
        <table className="w-full min-w-[640px] text-left text-sm">
          <thead className="bg-[#f4f7f8] text-[#04334a]">
            <tr>
              <th className="px-4 py-3 font-semibold">Kategori</th>
              <th className="px-4 py-3 font-semibold">Bağlı olduğu</th>
              <th className="px-4 py-3 font-semibold">Taksit</th>
              <th className="px-4 py-3 font-semibold" />
            </tr>
          </thead>
          <tbody>
            {rows.map((row) => {
              const key = `${row.level}-${row.id}`;
              return (
                <tr key={key} className="border-t border-[#e3eaee]">
                  <td className="px-4 py-3 font-medium text-[#04334a]">{row.name}</td>
                  <td className="px-4 py-3 text-[#5c6b76]">{row.parent || "—"}</td>
                  <td className="px-4 py-3">
                    <select
                      className="h-10 rounded-md border border-[#d7e0e6] bg-white px-2 text-[#04334a]"
                      value={row.max_installment == null ? "" : String(row.max_installment)}
                      onChange={(event) => onSave(row, event.target.value, false)}
                      aria-label={`${row.name} taksit`}
                    >
                      {OPTIONS.map((option) => (
                        <option key={option.value || "empty"} value={option.value}>
                          {row.level === "category" && option.value === ""
                            ? "Kural yok — tek çekim"
                            : option.label}
                        </option>
                      ))}
                    </select>
                  </td>
                  <td className="px-4 py-3">
                    <button
                      type="button"
                      disabled={savingKey === key}
                      onClick={() => onSave(row, null, true)}
                      className="h-10 rounded-md bg-[#04334a] px-3 text-sm text-white disabled:opacity-60"
                    >
                      {savingKey === key ? "Kaydediliyor" : "Kaydet"}
                    </button>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
    </section>
  );
}

export default function IyzicoInstallmentEditor() {
  const [status, setStatus] = useState("loading");
  const [data, setData] = useState(null);
  const [drafts, setDrafts] = useState({});
  const [savingKey, setSavingKey] = useState("");
  const [notice, setNotice] = useState("");

  const load = useCallback(async () => {
    const session = auth();
    if (!session?.access_token) {
      setStatus("login");
      return;
    }

    const response = await fetch(apiRoutes.iyzicoInstallments, {
      headers: { Authorization: `Bearer ${session.access_token}`, Accept: "application/json" },
    });

    if (response.status === 401) {
      setStatus("login");
      return;
    }
    if (response.status === 403) {
      setStatus("denied");
      return;
    }
    if (!response.ok) {
      setStatus("error");
      return;
    }

    const json = await response.json();
    setData(json);
    setStatus("ready");
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  const valueOf = (row) => {
    const key = `${row.level}-${row.id}`;
    if (Object.prototype.hasOwnProperty.call(drafts, key)) {
      return drafts[key];
    }
    return row.max_installment == null ? "" : String(row.max_installment);
  };

  const save = async (row, nextValue, commit) => {
    const key = `${row.level}-${row.id}`;
    const chosen = nextValue == null ? valueOf(row) : nextValue;
    setDrafts((prev) => ({ ...prev, [key]: chosen }));
    if (!commit) return;

    const session = auth();
    setSavingKey(key);
    setNotice("");
    try {
      const response = await fetch(apiRoutes.iyzicoInstallments, {
        method: "PUT",
        headers: {
          Authorization: `Bearer ${session.access_token}`,
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          level: row.level,
          id: row.id,
          max_installment: chosen === "" ? 0 : Number(chosen),
        }),
      });
      const json = await response.json().catch(() => ({}));
      if (!response.ok) {
        setNotice(json.message || "Kayıt olmadı.");
        return;
      }
      setData((prev) => {
        if (!prev) return prev;
        const patch = (list) =>
          (list || []).map((item) =>
            item.level === row.level && item.id === row.id
              ? { ...item, max_installment: json.max_installment }
              : item
          );
        return {
          ...prev,
          categories: patch(prev.categories),
          sub_categories: patch(prev.sub_categories),
          child_categories: patch(prev.child_categories),
        };
      });
      setDrafts((prev) => {
        const copy = { ...prev };
        delete copy[key];
        return copy;
      });
      setNotice(`${json.name} kaydedildi.`);
    } finally {
      setSavingKey("");
    }
  };

  if (status === "loading") {
    return <p className="px-4 py-16 text-center text-[#5c6b76]">Yükleniyor…</p>;
  }

  if (status === "login") {
    return (
      <div className="mx-auto max-w-lg px-4 py-16 text-center">
        <h1 className="text-2xl font-semibold text-[#04334a]">Kategori taksitleri</h1>
        <p className="mt-3 text-sm text-[#5c6b76]">
          Bu sayfa yalnızca iyzicotestekibi@gmail.com hesabıyla açılır.
        </p>
        <Link
          href="/login?next=/iyzico-taksit"
          className="mt-6 inline-flex h-11 items-center rounded-md bg-[#04334a] px-4 text-sm text-white"
        >
          Giriş yap
        </Link>
      </div>
    );
  }

  if (status === "denied") {
    return (
      <div className="mx-auto max-w-lg px-4 py-16 text-center">
        <h1 className="text-2xl font-semibold text-[#04334a]">Bu sayfa kapalı</h1>
        <p className="mt-3 text-sm text-[#5c6b76]">
          Giriş yaptığınız hesap bu listeyi göremez ve değiştiremez.
        </p>
      </div>
    );
  }

  if (status === "error" || !data) {
    return <p className="px-4 py-16 text-center text-[#5c6b76]">Liste alınamadı.</p>;
  }

  const withDraft = (rows) =>
    (rows || []).map((row) => ({
      ...row,
      max_installment: valueOf(row) === "" ? null : Number(valueOf(row)),
    }));

  return (
    <div className="bg-[#f4f7f8] px-4 py-8">
      <div className="mx-auto max-w-5xl">
        <h1 className="text-2xl font-semibold text-[#04334a]">Kategori taksitleri</h1>
        <p className="mt-2 mb-6 max-w-2xl text-sm text-[#5c6b76]">
          Boş bırakılan alt kategori bir üstün kuralını kullanır. Ana kategoride kural yoksa ödeme tek çekimdir.
          Sepette en düşük taksit sayısı geçerlidir. Değiştirdikten sonra Kaydet’e basın.
        </p>
        {notice && <p className="mb-4 text-sm text-[#04334a]">{notice}</p>}
        <Table
          title="Ana kategoriler"
          hint="Kural yoksa bu kategorideki ürünler tek çekim olur."
          rows={withDraft(data.categories)}
          onSave={save}
          savingKey={savingKey}
        />
        <Table
          title="Alt kategoriler"
          hint="Boşsa ana kategorinin taksiti kullanılır."
          rows={withDraft(data.sub_categories)}
          onSave={save}
          savingKey={savingKey}
        />
        <Table
          title="Altın kategoriler"
          hint="Boşsa önce alt kategoriye, o da boşsa ana kategoriye bakılır."
          rows={withDraft(data.child_categories)}
          onSave={save}
          savingKey={savingKey}
        />
      </div>
    </div>
  );
}
