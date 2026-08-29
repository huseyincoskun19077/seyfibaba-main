"use client";

import { useState } from "react";
import Link from "next/link";
import { toast } from "react-toastify";
import auth from "@/utils/auth";
import { useSecondHandReportCreateMutation } from "@/redux/features/secondHand/apiSlice";
import { marketplaceProfileUrl } from "@/utils/secondHandSite";

const REASONS = [
  { value: "spam", label: "Spam" },
  { value: "scam", label: "Dolandırıcılık" },
  { value: "harassment", label: "Taciz" },
  { value: "illegal", label: "Yasadışı içerik" },
  { value: "other", label: "Diğer" },
];

/**
 * @param {{ listingId: number|string }} props
 */
export default function SecondHandReportListing({ listingId }) {
  const [open, setOpen] = useState(false);
  const [reason, setReason] = useState("spam");
  const [details, setDetails] = useState("");
  const [submitReport, { isLoading }] = useSecondHandReportCreateMutation();

  const session = auth();

  if (!session) {
    return null;
  }

  const submit = async () => {
    try {
      await submitReport({
        subject_type: "listing",
        subject_id: Number(listingId),
        reason,
        details: details.trim() || undefined,
      }).unwrap();
      toast.success("Raporunuz alındı. Teşekkürler.");
      setDetails("");
      setOpen(false);
    } catch (err) {
      const status = err?.status;
      const msg = err?.data?.message || "Rapor gönderilemedi.";
      if (status === 409) {
        toast.warn(msg);
      } else {
        toast.error(msg);
      }
    }
  };

  return (
    <div className="mt-6 border-t border-qgray-border pt-6">
      {!open ? (
        <button
          type="button"
          onClick={() => setOpen(true)}
          className="text-sm text-qgray underline hover:text-qblack"
        >
          Bu ilanı şikayet et
        </button>
      ) : (
        <div className="p-4 border border-qgray-border rounded-lg bg-white max-w-md">
          <p className="text-xs text-qgray mb-3">
            İkinci el doğrulamanız yoksa rapor gönderilemez.{" "}
            <Link href={marketplaceProfileUrl("second-hand")} className="underline">
              Doğrulama
            </Link>
          </p>
          <label className="block text-xs text-qgray mb-1">Sebep</label>
          <select
            value={reason}
            onChange={(e) => setReason(e.target.value)}
            className="w-full h-10 px-3 border border-qgray-border rounded-md text-sm mb-3"
          >
            {REASONS.map((r) => (
              <option key={r.value} value={r.value}>
                {r.label}
              </option>
            ))}
          </select>
          <label className="block text-xs text-qgray mb-1">Açıklama (isteğe bağlı)</label>
          <textarea
            value={details}
            onChange={(e) => setDetails(e.target.value)}
            rows={3}
            maxLength={2000}
            className="w-full px-3 py-2 border border-qgray-border rounded-md text-sm mb-3"
          />
          <div className="flex gap-2">
            <button
              type="button"
              disabled={isLoading}
              onClick={submit}
              className="h-9 px-4 bg-qblack text-white rounded-md text-sm disabled:opacity-50"
            >
              {isLoading ? "Gönderiliyor…" : "Raporu gönder"}
            </button>
            <button type="button" onClick={() => setOpen(false)} className="h-9 px-4 border border-qgray-border rounded-md text-sm">
              Vazgeç
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
