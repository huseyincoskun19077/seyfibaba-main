"use client";

import Link from "next/link";
import { legalPath } from "@/config/legalDocuments";

/**
 * @param {{ items: Array<{slug?: string, slugs?: string[], links?: Array<{slug: string, label: string}>, label: string, linkLabel?: string, href?: string, key?: string}>, values: Record<string, boolean>, onChange: (key: string, checked: boolean) => void, required?: boolean, className?: string, title?: string }} props
 */
export default function LegalConsentCheckboxes({
  items,
  values,
  onChange,
  required = true,
  className = "",
  title = "Yasal Onaylar",
  compact = false,
}) {
  return (
    <div className={className} role="group" aria-label="Yasal onay kutuları">
      {title ? (
        <p className="text-sm font-semibold text-[#1D1D1D] mb-2.5">{title}</p>
      ) : null}
      <div className={compact ? "space-y-2" : "space-y-3"}>
        {items.map((item, index) => {
          const key = item.key || item.slug || item.href || `consent-${index}`;
          const checked = !!values[key];
          const href = item.href || (item.slug ? legalPath(item.slug) : "#");
          const linkLabel = item.linkLabel || item.label;
          const bundledLinks = Array.isArray(item.links) ? item.links : [];
          const isItemRequired =
            item.required !== undefined ? item.required : required;

          return (
            <div
              key={key}
              className={`rounded-xl border transition-colors cursor-pointer ${
                compact ? "p-2.5" : "p-3.5"
              } ${
                checked
                  ? "border-green-500 bg-green-50/80"
                  : "border-gray-200 bg-white"
              }`}
              onClick={() => onChange(key, !checked)}
              onKeyDown={(event) => {
                if (event.key === "Enter" || event.key === " ") {
                  event.preventDefault();
                  onChange(key, !checked);
                }
              }}
              role="checkbox"
              aria-checked={checked}
              tabIndex={0}
            >
              <div className="flex items-start gap-3">
                <span
                  aria-hidden="true"
                  className={`mt-0.5 flex h-[22px] w-[22px] shrink-0 items-center justify-center rounded-md border-2 transition-colors ${
                    checked
                      ? "border-green-600 bg-green-600 text-white"
                      : "border-gray-400 bg-white text-transparent"
                  }`}
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    className="h-4 w-4"
                  >
                    <path
                      fillRule="evenodd"
                      d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                      clipRule="evenodd"
                    />
                  </svg>
                </span>
                <span
                  className="text-sm text-[#1D1D1D] leading-relaxed"
                  onClick={(event) => event.stopPropagation()}
                >
                  {isItemRequired ? (
                    <span className="text-qred mr-1 font-semibold" aria-hidden="true">
                      *
                    </span>
                  ) : null}
                  {bundledLinks.length > 0 ? (
                    <>
                      {bundledLinks.map((link, linkIndex) => (
                        <span key={link.slug}>
                          {linkIndex > 0
                            ? linkIndex === bundledLinks.length - 1
                              ? " ve "
                              : ", "
                            : null}
                          <Link
                            href={legalPath(link.slug)}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-green-700 underline underline-offset-2 hover:text-qyellow font-semibold"
                            onClick={(event) => event.stopPropagation()}
                          >
                            {link.label}
                          </Link>
                        </span>
                      ))}
                      {item.label}
                    </>
                  ) : item.linkLabel ? (
                    <>
                      <Link
                        href={href}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-green-700 underline underline-offset-2 hover:text-qyellow font-semibold"
                        onClick={(event) => event.stopPropagation()}
                      >
                        {item.linkLabel}
                      </Link>
                      {item.label}
                    </>
                  ) : (
                    <Link
                      href={href}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-green-700 underline underline-offset-2 hover:text-qyellow font-semibold"
                      onClick={(event) => event.stopPropagation()}
                    >
                      {linkLabel}
                    </Link>
                  )}
                </span>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}

export function allRequiredChecked(items, values, requiredDefault = true) {
  return items.every((item, index) => {
    const isRequired =
      item.required !== undefined ? item.required : requiredDefault;
    if (!isRequired) return true;
    const key = item.key || item.slug || item.href || `consent-${index}`;
    return !!values[key];
  });
}
