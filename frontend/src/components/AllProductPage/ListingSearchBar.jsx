"use client";
import { useEffect, useState } from "react";
import {
  addRecentProductSearch,
  clearRecentProductSearches,
  loadRecentProductSearches,
} from "@/utils/recentProductSearches";

export default function ListingSearchBar({ value = "", onSubmit }) {
  const [draft, setDraft] = useState(value);
  const [recent, setRecent] = useState([]);

  useEffect(() => {
    setDraft(value);
  }, [value]);

  useEffect(() => {
    setRecent(loadRecentProductSearches());
  }, []);

  const submit = (term) => {
    const next = String(term ?? draft).trim();
    if (next.length < 2 && next.length > 0) return;
    if (next.length >= 2) {
      setRecent(addRecentProductSearch(next));
    }
    onSubmit?.(next);
  };

  return (
    <div className="w-full mb-4">
      <form
        onSubmit={(e) => {
          e.preventDefault();
          submit(draft);
        }}
        className="flex h-11 items-center gap-2 rounded-xl border border-qgray-border bg-white px-3"
      >
        <svg
          className="h-4 w-4 shrink-0 text-qgray"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          aria-hidden="true"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"
          />
        </svg>
        <input
          type="search"
          value={draft}
          onChange={(e) => setDraft(e.target.value)}
          placeholder="Ürün ara..."
          className="h-full w-full bg-transparent text-[13px] outline-none"
          aria-label="Ürün ara"
        />
        {draft ? (
          <button
            type="button"
            className="text-xs text-qgray hover:text-qblack"
            onClick={() => {
              setDraft("");
              onSubmit?.("");
            }}
          >
            Temizle
          </button>
        ) : null}
        <button
          type="submit"
          className="shrink-0 rounded-lg bg-qyellow px-3 py-1.5 text-[12px] font-700 text-qblack"
        >
          Ara
        </button>
      </form>
      {recent.length > 0 ? (
        <div className="mt-2 flex flex-wrap items-center gap-2">
          <span className="text-[11px] font-600 text-qgray">Son aramalar:</span>
          {recent.map((term) => (
            <button
              key={term}
              type="button"
              onClick={() => {
                setDraft(term);
                submit(term);
              }}
              className="rounded-full border border-qgray-border bg-white px-2.5 py-1 text-[11px] font-600 text-qblack hover:border-qyellow"
            >
              {term}
            </button>
          ))}
          <button
            type="button"
            className="text-[11px] text-qgray underline"
            onClick={() => setRecent(clearRecentProductSearches())}
          >
            Temizle
          </button>
        </div>
      ) : null}
    </div>
  );
}
