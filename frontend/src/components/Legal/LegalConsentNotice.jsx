"use client";

import { useState } from "react";
import LegalDocumentModal from "@/components/Legal/LegalDocumentModal";

/**
 * Otomatik kabul bildirimi — checkbox yok, belgeler popup ile okunabilir.
 */
export default function LegalConsentNotice({ notice, className = "" }) {
  const [modal, setModal] = useState({ open: false, slug: "", title: "" });
  const links = Array.isArray(notice?.links) ? notice.links : [];

  if (!notice) return null;

  return (
    <div
      className={`rounded-xl border border-[#04334a]/10 bg-[#F4F6F7] p-3.5 text-sm leading-relaxed text-[#04334a]/80 ${className}`}
    >
      {notice.prefix || null}
      {links.map((link, linkIndex) => (
        <span key={link.slug}>
          {linkIndex > 0
            ? linkIndex === links.length - 1
              ? " ve "
              : ", "
            : null}
          <button
            type="button"
            className="font-700 text-[#04334a] underline underline-offset-2 hover:text-qyellow"
            onClick={() =>
              setModal({ open: true, slug: link.slug, title: link.label || "" })
            }
          >
            {link.label}
          </button>
        </span>
      ))}
      {notice.suffix || notice.label || null}

      <LegalDocumentModal
        open={modal.open}
        slug={modal.slug}
        title={modal.title}
        onClose={() => setModal({ open: false, slug: "", title: "" })}
      />
    </div>
  );
}
