"use client";

import Link from "next/link";
import { PROFILE_LEGAL_LINKS, legalPath } from "@/config/legalDocuments";

export default function LegalDocumentsTab() {
  return (
    <div className="w-full">
      <h2 className="text-[22px] font-semibold text-qblack dark:text-white mb-6">
        Yasal Belgeler
      </h2>
      <ul className="space-y-3">
        {PROFILE_LEGAL_LINKS.map((item) => (
          <li key={item.slug}>
            <Link
              href={legalPath(item.slug)}
              target="_blank"
              rel="noopener noreferrer"
              className="text-[15px] text-qgray dark:text-gray-300 hover:text-qblack dark:hover:text-white underline underline-offset-2"
            >
              {item.label}
            </Link>
          </li>
        ))}
      </ul>
    </div>
  );
}
