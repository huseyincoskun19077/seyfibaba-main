"use client";

import React, { useEffect, useState } from "react";
import Link from "next/link";
import DateFormat from "../../../../utils/DateFormat";
import ServeLangItem from "../../../Helpers/ServeLangItem";
import CurrencyConvert from "../../../Shared/CurrencyConvert";

const STATUS_OPTIONS = [
  { value: "all", label: "Tüm Talepler" },
  { value: "0", label: "Beklemede" },
  { value: "1", label: "Satıcı Onayladı" },
  { value: "2", label: "Yönetici Onayladı" },
  { value: "3", label: "Ürün Teslim Alındı" },
  { value: "4", label: "İade Edildi" },
  { value: "5", label: "Satıcı Reddetti" },
  { value: "6", label: "Yönetici Reddetti" },
  { value: "7", label: "İptal Edildi" },
];

const REASON_OPTIONS = [
  { value: "", label: "Tüm Nedenler" },
  { value: "defective", label: "Kusurlu Ürün" },
  { value: "damaged_in_shipping", label: "Kargoda Hasar Gördü" },
  { value: "wrong_item", label: "Yanlış Ürün" },
  { value: "not_as_described", label: "Açıklamayla Uyuşmuyor" },
  { value: "changed_mind", label: "Vazgeçtim" },
  { value: "other", label: "Diğer" },
];

const STATUS_MAP = {
  0: { label: "Beklemede", color: "bg-yellow-100 text-yellow-800" },
  1: { label: "Satıcı Onayladı", color: "bg-blue-100 text-blue-800" },
  2: { label: "Yönetici Onayladı", color: "bg-indigo-100 text-indigo-800" },
  3: { label: "Ürün Teslim Alındı", color: "bg-purple-100 text-purple-800" },
  4: { label: "İade Edildi", color: "bg-green-100 text-green-800" },
  5: { label: "Satıcı Reddetti", color: "bg-red-100 text-red-800" },
  6: { label: "Yönetici Reddetti", color: "bg-red-100 text-red-800" },
  7: { label: "İptal Edildi", color: "bg-gray-100 text-gray-800" },
};

function StatCard({ label, value, valueClassName = "text-qblack", tone = "bg-gray-50" }) {
  return (
    <div className={`${tone} rounded-lg p-4 text-center`}>
      <p className={`text-2xl font-bold ${valueClassName}`}>{value ?? 0}</p>
      <p className="text-sm text-qgray">{label}</p>
    </div>
  );
}

export default function ReturnRequestsTab({
  returns = [],
  stats = {},
  pagination,
  filters,
  onFiltersChange,
}) {
  const [draftFilters, setDraftFilters] = useState(filters);

  useEffect(() => {
    setDraftFilters(filters);
  }, [filters]);

  const updateFilter = (key, value) => {
    setDraftFilters((prev) => ({
      ...prev,
      [key]: value,
    }));
  };

  const applyFilters = () => {
    onFiltersChange((prev) => ({
      ...prev,
      ...draftFilters,
    }));
  };

  const clearFilters = () => {
    const nextFilters = {
      ...filters,
      status: "all",
      search: "",
      reason: "",
      dateFrom: "",
      dateTo: "",
    };

    setDraftFilters(nextFilters);
    onFiltersChange((prev) => ({
      ...prev,
      ...nextFilters,
    }));
  };

  const hasActiveFilters = Boolean(
    draftFilters?.status !== "all" ||
      draftFilters?.search ||
      draftFilters?.reason ||
      draftFilters?.dateFrom ||
      draftFilters?.dateTo
  );

  return (
    <div className="return-requests-wrapper w-full">
      <div className="grid grid-cols-2 xl:grid-cols-6 gap-4 mb-6">
        <StatCard label="Toplam" value={stats?.total} />
        <StatCard
          label="Beklemede"
          value={stats?.pending}
          valueClassName="text-yellow-700"
          tone="bg-yellow-50"
        />
        <StatCard
          label="Onaylandı"
          value={stats?.approved}
          valueClassName="text-blue-700"
          tone="bg-blue-50"
        />
        <StatCard
          label="İade Edildi"
          value={stats?.refunded}
          valueClassName="text-green-700"
          tone="bg-green-50"
        />
        <StatCard
          label="Reddedildi"
          value={stats?.rejected}
          valueClassName="text-red-700"
          tone="bg-red-50"
        />
        <StatCard
          label="İptal"
          value={stats?.cancelled}
          valueClassName="text-gray-700"
          tone="bg-gray-100"
        />
      </div>

      <div className="border border-qgray-border rounded-lg p-4 mb-6">
        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
          <div>
            <label className="block text-sm text-qgray mb-2">Ara</label>
            <input
              type="text"
              value={draftFilters?.search || ""}
              onChange={(event) => updateFilter("search", event.target.value)}
              placeholder="Sipariş no, ürün, neden"
              className="w-full h-[50px] border border-qgray-border px-4 text-sm focus:outline-none"
            />
          </div>

          <div>
            <label className="block text-sm text-qgray mb-2">Durum</label>
            <select
              className="w-full h-[50px] border border-qgray-border px-4 text-sm bg-white focus:outline-none"
              value={draftFilters?.status || "all"}
              onChange={(event) => updateFilter("status", event.target.value)}
            >
              {STATUS_OPTIONS.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
          </div>

          <div>
            <label className="block text-sm text-qgray mb-2">Neden</label>
            <select
              className="w-full h-[50px] border border-qgray-border px-4 text-sm bg-white focus:outline-none"
              value={draftFilters?.reason || ""}
              onChange={(event) => updateFilter("reason", event.target.value)}
            >
              {REASON_OPTIONS.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
          </div>

          <div>
            <label className="block text-sm text-qgray mb-2">Başlangıç</label>
            <input
              type="date"
              value={draftFilters?.dateFrom || ""}
              onChange={(event) => updateFilter("dateFrom", event.target.value)}
              className="w-full h-[50px] border border-qgray-border px-4 text-sm focus:outline-none"
            />
          </div>

          <div>
            <label className="block text-sm text-qgray mb-2">Bitiş</label>
            <input
              type="date"
              value={draftFilters?.dateTo || ""}
              onChange={(event) => updateFilter("dateTo", event.target.value)}
              className="w-full h-[50px] border border-qgray-border px-4 text-sm focus:outline-none"
            />
          </div>
        </div>

        <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mt-4">
          <div className="text-sm text-qgray">
            {pagination?.total
              ? `${pagination.from}-${pagination.to} / ${pagination.total} talep gösteriliyor`
              : "İade talebi bulunamadı"}
          </div>

          <div className="flex items-center gap-4">
            {hasActiveFilters ? (
              <button
                type="button"
                onClick={clearFilters}
                className="text-sm text-qblack underline text-left sm:text-right"
              >
                Filtreleri temizle
              </button>
            ) : null}

            <button
              type="button"
              onClick={applyFilters}
              className="h-[42px] px-5 bg-qyellow text-qblack text-sm font-semibold"
            >
              Filtreleri uygula
            </button>
          </div>
        </div>
      </div>

      <div className="relative w-full overflow-x-auto sm:rounded-lg">
        <table className="w-full text-sm text-left text-gray-500">
          <tbody>
            <tr className="text-base text-qgray whitespace-nowrap px-2 border-b default-border-bottom">
              <td className="py-4 block whitespace-nowrap text-center">Sipariş</td>
              <td className="py-4 whitespace-nowrap text-center">Ürün</td>
              <td className="py-4 whitespace-nowrap text-center">Neden</td>
              <td className="py-4 whitespace-nowrap text-center">Tarih</td>
              <td className="py-4 whitespace-nowrap text-center">Tutar</td>
              <td className="py-4 whitespace-nowrap text-center">Durum</td>
              <td className="py-4 whitespace-nowrap text-center">
                {ServeLangItem()?.Action}
              </td>
            </tr>
            {returns.length > 0 ? (
              returns.map((item) => {
                const statusInfo = STATUS_MAP[item.status] || {
                  label: "Bilinmiyor",
                  color: "bg-gray-100 text-gray-800",
                };

                return (
                  <tr key={item.id} className="bg-white border-b hover:bg-gray-50">
                    <td className="text-center py-4">
                      <span className="text-lg text-qgray font-medium">
                        #{item.order?.order_id || item.order_id}
                      </span>
                    </td>
                    <td className="text-center py-4 px-2">
                      <div className="flex flex-col">
                        <span className="text-sm text-qblack whitespace-nowrap">
                          {item.order_product?.product?.name || item.order_product?.product_name || "-"}
                        </span>
                        <span className="text-xs text-qgray">
                          Adet: {item.qty || 0}
                        </span>
                      </div>
                    </td>
                    <td className="text-center py-4 px-2">
                      <div className="flex flex-col">
                        <span className="text-sm text-qblack capitalize">
                          {(item.reason || "-").replaceAll("_", " ")}
                        </span>
                        <span className="text-xs text-qgray line-clamp-2 max-w-[220px] mx-auto">
                          {item.details || item.rejected_reason || "-"}
                        </span>
                      </div>
                    </td>
                    <td className="text-center py-4 px-2">
                      <span className="text-base text-qgray whitespace-nowrap">
                        {DateFormat(item.created_at)}
                      </span>
                    </td>
                    <td className="text-center py-4 px-2">
                      <span className="text-base text-qblack whitespace-nowrap">
                        {Number(item.refund_amount) > 0 ? (
                          <CurrencyConvert price={item.refund_amount} />
                        ) : (
                          "-"
                        )}
                      </span>
                    </td>
                    <td className="text-center py-4 px-2">
                      <span
                        className={`inline-block px-3 py-1 rounded-full text-xs font-semibold ${statusInfo.color}`}
                      >
                        {statusInfo.label}
                      </span>
                    </td>
                    <td className="py-4 flex justify-center">
                      <Link href={`/order/${item.order?.order_id || item.order_id}`}>
                        <div className="w-[116px] h-[46px] bg-qyellow text-qblack font-bold flex justify-center items-center cursor-pointer">
                          <span>{ServeLangItem()?.View_Details}</span>
                        </div>
                      </Link>
                    </td>
                  </tr>
                );
              })
            ) : (
              <tr>
                <td colSpan="7" className="text-center py-10 text-qgray">
                  Filtrelerinize uygun iade talebi bulunamadı.
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}
