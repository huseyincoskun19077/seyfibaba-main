"use client";

import { getOrderStatusStepIndex } from "@/utils/orderStatus";

const STEPS = [
  { label: "Sipariş alındı" },
  { label: "Hazırlanıyor" },
  { label: "Kargoda" },
  { label: "Teslim" },
];

export default function OrderStatusStepper({ orderStatus }) {
  if (orderStatus === "Reddedildi") {
    return (
      <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-center text-sm font-semibold text-red-700">
        Siparişiniz reddedildi
      </div>
    );
  }

  const activeIndex = Math.min(getOrderStatusStepIndex(orderStatus), STEPS.length - 1);
  const isCompleted = orderStatus === "Tamamlandı";

  return (
    <div className="rounded-2xl border border-slate-200/80 bg-gradient-to-br from-slate-50 to-white p-4 sm:p-6 print:hidden">
      <div className="relative flex items-start justify-between gap-1 sm:gap-2">
        {STEPS.map((step, index) => {
          const done = isCompleted ? true : index < activeIndex;
          const active = !isCompleted && index === activeIndex;

          return (
            <div key={step.label} className="relative z-10 flex flex-1 flex-col items-center text-center">
              {index < STEPS.length - 1 && (
                <div
                  className={`absolute left-[calc(50%+14px)] top-4 hidden h-0.5 w-[calc(100%-28px)] sm:block ${
                    done ? "bg-qyellow" : "bg-slate-200"
                  }`}
                  aria-hidden
                />
              )}
              <div
                className={`flex h-8 w-8 items-center justify-center rounded-full border-2 text-xs font-bold transition-colors sm:h-9 sm:w-9 ${
                  done
                    ? "border-qyellow bg-qyellow text-qblack"
                    : active
                      ? "border-qyellow bg-white text-qblack shadow-sm"
                      : "border-slate-200 bg-white text-slate-400"
                }`}
              >
                {done ? (
                  <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                  </svg>
                ) : (
                  index + 1
                )}
              </div>
              <p
                className={`mt-2 max-w-[4.5rem] text-[10px] font-medium leading-tight sm:max-w-none sm:text-xs ${
                  active || done ? "text-qblack" : "text-slate-400"
                }`}
              >
                {step.label}
              </p>
            </div>
          );
        })}
      </div>
    </div>
  );
}
