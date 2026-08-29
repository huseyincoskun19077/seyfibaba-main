"use client";
import CurrencyConvert from "./CurrencyConvert";

const FREE_SHIPPING_THRESHOLD = 500;

export default function FreeShippingBar({ totalPrice }) {
  if (!totalPrice || totalPrice <= 0) return null;

  const remaining = FREE_SHIPPING_THRESHOLD - totalPrice;
  const progress = Math.min((totalPrice / FREE_SHIPPING_THRESHOLD) * 100, 100);

  if (remaining > 0) {
    return (
      <div className="p-3 bg-amber-50 border border-amber-200 rounded-lg">
        <div className="flex items-center gap-2 mb-2">
          <svg
            className="w-5 h-5 text-amber-600 flex-shrink-0"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"
            />
          </svg>
          <span className="text-sm font-medium text-amber-800">
            Ücretsiz kargo için{" "}
            <span className="font-bold" suppressHydrationWarning>
              <CurrencyConvert price={remaining} />
            </span>{" "}
            daha ekleyin!
          </span>
        </div>
        <div className="w-full bg-amber-200 rounded-full h-2">
          <div
            className="bg-amber-500 h-2 rounded-full transition-all duration-500"
            style={{ width: `${progress}%` }}
          />
        </div>
      </div>
    );
  }

  return (
    <div className="p-3 bg-green-50 border border-green-200 rounded-lg">
      <div className="flex items-center gap-2">
        <svg
          className="w-5 h-5 text-green-600 flex-shrink-0"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M5 13l4 4L19 7"
          />
        </svg>
        <span className="text-sm font-medium text-green-800">
          Tebrikler! Ücretsiz kargo kazandınız!
        </span>
      </div>
    </div>
  );
}
