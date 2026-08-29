"use client";

import Link from "next/link";

export default function ConsentModal({ open, title, body, href, onClose }) {
  if (!open) return null;

  return (
    <div className="fixed inset-0 z-[1200] flex items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true">
      <div className="w-full max-w-xl rounded-xl bg-white shadow-2xl border border-gray-200">
        <div className="flex items-center justify-between border-b border-gray-100 px-5 py-4">
          <h3 className="text-base font-700 text-qblack">{title}</h3>
          <button type="button" onClick={onClose} className="text-qgray hover:text-qblack text-xl leading-none">
            ×
          </button>
        </div>
        <div className="px-5 py-4">
          <p className="text-sm text-qgray leading-relaxed">{body}</p>
          <div className="mt-4 flex items-center justify-end gap-2">
            <Link
              href={href}
              target="_blank"
              className="h-9 inline-flex items-center rounded-md border border-gray-200 px-3 text-xs font-700 text-qblack hover:bg-gray-50"
            >
              Metnin tamamını aç
            </Link>
            <button
              type="button"
              onClick={onClose}
              className="h-9 inline-flex items-center rounded-md bg-qblack px-3 text-xs font-700 text-white"
            >
              Kapat
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
