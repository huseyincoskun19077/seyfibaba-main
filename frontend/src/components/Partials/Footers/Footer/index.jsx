"use client";
import Image from "next/image";
import Link from "next/link";
import { useEffect, useMemo, useState } from "react";
import { useSelector } from "react-redux";
import FontAwesomeCom from "../../../Helpers/icons/FontAwesomeCom";
import appConfig from "@/appConfig";
import { getProductImageProps } from "@/utils/productImage";
import { legalPath } from "@/config/legalDocuments";
import { marketplaceUrl } from "@/utils/secondHandSite";

const brandLinks = [
  { href: "/about", label: "Biz Kimiz" },
  { href: "/contact", label: "Kariyer" },
  { href: "/contact", label: "İletişim" },
  { href: legalPath("privacy-policy"), label: "Kuaför Tedarik'te Güvenlik" },
  { href: legalPath("prohibited-products"), label: "Geri Çağrılan Ürünler" },
];

const campaignLinks = [
  { href: "/flash-sale", label: "Kampanyalar" },
  { href: "/yardim", label: "Alışveriş Kredisi" },
  { href: "/blogs", label: "Hediye Fikirleri" },
];

const sellerLinks = [
  { href: "/satici", label: "Kuaför Tedarik'te Satış Yap" },
  { href: "/satici/nasil-satici-olunur", label: "Temel Kavramlar" },
  { href: "/satici", label: "Kuaför Tedarik Akademi" },
];

const helpLinksFallback = [
  { href: "/yardim", label: "Sıkça Sorulan Sorular" },
  { href: "/yardim?kategori=destek", label: "Canlı Yardım" },
  { href: legalPath("delivery-return"), label: "Nasıl İade Edebilirim" },
  { href: "/tracking-order", label: "İşlem Rehberi" },
];

const socialSvgIcons = {
  "fab fa-facebook-f": (
    <svg viewBox="0 0 320 512" fill="currentColor" className="w-4 h-4"><path d="M80 299.3V512H196V299.3h86.5l18-97.8H196V136.9c0-51.7 20.7-71.5 72.7-71.5 16.3 0 29.4.4 37 1.2V7.9C291.4 4 256.4 0 236.2 0 129.3 0 80 50.5 80 159.4v42.1H0v97.8h80z"/></svg>
  ),
  "fab fa-instagram": (
    <svg viewBox="0 0 448 512" fill="currentColor" className="w-4 h-4"><path d="M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.8-26.8 26.8-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8 26.8 12 26.8 26.8zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z"/></svg>
  ),
  "fab fa-x-twitter": (
    <svg viewBox="0 0 512 512" fill="currentColor" className="w-4 h-4"><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"/></svg>
  ),
  "fab fa-twitter": (
    <svg viewBox="0 0 512 512" fill="currentColor" className="w-4 h-4"><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"/></svg>
  ),
  "fab fa-linkedin-in": (
    <svg viewBox="0 0 448 512" fill="currentColor" className="w-4 h-4"><path d="M100.3 448H7.4V148.9h92.9zM53.8 108.1C24.1 108.1 0 83.5 0 53.8a53.8 53.8 0 0 1 107.6 0c0 29.7-24.1 54.3-53.8 54.3zM447.9 448h-92.7V302.4c0-34.7-.7-79.2-48.3-79.2-48.3 0-55.7 37.7-55.7 76.7V448h-92.8V148.9h89.1v40.8h1.3c12.4-23.5 42.7-48.3 87.9-48.3 94 0 111.3 61.9 111.3 142.3V448z"/></svg>
  ),
  "fab fa-linkedin": (
    <svg viewBox="0 0 448 512" fill="currentColor" className="w-4 h-4"><path d="M100.3 448H7.4V148.9h92.9zM53.8 108.1C24.1 108.1 0 83.5 0 53.8a53.8 53.8 0 0 1 107.6 0c0 29.7-24.1 54.3-53.8 54.3zM447.9 448h-92.7V302.4c0-34.7-.7-79.2-48.3-79.2-48.3 0-55.7 37.7-55.7 76.7V448h-92.8V148.9h89.1v40.8h1.3c12.4-23.5 42.7-48.3 87.9-48.3 94 0 111.3 61.9 111.3 142.3V448z"/></svg>
  ),
  "fab fa-youtube": (
    <svg viewBox="0 0 576 512" fill="currentColor" className="w-4 h-4"><path d="M549.7 124.1c-6.3-23.7-24.8-42.3-48.3-48.6C458.8 64 288 64 288 64S117.2 64 74.6 75.5c-23.5 6.3-42 24.9-48.3 48.6-11.4 42.9-11.4 132.3-11.4 132.3s0 89.4 11.4 132.3c6.3 23.7 24.8 41.5 48.3 47.8C117.2 448 288 448 288 448s170.8 0 213.4-11.5c23.5-6.3 42-24.2 48.3-47.8 11.4-42.9 11.4-132.3 11.4-132.3s0-89.4-11.4-132.3zm-317.5 213.5V175.2l142.7 81.2-142.7 81.2z"/></svg>
  ),
  "fab fa-tiktok": (
    <svg viewBox="0 0 448 512" fill="currentColor" className="w-4 h-4"><path d="M448 209.9a210.1 210.1 0 0 1-122.8-39.3V349.4A162.6 162.6 0 1 1 185 188.3v89.9a74.6 74.6 0 1 0 52.2 71.2V0h88a121.2 121.2 0 0 0 1.9 22.2 122.2 122.2 0 0 0 53.9 80.2 121.4 121.4 0 0 0 67 20.1z"/></svg>
  ),
};

const fallbackSocialLinks = [
  { icon: "fab fa-instagram", link: "https://instagram.com/seyfibaba", label: "Instagram" },
  { icon: "fab fa-youtube", link: "https://youtube.com/@seyfibaba", label: "YouTube" },
  { icon: "fab fa-facebook-f", link: "https://facebook.com/seyfibaba", label: "Facebook" },
  { icon: "fab fa-x-twitter", link: "https://x.com/seyfibaba", label: "X" },
];

const FALLBACK_PLAY_STORE =
  (typeof process !== "undefined" && process.env.NEXT_PUBLIC_PLAY_STORE_URL) ||
  "https://play.google.com/store/apps/details?id=com.seyfibaba.app";

const FALLBACK_APP_STORE =
  (typeof process !== "undefined" && process.env.NEXT_PUBLIC_APP_STORE_URL) ||
  "https://apps.apple.com/tr/search?term=Seyfibaba";

function AppleBadgeIcon() {
  return (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden>
      <path d="M16.365 1.43c0 1.14-.42 2.2-1.18 3.02-.8.86-2.1 1.52-3.2 1.43-.14-1.1.4-2.26 1.16-3.05.8-.85 2.2-1.48 3.22-1.4zM20.5 17.2c-.55 1.27-.82 1.84-1.53 2.97-1 1.57-2.4 3.52-4.15 3.54-1.55.02-1.95-1.02-4.06-1-2.1.01-2.55 1.03-4.1 1.01-1.74-.02-3.07-1.78-4.07-3.35C.9 17.1-.3 12.7 1.4 9.55c1.07-1.98 2.96-3.23 4.65-3.23 1.74 0 2.83 1.05 4.26 1.05 1.38 0 2.22-1.06 4.25-1.06 1.52 0 3.13.83 4.2 2.26-3.7 2.03-3.1 7.32.74 8.63z" />
    </svg>
  );
}

function PlayBadgeIcon() {
  return (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden>
      <path d="M3.6 2.3c-.3.2-.5.5-.5.9v17.6c0 .4.2.7.5.9l.1.1 9.9-9.9v-.2L3.7 2.2l-.1.1zm12.2 7L12.5 7l-2.2 2.2 3.3 3.3 2.2-3.2zm-4.4 4.4-3.2 3.2 4.3 2.4 2.1-3.1-3.2-2.5zM4.8 3.5l8.3 4.7 2.3-3.3L5.9 2.5c-.4-.2-.8-.1-1.1.1v.9zm9.7 15.3-4.3-2.4 8.5-4.8c.10.0.0.1.3.2l-4.5 7z" />
    </svg>
  );
}

function StoreBadge({ href, imageSrc, label, sublabel, icon, dark = false }) {
  if (!href) return null;
  const className = dark
    ? "inline-flex items-center gap-2.5 rounded-xl bg-white text-[#04334a] px-3.5 py-2.5 hover:bg-qyellow transition shadow-sm"
    : "inline-flex items-center gap-2.5 rounded-xl bg-[#04334a] text-white px-3.5 py-2.5 hover:brightness-110 transition shadow-sm";

  if (imageSrc) {
    return (
      <a href={href} target="_blank" rel="noopener noreferrer" className="block shrink-0">
        <Image
          width={140}
          height={42}
          src={imageSrc}
          alt={label}
          className="h-[42px] w-auto object-contain"
          unoptimized
        />
      </a>
    );
  }

  return (
    <a href={href} target="_blank" rel="noopener noreferrer" className={className}>
      <span className="shrink-0">{icon}</span>
      <span className="text-left leading-tight">
        <span className="block text-[10px] font-500 opacity-70">{sublabel}</span>
        <span className="block text-sm font-800">{label}</span>
      </span>
    </a>
  );
}

function FooterLinkList({ links, columns = 3 }) {
  if (!links?.length) {
    return (
      <p className="text-sm text-[#04334a]/45">
        Henüz link eklenmedi. Admin → Footer linklerinden ekleyebilirsiniz.
      </p>
    );
  }
  return (
    <ul
      className={`grid gap-x-6 gap-y-1.5 text-[13px] text-[#04334a]/75 ${
        columns === 3
          ? "grid-cols-2 sm:grid-cols-3"
          : "grid-cols-2 sm:grid-cols-3"
      }`}
    >
      {links.map((item, i) => (
        <li key={`${item.link}-${i}`}>
          <Link
            href={item.link || "#"}
            className="hover:text-[#04334a] hover:underline underline-offset-2"
          >
            {item.title}
          </Link>
        </li>
      ))}
    </ul>
  );
}

function ColTitle({ children }) {
  return (
    <h3 className="text-sm font-800 text-[#04334a] mb-3 tracking-wide">
      {children}
    </h3>
  );
}

function SimpleLinks({ items }) {
  return (
    <ul className="space-y-2">
      {items.map((item) => (
        <li key={`${item.href}-${item.label}`}>
          <Link
            href={item.href}
            className="text-[13px] text-[#04334a]/75 hover:text-[#04334a] hover:underline underline-offset-2"
          >
            {item.label}
          </Link>
        </li>
      ))}
    </ul>
  );
}

export default function Footer({ settings, isSecondHandSite = false }) {
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const [footerData, setFooterData] = useState({
    footerContent: null,
    socialLinks: null,
    firstColumn: null,
    secondColumn: null,
    thirdColumn: null,
  });

  useEffect(() => {
    if (websiteSetup?.payload) {
      const { payload } = websiteSetup;
      setFooterData({
        footerContent: payload.footer,
        socialLinks: payload.social_links,
        firstColumn: payload.footer_first_col,
        secondColumn: payload.footer_second_col,
        thirdColumn: payload.footer_third_col,
      });
    }
  }, [websiteSetup]);

  const { footerContent, socialLinks, firstColumn, secondColumn, thirdColumn } =
    footerData;

  const normalizeLinks = (col) => {
    if (!Array.isArray(col?.col_links)) return [];
    return col.col_links
      .filter((item) => item?.title && item?.link)
      .map((item) => ({
        ...item,
        link: item.link === "/become-seller" ? "/satici-kayit" : item.link,
        title:
          item.link === "/become-seller" ||
          String(item.title || "").toLowerCase().includes("satıcı")
            ? item.link === "/become-seller"
              ? "Satıcı Ol"
              : item.title
            : item.title,
      }));
  };

  const popularBrands = useMemo(
    () => normalizeLinks(firstColumn),
    [firstColumn]
  );
  const popularPages = useMemo(
    () => normalizeLinks(secondColumn),
    [secondColumn]
  );
  const helpFromAdmin = useMemo(() => {
    const links = normalizeLinks(thirdColumn);
    if (!links.length) return helpLinksFallback;
    return links.map((l) => ({ href: l.link, label: l.title }));
  }, [thirdColumn]);

  const resolvedSocialLinks =
    socialLinks?.length > 0 ? socialLinks : fallbackSocialLinks;

  const etbisUrl = String(footerContent?.etbis_url || "").trim();
  const etbisImage = footerContent?.etbis_image
    ? `${appConfig.BASE_URL}${footerContent.etbis_image}`
    : null;

  const appStoreUrl =
    String(footerContent?.app_store_url || "").trim() || FALLBACK_APP_STORE;
  const playStoreUrl =
    String(footerContent?.play_store_url || "").trim() || FALLBACK_PLAY_STORE;
  const appStoreImage = footerContent?.app_store_image
    ? `${appConfig.BASE_URL}${footerContent.app_store_image}`
    : null;
  const playStoreImage = footerContent?.play_store_image
    ? `${appConfig.BASE_URL}${footerContent.play_store_image}`
    : null;

  if (isSecondHandSite) {
    return (
      <footer className="footer-section-wrapper bg-white print:hidden border-t border-gray-100">
        <div className="container-x mx-auto py-10">
          <div className="flex flex-col items-center gap-4 text-center">
            <p className="text-sm font-800 text-[#04334a]">Kuaför Tedarik İkinci El</p>
            <div className="flex flex-wrap justify-center gap-x-5 gap-y-2 text-sm text-[#04334a]/70">
              <Link href="/" className="hover:text-[#04334a]">İlanlar</Link>
              <Link href="/ikinci-el-sozlesmesi" className="hover:text-[#04334a]">Sözleşme</Link>
              <Link href="/ikinci-el-kvkk" className="hover:text-[#04334a]">KVKK</Link>
              <Link href={marketplaceUrl("/")} className="hover:text-[#04334a]">Mağazaya git</Link>
            </div>
          </div>
        </div>
      </footer>
    );
  }

  return (
    <footer className="footer-section-wrapper print:hidden">
      {/* Üst: Popüler marka / sayfalar — admin */}
      <div className="bg-[#eef2f5] border-t border-[#04334a]/10">
        <div className="container-x mx-auto py-8 md:py-10">
          <div className="grid md:grid-cols-2 gap-8 md:gap-12">
            <div>
              <ColTitle>
                {firstColumn?.columnTitle || "Popüler Marka ve Mağazalar"}
              </ColTitle>
              <FooterLinkList links={popularBrands} />
            </div>
            <div>
              <ColTitle>
                {secondColumn?.columnTitle || "Popüler Sayfalar"}
              </ColTitle>
              <FooterLinkList links={popularPages} />
            </div>
          </div>
        </div>
      </div>

      {/* Orta: kurumsal + güven */}
      <div className="bg-[#f7f9fb] border-t border-[#04334a]/08">
        <div className="container-x mx-auto py-10 md:py-12">
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-8">
            <div>
              <ColTitle>Kuaför Tedarik</ColTitle>
              <SimpleLinks items={brandLinks} />
            </div>

            <div>
              <ColTitle>Kampanyalar</ColTitle>
              <SimpleLinks items={campaignLinks} />
            </div>

            <div>
              <ColTitle>Satıcı</ColTitle>
              <SimpleLinks items={sellerLinks} />
            </div>

            <div>
              <ColTitle>
                {thirdColumn?.columnTitle || "Yardım"}
              </ColTitle>
              <SimpleLinks items={helpFromAdmin} />
            </div>

            <div className="col-span-2 md:col-span-1 space-y-5">
              <div>
                <ColTitle>Güvenli Alışveriş</ColTitle>
                <div className="flex flex-wrap items-center gap-2 mb-3">
                  <span className="inline-flex items-center rounded-lg bg-[#04334a] text-white px-3 py-2 text-sm font-800 tracking-wide">
                    iyzico
                  </span>
                  <span className="text-[11px] text-[#04334a]/55 leading-snug">
                    3D Secure ile güvenli ödeme
                  </span>
                </div>
                {footerContent?.payment_image ? (
                  <Image
                    width={240}
                    height={80}
                    src={`${appConfig.BASE_URL + footerContent.payment_image}`}
                    alt="Güvenli ödeme"
                    className="h-auto w-full max-w-[240px] object-contain mb-3"
                    unoptimized
                  />
                ) : null}
                {(etbisImage || etbisUrl) && (
                  <div className="mt-2">
                    <p className="text-[11px] font-700 text-[#04334a]/55 mb-2">
                      Güvenlik Sertifikası
                    </p>
                    {etbisUrl ? (
                      <a
                        href={etbisUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-block"
                      >
                        {etbisImage ? (
                          <Image
                            width={120}
                            height={60}
                            src={etbisImage}
                            alt="ETBİS"
                            className="h-[52px] w-auto object-contain"
                            unoptimized
                          />
                        ) : (
                          <span className="inline-flex items-center rounded-lg border border-[#04334a]/20 bg-white px-3 py-2 text-xs font-800 text-[#04334a]">
                            ETBİS
                          </span>
                        )}
                      </a>
                    ) : etbisImage ? (
                      <Image
                        width={120}
                        height={60}
                        src={etbisImage}
                        alt="ETBİS"
                        className="h-[52px] w-auto object-contain"
                        unoptimized
                      />
                    ) : null}
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Alt bar: sosyal + App Store / Google Play */}
      <div className="bg-[#04334a]">
        <div className="container-x mx-auto py-5 md:py-6 flex flex-col md:flex-row md:items-center md:justify-between gap-5">
          <div className="flex flex-wrap items-center gap-3">
            {resolvedSocialLinks.map((item, i) => (
              <a
                key={i}
                href={item.link}
                target="_blank"
                rel="noreferrer"
                aria-label={item.label || "Sosyal medya"}
                className="h-10 w-10 inline-flex items-center justify-center rounded-full bg-white text-[#04334a] hover:bg-qyellow transition"
              >
                {socialSvgIcons[item.icon] || (
                  <FontAwesomeCom className="w-4 h-4" icon={item.icon} />
                )}
              </a>
            ))}
          </div>

          <div className="flex flex-wrap items-center gap-3">
            <StoreBadge
              href={appStoreUrl}
              imageSrc={appStoreImage}
              label="App Store"
              sublabel="İndirin"
              icon={<AppleBadgeIcon />}
              dark
            />
            <StoreBadge
              href={playStoreUrl}
              imageSrc={playStoreImage}
              label="Google Play"
              sublabel="İndirin"
              icon={<PlayBadgeIcon />}
            />
          </div>
        </div>

        <div className="border-t border-white/10">
          <div className="container-x mx-auto py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div className="flex items-center gap-3">
              {settings?.logo ? (
                <Link href="/" className="relative block h-7 w-28">
                  <Image
                    fill
                    sizes="112px"
                    className="object-contain brightness-0 invert opacity-90"
                    {...getProductImageProps(settings.logo)}
                    alt="Kuaför Tedarik"
                  />
                </Link>
              ) : null}
              <span className="text-[11px] text-white/60">
                {footerContent?.copyright ||
                  `© ${new Date().getFullYear()} Kuaför Tedarik. Tüm hakları saklıdır.`}
              </span>
            </div>
            <span className="text-[11px] text-white/45">Türkiye</span>
          </div>
        </div>
      </div>
    </footer>
  );
}
