"use client";

import { useCallback, useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import apiRoutes from "@/appConfig/apiRoutes";
import TurkeyAddressSelects from "@/components/SecondHand/TurkeyAddressSelects";

export default function SecondHandListFilters({ initialQuery = {}, conditionOptions = {}, categories = [] }) {
  const router = useRouter();
  const [q, setQ] = useState("");
  const [condition, setCondition] = useState("");
  const [sort, setSort] = useState("");
  const [filterTr, setFilterTr] = useState({ province: "", district: "", locality: "", neighborhood: "" });
  const [categoryId, setCategoryId] = useState("");
  const [subCategoryId, setSubCategoryId] = useState("");
  const [childCategoryId, setChildCategoryId] = useState("");
  const [subOptions, setSubOptions] = useState([]);
  const [childOptions, setChildOptions] = useState([]);

  const syncFromQuery = useCallback((iq) => {
    setQ(iq.q != null ? String(iq.q) : "");
    setCondition(iq.condition != null ? String(iq.condition) : "");
    setSort(iq.sort != null ? String(iq.sort) : "");
    setFilterTr({
      province: iq.province != null ? String(iq.province) : "",
      district: iq.district != null ? String(iq.district) : "",
      locality: iq.locality != null ? String(iq.locality) : "",
      neighborhood: iq.neighborhood != null ? String(iq.neighborhood) : "",
    });
    setCategoryId(iq.category_id != null && String(iq.category_id) !== "" ? String(iq.category_id) : "");
    setSubCategoryId(iq.sub_category_id != null && String(iq.sub_category_id) !== "" ? String(iq.sub_category_id) : "");
    setChildCategoryId(iq.child_category_id != null && String(iq.child_category_id) !== "" ? String(iq.child_category_id) : "");
  }, []);

  useEffect(() => {
    syncFromQuery(initialQuery);
  }, [initialQuery, syncFromQuery]);

  const loadSubs = useCallback(async (catId) => {
    if (!catId) {
      setSubOptions([]);
      return;
    }
    try {
      const res = await fetch(`${apiRoutes.subcategoryByCategory}${catId}`);
      if (!res.ok) {
        setSubOptions([]);
        return;
      }
      const j = await res.json();
      setSubOptions(j.subCategories || []);
    } catch {
      setSubOptions([]);
    }
  }, []);

  const loadChildren = useCallback(async (subId) => {
    if (!subId) {
      setChildOptions([]);
      return;
    }
    try {
      const res = await fetch(`${apiRoutes.childcategoryBySubcategory}${subId}`);
      if (!res.ok) {
        setChildOptions([]);
        return;
      }
      const j = await res.json();
      setChildOptions(j.childCategories || []);
    } catch {
      setChildOptions([]);
    }
  }, []);

  useEffect(() => {
    loadSubs(categoryId);
  }, [categoryId, loadSubs]);

  useEffect(() => {
    loadChildren(subCategoryId);
  }, [subCategoryId, loadChildren]);

  const applyFilters = () => {
    const params = new URLSearchParams();
    if (q.trim()) params.set("q", q.trim());
    if (condition) params.set("condition", condition);
    if (sort) params.set("sort", sort);
    if (filterTr.province?.trim()) params.set("province", filterTr.province.trim());
    if (filterTr.district?.trim()) params.set("district", filterTr.district.trim());
    if (filterTr.locality?.trim()) params.set("locality", filterTr.locality.trim());
    if (filterTr.neighborhood?.trim()) params.set("neighborhood", filterTr.neighborhood.trim());
    if (categoryId) params.set("category_id", categoryId);
    if (subCategoryId) params.set("sub_category_id", subCategoryId);
    if (childCategoryId) params.set("child_category_id", childCategoryId);
    const qs = params.toString();
    router.push(qs ? `/ikinci-el?${qs}` : "/ikinci-el");
  };

  const resetFilters = () => {
    setQ("");
    setCondition("");
    setSort("");
    setFilterTr({ province: "", district: "", locality: "", neighborhood: "" });
    setCategoryId("");
    setSubCategoryId("");
    setChildCategoryId("");
    setSubOptions([]);
    setChildOptions([]);
    router.push("/ikinci-el");
  };

  return (
    <div className="rounded-xl border border-qgray-border bg-white p-4 shadow-sm lg:p-5">
      <h2 className="text-sm font-700 text-qblack uppercase tracking-wide mb-4 pb-3 border-b border-qgray-border">
        Filtreler
      </h2>
      <div className="flex flex-col gap-4">
        <div>
          <label className="block text-xs text-qgray mb-1">Sıralama</label>
          <select
            value={sort}
            onChange={(e) => setSort(e.target.value)}
            className="w-full h-11 px-3 border border-qgray-border rounded-md text-sm bg-white"
          >
            <option value="">Öne çıkanlar</option>
            <option value="new">Yeni düştü</option>
          </select>
        </div>
        <div>
          <label className="block text-xs text-qgray mb-1">Arama</label>
          <input
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder="Başlık veya açıklama"
            className="w-full h-11 px-3 border border-qgray-border rounded-md text-sm"
          />
        </div>
        <div>
          <label className="block text-xs text-qgray mb-1">Ürün durumu</label>
          <select
            value={condition}
            onChange={(e) => setCondition(e.target.value)}
            className="w-full h-11 px-3 border border-qgray-border rounded-md text-sm bg-white"
          >
            <option value="">Tümü</option>
            {Object.entries(conditionOptions).map(([key, label]) => (
              <option key={key} value={key}>
                {label}
              </option>
            ))}
          </select>
        </div>
        <div>
          <label className="block text-xs text-qgray mb-1">Ana kategori</label>
          <select
            value={categoryId}
            onChange={(e) => {
              setCategoryId(e.target.value);
              setSubCategoryId("");
              setChildCategoryId("");
            }}
            className="w-full h-11 px-3 border border-qgray-border rounded-md text-sm bg-white"
          >
            <option value="">Tümü</option>
            {categories.map((c) => (
              <option key={c.id} value={String(c.id)}>
                {c.name}
              </option>
            ))}
          </select>
        </div>
        {subOptions.length > 0 && (
          <div>
            <label className="block text-xs text-qgray mb-1">Alt kategori</label>
            <select
              value={subCategoryId}
              onChange={(e) => {
                setSubCategoryId(e.target.value);
                setChildCategoryId("");
              }}
              className="w-full h-11 px-3 border border-qgray-border rounded-md text-sm bg-white"
            >
              <option value="">Tümü</option>
              {subOptions.map((s) => (
                <option key={s.id} value={String(s.id)}>
                  {s.name}
                </option>
              ))}
            </select>
          </div>
        )}
        {childOptions.length > 0 && (
          <div>
            <label className="block text-xs text-qgray mb-1">Alt-alt kategori</label>
            <select
              value={childCategoryId}
              onChange={(e) => setChildCategoryId(e.target.value)}
              className="w-full h-11 px-3 border border-qgray-border rounded-md text-sm bg-white"
            >
              <option value="">Tümü</option>
              {childOptions.map((ch) => (
                <option key={ch.id} value={String(ch.id)}>
                  {ch.name}
                </option>
              ))}
            </select>
          </div>
        )}
        <div>
          <label className="block text-xs text-qgray mb-1">Konum (isteğe bağlı)</label>
          <TurkeyAddressSelects value={filterTr} onChange={setFilterTr} />
        </div>
        <div className="flex flex-col gap-2 pt-1">
          <button
            type="button"
            onClick={applyFilters}
            className="h-11 w-full rounded-md bg-qyellow text-qblack text-sm font-600 hover:opacity-90"
          >
            Filtrele
          </button>
          <button
            type="button"
            onClick={resetFilters}
            className="h-11 w-full rounded-md border border-qgray-border text-sm text-qgray hover:bg-gray-50"
          >
            Sıfırla
          </button>
        </div>
      </div>
    </div>
  );
}
