"use client";

import Link from "next/link";
import LoginWidget from "@/components/Auth/Login/LoginWidget";

export default function SellerLogin() {
  return (
    <div className="w-full min-h-[70vh] bg-[#f4f7f9]">
      <section className="relative overflow-hidden border-b border-[#04334a]/10">
        <div
          className="absolute inset-0"
          style={{
            background:
              "radial-gradient(ellipse 80% 70% at 50% 0%, rgba(252,191,73,0.28), transparent 55%), linear-gradient(180deg, #eef3f6 0%, #f4f7f9 100%)",
          }}
        />
        <div
          className="pointer-events-none absolute inset-0 opacity-25"
          style={{
            backgroundImage:
              "repeating-linear-gradient(90deg, transparent, transparent 48px, rgba(4,51,74,0.04) 49px), repeating-linear-gradient(0deg, transparent, transparent 48px, rgba(4,51,74,0.04) 49px)",
          }}
        />
        <div className="relative container-x mx-auto px-4 py-10 md:py-12 text-center">
          <p className="mb-2 text-xs font-800 uppercase tracking-widest text-[#04334a]/50">
            Kuaför Tedarik Satıcı Paneli
          </p>
          <h1 className="text-2xl font-800 text-[#04334a] md:text-3xl">
            Satıcı girişi
          </h1>
          <p className="mx-auto mt-2 max-w-lg text-sm text-[#04334a]/60">
            Telefon veya e-posta ile giriş yapın; sipariş, ürün ve hakediş
            işlemlerinizi yönetin.
          </p>
        </div>
      </section>

      <div className="container-x mx-auto px-4 py-8 md:py-10">
        <div className="mx-auto max-w-md">
          <div className="rounded-2xl border border-[#04334a]/10 bg-white p-5 shadow-sm sm:p-8">
            <LoginWidget variant="seller" redirect={false} />
          </div>

          <div className="mt-6 flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-center text-sm text-[#04334a]/55">
            <Link href="/satici" className="font-600 hover:text-[#04334a]">
              Satıcı merkezi
            </Link>
            <span className="hidden sm:inline text-[#04334a]/25">·</span>
            <Link href="/satici-kayit" className="font-600 hover:text-[#04334a]">
              Satıcı başvurusu
            </Link>
            <span className="hidden sm:inline text-[#04334a]/25">·</span>
            <Link href="/yardim" className="font-600 hover:text-[#04334a]">
              Yardım
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}
