"use client";

import React, { useEffect, useState } from "react";
import Link from "next/link";
import { toast } from "react-toastify";
import DateFormat from "../../../../utils/DateFormat";
import ServeLangItem from "../../../Helpers/ServeLangItem";
import CurrencyConvert from "../../../Shared/CurrencyConvert";
import auth from "../../../../utils/auth";
import { useSubmitReturnTrackingApiMutation } from "../../../../redux/features/auth/apiSlice";

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
  0: {
    label: "İade talebi alındı — satıcı/yönetici inceliyor",
    color: "bg-yellow-100 text-yellow-800",
  },
  1: {
    label: "Satıcı onayladı — ürünü iade adresine kargolayın",
    color: "bg-blue-100 text-blue-800",
  },
  2: {
    label: "İade onaylandı — kargo talimatını uygulayın",
    color: "bg-indigo-100 text-indigo-800",
  },
  3: {
    label: "İade ürünü satıcıya / depoya ulaştı",
    color: "bg-purple-100 text-purple-800",
  },
  4: {
    label: "İade tamamlandı — para iadesi yapıldı",
    color: "bg-green-100 text-green-800",
  },
  5: { label: "İade talebi reddedildi", color: "bg-red-100 text-red-800" },
  6: { label: "İade talebi reddedildi", color: "bg-red-100 text-red-800" },
  7: { label: "İptal Edildi", color: "bg-gray-100 text-gray-800" },
};

const PAYER_LABEL = {
  seller: "Satıcı karşılar",
  buyer: "Alıcı karşılar",
};

function getRejectionNote(item) {
  const candidates = [
    item?.admin_note,
    item?.admin_response,
    item?.rejected_reason,
    item?.seller_note,
    item?.vendor_response,
  ];
  for (const value of candidates) {
    const text = String(value || "").trim();
    if (text && text !== "Cancelled by customer") return text;
  }
  return "";
}

function buyerStatusLabel(item) {
  const status = Number(item?.status);
  if (
    (status === 1 || status === 2) &&
    String(item?.buyer_return_tracking_number || "").trim()
  ) {
    return `İade kargoda — takip: ${item.buyer_return_tracking_number}`;
  }
  return STATUS_MAP[status]?.label || "Bilinmiyor";
}

function canSubmitTracking(item) {
  const status = Number(item?.status);
  return status === 1 || status === 2;
}

function StatCard({ label, value, valueClassName = "text-qblack", tone = "bg-gray-50" }) {
  return (
    <div className={`${tone} rounded-lg p-4 text-center`}>
      <p className={`text-2xl font-bold ${valueClassName}`}>{value ?? 0}</p>
      <p className="text-sm text-qgray">{label}</p>
    </div>
  );
}

function TrackingForm({ item, onSaved }) {
  const [carrier, setCarrier] = useState(item.buyer_return_carrier || "");
  const [tracking, setTracking] = useState(item.buyer_return_tracking_number || "");
  const [url, setUrl] = useState(item.buyer_return_tracking_url || "");
  const [submitTracking, { isLoading }] = useSubmitReturnTrackingApiMutation();

  const handleSubmit = async (e) => {
    e.preventDefault();
    const token = auth()?.access_token;
    if (!token) {
      toast.error("Oturum bulunamadı");
      return;
    }
    try {
      const res = await submitTracking({
        token,
        id: item.id,
        buyer_return_carrier: carrier || undefined,
        buyer_return_tracking_number: tracking,
        buyer_return_tracking_url: url || undefined,
      }).unwrap();
      toast.success(res?.message || "Takip bilgisi kaydedildi");
      onSaved?.({
        buyer_return_carrier: carrier,
        buyer_return_tracking_number: tracking,
        buyer_return_tracking_url: url,
        buyer_shipped_at: new Date().toISOString(),
      });
    } catch (err) {
      const msg =
        err?.data?.message ||
        err?.data?.errors?.buyer_return_tracking_number?.[0] ||
        "Takip bilgisi kaydedilemedi";
      toast.error(msg);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="mt-2 space-y-2 rounded-md border border-indigo-100 bg-indigo-50/60 p-2 text-left">
      <p className="text-xs font-semibold text-indigo-900">İade kargo takip bilgisi</p>
      <input
        type="text"
        className="w-full rounded border border-indigo-200 px-2 py-1 text-xs"
        placeholder="Kargo firması (örn. Yurtiçi)"
        value={carrier}
        onChange={(e) => setCarrier(e.target.value)}
      />
      <input
        type="text"
        className="w-full rounded border border-indigo-200 px-2 py-1 text-xs"
        placeholder="Takip numarası *"
        value={tracking}
        onChange={(e) => setTracking(e.target.value)}
        required
      />
      <input
        type="url"
        className="w-full rounded border border-indigo-200 px-2 py-1 text-xs"
        placeholder="Takip linki (opsiyonel)"
        value={url}
        onChange={(e) => setUrl(e.target.value)}
      />
      <button
        type="submit"
        disabled={isLoading || !tracking.trim()}
        className="w-full rounded bg-indigo-600 px-2 py-1.5 text-xs font-semibold text-white disabled:opacity-50"
      >
        {isLoading ? "Kaydediliyor..." : "Takip bilgisini kaydet"}
      </button>
    </form>
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
  const [localReturns, setLocalReturns] = useState(returns || []);

  useEffect(() => {
    setDraftFilters(filters);
  }, [filters]);

  useEffect(() => {
    setLocalReturns(returns || []);
  }, [returns]);

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

  const refreshAfterTracking = (itemId, patch) => {
    setLocalReturns((prev) =>
      (prev || []).map((row) => (row.id === itemId ? { ...row, ...patch } : row))
    );
    onFiltersChange((prev) => ({ ...prev }));
  };

  return (
    <div className="return-requests-wrapper w-full">
      <div className="grid grid-cols-2 xl:grid-cols-6 gap-4 mb-6">
        <StatCard label="Toplam" value={stats?.total} />
        <StatCard label="Bekleyen" value={stats?.pending} tone="bg-yellow-50" valueClassName="text-yellow-700" />
        <StatCard label="Onaylı" value={stats?.approved} tone="bg-blue-50" valueClassName="text-blue-700" />
        <StatCard label="İade Edildi" value={stats?.refunded} tone="bg-green-50" valueClassName="text-green-700" />
        <StatCard label="Reddedilen" value={stats?.rejected} tone="bg-red-50" valueClassName="text-red-700" />
        <StatCard label="İptal" value={stats?.cancelled} tone="bg-gray-100" />
      </div>

      <div className="bg-white border rounded-lg p-4 mb-6">
        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
          <input
            type="text"
            value={draftFilters?.search || ""}
            onChange={(e) => updateFilter("search", e.target.value)}
            placeholder="Sipariş / ürün ara"
            className="border rounded px-3 py-2 text-sm"
          />
          <select
            value={draftFilters?.status || "all"}
            onChange={(e) => updateFilter("status", e.target.value)}
            className="border rounded px-3 py-2 text-sm"
          >
            {STATUS_OPTIONS.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>
          <select
            value={draftFilters?.reason || ""}
            onChange={(e) => updateFilter("reason", e.target.value)}
            className="border rounded px-3 py-2 text-sm"
          >
            {REASON_OPTIONS.map((option) => (
              <option key={option.value || "all"} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>
          <input
            type="date"
            value={draftFilters?.dateFrom || ""}
            onChange={(e) => updateFilter("dateFrom", e.target.value)}
            className="border rounded px-3 py-2 text-sm"
          />
          <input
            type="date"
            value={draftFilters?.dateTo || ""}
            onChange={(e) => updateFilter("dateTo", e.target.value)}
            className="border rounded px-3 py-2 text-sm"
          />
        </div>
        <div className="flex flex-wrap gap-2 mt-3">
          <button
            type="button"
            onClick={applyFilters}
            className="px-4 py-2 rounded bg-qyellow text-qblack text-sm font-semibold"
          >
            Filtreleri uygula
          </button>
          {hasActiveFilters ? (
            <button
              type="button"
              onClick={clearFilters}
              className="px-4 py-2 rounded border text-sm font-semibold text-qgray"
            >
              Temizle
            </button>
          ) : null}
        </div>
      </div>

      <div className="relative w-full overflow-x-auto sm:rounded-lg">
        <table className="w-full text-sm text-left text-gray-500">
          <tbody>
            <tr className="text-base text-qgray whitespace-nowrap px-2 border-b default-border-bottom">
              <td className="py-4 block whitespace-nowrap text-center">Sipariş</td>
              <td className="py-4 whitespace-nowrap text-center">Ürün / Kargo</td>
              <td className="py-4 whitespace-nowrap text-center">Neden</td>
              <td className="py-4 whitespace-nowrap text-center">Tarih</td>
              <td className="py-4 whitespace-nowrap text-center">Tutar</td>
              <td className="py-4 whitespace-nowrap text-center">Durum</td>
              <td className="py-4 whitespace-nowrap text-center">
                {ServeLangItem()?.Action}
              </td>
            </tr>
            {localReturns.length > 0 ? (
              localReturns.map((item) => {
                const statusInfo = STATUS_MAP[item.status] || {
                  label: "Bilinmiyor",
                  color: "bg-gray-100 text-gray-800",
                };
                const isRejected = Number(item.status) === 5 || Number(item.status) === 6;
                const rejectionNote = isRejected ? getRejectionNote(item) : "";
                const statusLabel = buyerStatusLabel(item);

                return (
                  <tr key={item.id} className="bg-white border-b hover:bg-gray-50 align-top">
                    <td className="text-center py-4">
                      <span className="text-lg text-qgray font-medium">
                        #{item.order?.order_id || item.order_id}
                      </span>
                    </td>
                    <td className="text-center py-4 px-2">
                      <div className="flex flex-col items-stretch gap-1 text-left max-w-[280px] mx-auto">
                        <span className="text-sm text-qblack">
                          {item.order_product?.product?.name ||
                            item.order_product?.product_name ||
                            "-"}
                        </span>
                        <span className="text-xs text-qgray">Adet: {item.qty || 0}</span>
                        {item.return_address ? (
                          <span className="text-xs text-qgray whitespace-pre-line border-t pt-1 mt-1">
                            <strong className="text-qblack">İade adresi:</strong>
                            {"\n"}
                            {item.return_address}
                          </span>
                        ) : null}
                        {item.return_shipping_payer ? (
                          <span className="text-xs text-qgray">
                            <strong className="text-qblack">Kargo ücreti:</strong>{" "}
                            {PAYER_LABEL[item.return_shipping_payer] ||
                              item.return_shipping_payer}
                          </span>
                        ) : null}
                        {item.return_carrier_name ? (
                          <span className="text-xs text-qgray">
                            <strong className="text-qblack">Kargo:</strong>{" "}
                            {item.return_carrier_name}
                          </span>
                        ) : null}
                        {item.return_cargo_code ? (
                          <span className="text-xs text-qgray">
                            <strong className="text-qblack">İade kodu:</strong>{" "}
                            <span className="notranslate font-mono">
                              {item.return_cargo_code}
                            </span>
                          </span>
                        ) : null}
                        {item.return_shipping_instructions ? (
                          <span className="text-xs text-qgray whitespace-pre-line">
                            {item.return_shipping_instructions}
                          </span>
                        ) : null}
                        {canSubmitTracking(item) ? (
                          <TrackingForm
                            item={item}
                            onSaved={(patch) => refreshAfterTracking(item.id, patch)}
                          />
                        ) : null}
                        {item.buyer_return_tracking_number && !canSubmitTracking(item) ? (
                          <span className="text-xs text-indigo-700">
                            Takip: {item.buyer_return_tracking_number}
                          </span>
                        ) : null}
                      </div>
                    </td>
                    <td className="text-center py-4 px-2">
                      <div className="flex flex-col items-center gap-1">
                        <span className="text-sm text-qblack capitalize">
                          {(item.reason || "-").replaceAll("_", " ")}
                        </span>
                        <span className="text-xs text-qgray line-clamp-2 max-w-[220px] mx-auto">
                          {item.details || "-"}
                        </span>
                        {rejectionNote ? (
                          <span className="mt-1 max-w-[240px] rounded-md bg-red-50 px-2 py-1 text-left text-xs text-red-700">
                            <span className="font-semibold">Ret gerekçesi: </span>
                            {rejectionNote}
                          </span>
                        ) : null}
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
                        {statusLabel}
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
      {pagination?.last_page > 1 ? (
        <p className="text-xs text-qgray mt-3 text-center">
          Sayfa {pagination.current_page} / {pagination.last_page}
        </p>
      ) : null}
    </div>
  );
}
