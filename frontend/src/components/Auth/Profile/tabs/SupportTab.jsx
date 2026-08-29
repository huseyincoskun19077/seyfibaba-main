import React from "react";

export default function SupportTab() {
  return (
    <div className="support-tab w-full">
      <div className="rounded-3xl border border-[#EDEDED] bg-white p-8 text-center">
        <h2 className="text-2xl font-semibold text-qblack">Destek Talepleri</h2>
        <p className="mx-auto mt-3 max-w-2xl text-sm leading-7 text-qgray">
          Bu alanda acik destek kayitlariniz listelenir. Su anda hesabiniz icin
          olusturulmus bir destek talebi bulunmuyor.
        </p>
        <div className="mt-6 inline-flex rounded-full bg-[#FFF6E5] px-5 py-2 text-sm font-semibold text-[#9f7b2f]">
          Yeni destek modulu baglandiginda canli veriler burada gosterilecek.
        </div>
      </div>
    </div>
  );
}
