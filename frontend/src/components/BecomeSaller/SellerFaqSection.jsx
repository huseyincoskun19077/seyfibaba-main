"use client";

import { useState } from "react";
import { sellerFaqIntro, sellerFaqSections } from "@/data/sellerFaq";

export default function SellerFaqSection({ compact = false }) {
  const [openKey, setOpenKey] = useState("0-0");

  const toggle = (key) => {
    setOpenKey((prev) => (prev === key ? null : key));
  };

  return (
    <div className={`w-full ${compact ? "mt-8" : "mt-16 mb-12"}`}>
      <div className="text-center mb-8">
        <h2 className="text-2xl md:text-3xl font-bold text-qblack mb-3">
          Satıcılar için sıkça sorulan sorular
        </h2>
        <p className="text-qgraytwo max-w-2xl mx-auto text-sm md:text-base leading-relaxed">
          {sellerFaqIntro}
        </p>
      </div>

      <div className="max-w-3xl mx-auto space-y-6">
        {sellerFaqSections.map((section, si) => (
          <div key={section.title}>
            <h3 className="text-sm font-bold uppercase tracking-wide text-qyellow mb-3">
              {section.title}
            </h3>
            <div className="rounded-xl border border-gray-100 overflow-hidden bg-white shadow-sm">
              {section.items.map((item, qi) => {
                const key = `${si}-${qi}`;
                const isOpen = openKey === key;
                return (
                  <div key={key} className="border-b border-gray-50 last:border-b-0">
                    <button
                      type="button"
                      onClick={() => toggle(key)}
                      className="w-full text-left px-5 py-4 flex justify-between gap-4 items-start hover:bg-gray-50 transition-colors"
                      aria-expanded={isOpen}
                    >
                      <span className="font-semibold text-qblack text-sm md:text-base">
                        {item.q}
                      </span>
                      <span className="text-qyellow text-lg leading-none shrink-0">
                        {isOpen ? "−" : "+"}
                      </span>
                    </button>
                    {isOpen && (
                      <div className="px-5 pb-4 text-sm text-qgraytwo leading-relaxed">
                        {item.a}
                      </div>
                    )}
                  </div>
                );
              })}
            </div>
          </div>
        ))}
      </div>

      <p className="text-center text-sm text-qgraytwo mt-8">
        Daha fazla soru için{" "}
        <a href="tel:08503035073" className="text-qyellow font-semibold hover:underline">
          0850 303 5073
        </a>{" "}
        veya{" "}
        <a href="mailto:info@seyfibaba.com" className="text-qyellow font-semibold hover:underline">
          info@seyfibaba.com
        </a>
      </p>
    </div>
  );
}
