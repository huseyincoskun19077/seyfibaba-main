import Link from "next/link";
import appConfig from "@/appConfig";

const PLAY_STORE =
  (typeof process !== "undefined" && process.env.NEXT_PUBLIC_PLAY_STORE_URL) ||
  "https://play.google.com/store/apps/details?id=com.seyfibaba.app";

const APP_STORE =
  (typeof process !== "undefined" && process.env.NEXT_PUBLIC_APP_STORE_URL) ||
  "https://apps.apple.com/tr/search?term=Kuaför Tedarik";

export const metadata = {
  title: "Mobil Uygulama İndir | Kuaför Tedarik",
  description:
    "Kuaför Tedarik mobil uygulamasını iPhone (App Store) ve Android (Google Play) için indirin.",
  alternates: {
    canonical: "/indir",
  },
  openGraph: {
    title: "Mobil Uygulama İndir | Kuaför Tedarik",
    description:
      "Kuaför Tedarik’i App Store ve Google Play’den indirin. Kuaför ve berber malzemeleri cebinizde.",
    url: `${appConfig.APPLICATION_URL || "https://kuafortedarik.com"}/indir`,
    type: "website",
  },
};

function AppleIcon() {
  return (
    <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor" aria-hidden>
      <path d="M16.365 1.43c0 1.14-.42 2.2-1.18 3.02-.8.86-2.1 1.52-3.2 1.43-.14-1.1.4-2.26 1.16-3.05.8-.85 2.2-1.48 3.22-1.4zM20.5 17.2c-.55 1.27-.82 1.84-1.53 2.97-1 1.57-2.4 3.52-4.15 3.54-1.55.02-1.95-1.02-4.06-1-2.1.01-2.55 1.03-4.1 1.01-1.74-.02-3.07-1.78-4.07-3.35C.9 17.1-.3 12.7 1.4 9.55c1.07-1.98 2.96-3.23 4.65-3.23 1.74 0 2.83 1.05 4.26 1.05 1.38 0 2.22-1.06 4.25-1.06 1.52 0 3.13.83 4.2 2.26-3.7 2.03-3.1 7.32.74 8.63z" />
    </svg>
  );
}

function PlayIcon() {
  return (
    <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor" aria-hidden>
      <path d="M3.6 2.3c-.3.2-.5.5-.5.9v17.6c0 .4.2.7.5.9l.1.1 9.9-9.9v-.2L3.7 2.2l-.1.1zm12.2 7L12.5 7l-2.2 2.2 3.3 3.3 2.2-3.2zm-4.4 4.4-3.2 3.2 4.3 2.4 2.1-3.1-3.2-2.5zM4.8 3.5l8.3 4.7 2.3-3.3L5.9 2.5c-.4-.2-.8-.1-1.1.1v.9zm9.7 15.3-4.3-2.4 8.5-4.8c.10.0.0.1.3.2l-4.5 7z" />
    </svg>
  );
}

export default function IndirPage() {
  return (
    <div className="w-full bg-gradient-to-b from-[#04334a]/[0.06] to-white">
      <div className="container-x mx-auto px-4 py-12 md:py-20">
        <div className="max-w-3xl mx-auto text-center">
          <p className="text-sm font-700 tracking-wide text-[#04334a]/60 uppercase mb-3">
            Kuaför Tedarik Mobil
          </p>
          <h1 className="text-3xl md:text-4xl font-800 text-[#04334a] leading-tight mb-4">
            Uygulamayı indirin
          </h1>
          <p className="text-base md:text-lg text-[#04334a]/70 mb-10 max-w-xl mx-auto">
            Kuaför ve berber malzemelerini cebinizden takip edin. iPhone ve
            Android için tek yerden indirin.
          </p>

          <div className="grid sm:grid-cols-2 gap-4 max-w-xl mx-auto">
            <a
              href={APP_STORE}
              target="_blank"
              rel="noopener noreferrer"
              className="group flex items-center gap-4 rounded-2xl bg-[#04334a] text-white px-5 py-5 shadow-lg hover:brightness-110 transition"
            >
              <span className="shrink-0 text-qyellow">
                <AppleIcon />
              </span>
              <span className="text-left">
                <span className="block text-[11px] font-500 text-white/70">
                  App Store
                </span>
                <span className="block text-lg font-800 leading-tight">
                  iPhone için indir
                </span>
              </span>
            </a>

            <a
              href={PLAY_STORE}
              target="_blank"
              rel="noopener noreferrer"
              className="group flex items-center gap-4 rounded-2xl bg-qyellow text-[#04334a] px-5 py-5 shadow-lg hover:brightness-95 transition"
            >
              <span className="shrink-0">
                <PlayIcon />
              </span>
              <span className="text-left">
                <span className="block text-[11px] font-500 text-[#04334a]/70">
                  Google Play
                </span>
                <span className="block text-lg font-800 leading-tight">
                  Android için indir
                </span>
              </span>
            </a>
          </div>

          <div className="mt-10">
            <Link
              href="/products"
              className="text-sm font-700 text-[#04334a] underline-offset-4 hover:underline"
            >
              Web’de alışverişe devam et
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}
