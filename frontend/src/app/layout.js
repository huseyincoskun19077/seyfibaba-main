import appConfig from "@/appConfig";
import "@/app/globals.css";
import AosWrapper from "@/components/Helpers/AosWrapper";
// snake loader
import NextSnakeLoader from "@/components/Helpers/Loaders/NextSnakeLoader";
// external css
import "@/assets/css/loader.css";
import "@/assets/css/selecbox.css";
import DefaultLayout from "@/components/Partials/DefaultLayout";
import { Providers } from "@/redux/providers";
import Toaster from "@/components/Helpers/Toaster";
import localFont from "next/font/local";

const inter = localFont({
  src: [
    { path: "../../public/assets/fonts/Inter-Regular.ttf", weight: "400", style: "normal" },
    { path: "../../public/assets/fonts/Inter-Medium.ttf", weight: "500", style: "normal" },
    { path: "../../public/assets/fonts/Inter-SemiBold.ttf", weight: "600", style: "normal" },
    { path: "../../public/assets/fonts/Inter-Bold.ttf", weight: "700", style: "normal" },
  ],
  variable: "--font-inter",
  display: "swap",
});

export const metadata = {
  metadataBase: new URL(appConfig.APPLICATION_URL),
  title: {
    default: "Berber ve Kuaför Malzemeleri | Seyfibaba",
    template: "%s | Seyfibaba",
  },
  description: "Berber malzemeleri, kuaför ekipmanları, berber koltuğu ve salon mobilyaları. Profesyoneller için en uygun fiyatlı alışveriş sitesi.",
  authors: [{ name: "Seyfibaba", url: appConfig.APPLICATION_URL }],
  publisher: "Seyfibaba",
  robots: {
    index: true,
    follow: true,
    googleBot: {
      index: true,
      follow: true,
      "max-video-preview": -1,
      "max-image-preview": "large",
      "max-snippet": -1,
    },
  },
  icons: {
    icon: appConfig.BASE_URL + "uploads/website-images/favicon.png",
    shortcut: appConfig.BASE_URL + "uploads/website-images/favicon.png",
    apple: appConfig.BASE_URL + "uploads/website-images/favicon.png",
  },
  verification: {
    google: process.env.NEXT_PUBLIC_GOOGLE_SITE_VERIFICATION || "",
    yandex: process.env.NEXT_PUBLIC_YANDEX_VERIFICATION || "",
    other: {
      "msvalidate.01": process.env.NEXT_PUBLIC_BING_VERIFICATION || "",
    },
  },
  alternates: {
    canonical: "/",
  },
  facebook: {
    appId: process.env.NEXT_PUBLIC_FB_APP_ID || "",
  },
  openGraph: {
    type: "website",
    siteName: "Seyfibaba",
    title: "Berber ve Kuaför Malzemeleri | Seyfibaba",
    description: "Berber malzemeleri, kuaför ekipmanları, berber koltuğu ve salon mobilyaları. Profesyoneller için en uygun fiyatlı alışveriş sitesi.",
    url: appConfig.APPLICATION_URL,
    locale: "tr_TR",
    images: [
      {
        url: appConfig.BASE_URL + "uploads/website-images/logo-2025-12-18-04-53-36-7704.png",
        width: 1200,
        height: 630,
        alt: "Seyfibaba Pazaryeri",
      },
    ],
  },
  twitter: {
    card: "summary_large_image",
    site: "@seyfibaba",
    title: "Berber ve Kuaför Malzemeleri | Seyfibaba",
    description: "Berber malzemeleri, kuaför ekipmanları, berber koltuğu ve salon mobilyaları. Profesyoneller için en uygun fiyatlı alışveriş sitesi.",
    images: [appConfig.BASE_URL + "uploads/website-images/logo-2025-12-18-04-53-36-7704.png"],
  },
};

export const viewport = {
  width: "device-width",
  initialScale: 1,
  maximumScale: 5,
  themeColor: "#ffffff",
};

export default function RootLayout({ children }) {
  return (
    <html lang="tr" translate="no" className="notranslate">
      <head>
        <link rel="preconnect" href="https://admin.seyfibaba.com/" />
        <link rel="dns-prefetch" href="https://admin.seyfibaba.com/" />
      </head>
      <body className={`${inter.variable} font-sans antialiased`} suppressHydrationWarning={true}>
        {/* loader */}
        <NextSnakeLoader />
        {/* Toaster container */}
        <Toaster />
        {/* redux and context providers */}
        <Providers>
          {/* global app shell */}
          <DefaultLayout>
            <AosWrapper>{children}</AosWrapper>
          </DefaultLayout>
        </Providers>
      </body>
    </html>
  );
}
