import Link from "next/link";
import apiRoutes from "@/appConfig/apiRoutes";
import { getSecondHandListingSeoPath } from "@/api/secondHandPublic";
import SecondHandListFilters from "./SecondHandListFilters";
import SecondHandForYouBlock from "./SecondHandForYouBlock";
import SecondHandHomeSlider from "./SecondHandHomeSlider";
import { marketplaceProfileUrl } from "@/utils/secondHandSite";
import { displayTurkishLabel } from "@/utils/turkishDisplay";

function imageSrc(imageId) {
  if (!imageId) return null;
  return `${apiRoutes.secondHandListingImage}${imageId}`;
}

function formatTry(value) {
  const n = Number(value);
  if (Number.isNaN(n)) return "—";
  return new Intl.NumberFormat("tr-TR", {
    style: "currency",
    currency: "TRY",
    maximumFractionDigits: 0,
  }).format(n);
}

function ListingCard({ item, conditionLabel, featured = false }) {
  const thumb = item.images?.length ? imageSrc(item.images[0].id) : null;
  return (
    <Link
      href={`/ikinci-el/${getSecondHandListingSeoPath(item)}`}
      className={`group rounded-2xl overflow-hidden bg-white hover:shadow-lg transition-shadow ${
        featured
          ? "border border-amber-200 ring-1 ring-amber-900/5"
          : "border border-gray-200 ring-1 ring-black/[0.02]"
      }`}
    >
      <div className="aspect-[4/3] bg-[#f4f4f6] relative">
        {thumb ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img
            src={thumb}
            alt=""
            className="w-full h-full object-contain group-hover:scale-[1.02] transition-transform"
          />
        ) : (
          <div className="w-full h-full flex items-center justify-center text-qgray text-sm">Görsel yok</div>
        )}
        {item.is_urgent ? (
          <span className="absolute left-2 top-2 rounded-full bg-red-600 text-white text-[10px] font-800 px-2 py-0.5">
            Acil
          </span>
        ) : null}
      </div>
      <div className="p-2.5 sm:p-4">
        <div className="flex flex-wrap gap-1 mb-1.5">
          {featured ? (
            <span className="rounded-full bg-qyellow text-qblack text-[10px] font-800 px-2 py-0.5">
              Öne çıkan
            </span>
          ) : null}
          {conditionLabel ? (
            <span className="rounded-full bg-amber-50 text-amber-900 text-[10px] font-700 px-2 py-0.5 border border-amber-200">
              {conditionLabel}
            </span>
          ) : null}
          {item.seller_verified ? (
            <span className="inline-flex items-center gap-1 rounded-full bg-green-600/10 text-green-800 text-[10px] font-700 px-2 py-0.5 border border-green-600/20">
              Doğrulanmış satıcı
            </span>
          ) : null}
        </div>
        <h2 className="text-qblack font-700 text-xs sm:text-sm line-clamp-2 min-h-[32px] sm:min-h-[40px] mb-1">
          {item.title}
        </h2>
        {item.city?.name ? (
          <p className="text-[11px] sm:text-xs text-qgray mb-1">{item.city.name}</p>
        ) : null}
        <div className="text-sm sm:text-lg font-bold text-qblack">{formatTry(item.price)}</div>
      </div>
    </Link>
  );
}

export default function SecondHandListSection({ data, query = {}, filterCategories = [], homepage = null }) {
  const listings = data?.listings;
  const items = listings?.data || [];
  const conditionOptions = data?.condition_options || {};
  const currentPage = listings?.current_page || 1;
  const lastPage = listings?.last_page || 1;

  const hasAnyFilter = Object.entries(query).some(([k, v]) => {
    if (k === "page") return false;
    return v !== undefined && v !== null && String(v).trim() !== "";
  });

  const home = {
    title: homepage?.title || "Kuaför malzemeleri al/sat",
    subtitle:
      homepage?.subtitle ||
      "Doğrulanmış satıcılardan ikinci el ekipman. İlanlara herkes bakabilir; teklif ve mesaj için üye girişi gerekir.",
    cta_primary: homepage?.cta_primary || "İlan ver",
    cta_secondary: homepage?.cta_secondary || "İlanları gör",
    image: homepage?.image || null,
    show_categories: homepage?.show_categories !== false,
    show_featured: homepage?.show_featured !== false,
  };
  const sliders =
    Array.isArray(homepage?.sliders) && homepage.sliders.length > 0
      ? homepage.sliders
      : home.image
        ? [{ id: "home-image", image: home.image, title: home.title, subtitle: home.subtitle, link: "#ilanlar" }]
        : [];
  const isHome = !hasAnyFilter && Number(currentPage) === 1;
  const showFeaturedBlock = isHome && home.show_featured;
  const featuredItems = showFeaturedBlock ? items.filter((x) => !!x.is_featured) : [];
  const normalItems = showFeaturedBlock ? items.filter((x) => !x.is_featured) : items;

  const buildPageHref = (page) => {
    const params = new URLSearchParams();
    Object.entries(query).forEach(([k, v]) => {
      if (v !== undefined && v !== null && String(v) !== "") params.set(k, String(v));
    });
    if (page > 1) params.set("page", String(page));
    const qs = params.toString();
    return qs ? `/ikinci-el?${qs}` : "/ikinci-el";
  };

  return (
    <div className="w-full pb-16 pt-3 sm:pt-6">
      <div className="container-x mx-auto">
        <div className="mb-5 sm:mb-8">
          {sliders.length > 0 ? (
            <SecondHandHomeSlider slides={sliders} />
          ) : (
            <div className="relative overflow-hidden rounded-2xl md:rounded-3xl border border-amber-200/70 bg-gradient-to-br from-amber-50 via-white to-yellow-50 shadow-sm ring-1 ring-amber-900/5">
              <div className="relative px-4 py-6 sm:px-8 sm:py-10 md:px-10 md:py-12">
                <p className="text-[11px] font-800 uppercase tracking-[0.18em] text-amber-800/80">Seyfibaba İkinci El</p>
                <h1 className="mt-2 max-w-3xl text-2xl font-800 leading-tight text-qblack sm:text-4xl md:text-[42px]">
                  {home.title}
                </h1>
                {home.subtitle ? (
                  <p className="mt-2 max-w-2xl text-sm leading-relaxed text-qgray sm:text-base">
                    {home.subtitle}
                  </p>
                ) : null}
              </div>
            </div>
          )}

          <div className="mt-4 flex flex-wrap items-center gap-2">
            <Link
              href={marketplaceProfileUrl("second-hand-add")}
              className="h-10 px-4 w-full sm:w-auto inline-flex items-center justify-center rounded-xl bg-qyellow text-qblack text-sm font-800 shadow-sm ring-1 ring-amber-900/10 hover:brightness-95 transition"
            >
              {home.cta_primary}
            </Link>
            <a
              href="#ilanlar"
              className="h-10 px-4 w-full sm:w-auto inline-flex items-center justify-center rounded-xl border border-amber-200 bg-amber-50/80 text-qblack text-sm font-700 hover:bg-amber-100 transition"
            >
              {home.cta_secondary}
            </a>
          </div>

          {isHome && home.show_categories && Array.isArray(filterCategories) && filterCategories.length > 0 ? (
            <div className="mt-5">
              <p className="mb-2 sm:mb-3 text-sm font-800 text-qblack">Kategoriler</p>
              <div className="flex gap-2 overflow-x-auto pb-1 -mx-1 px-1">
                {filterCategories.slice(0, 16).map((cat) => (
                  <Link
                    key={cat.id}
                    href={`/ikinci-el?category_id=${encodeURIComponent(cat.id)}`}
                    className="shrink-0 inline-flex items-center rounded-full border border-gray-200 bg-white px-3 py-1.5 sm:px-4 sm:py-2 text-xs sm:text-sm font-700 text-qblack hover:border-amber-300 hover:bg-amber-50 transition"
                  >
                    {displayTurkishLabel(cat.name)}
                  </Link>
                ))}
              </div>
            </div>
          ) : null}
        </div>

        <div className="flex flex-col lg:flex-row lg:items-start gap-5 lg:gap-10">
          <aside className="w-full lg:w-72 shrink-0 lg:sticky lg:top-24 z-10">
            <div className="lg:hidden">
              <details className="rounded-xl border border-gray-200 bg-white" open={hasAnyFilter}>
                <summary className="cursor-pointer list-none px-4 py-3 text-sm font-800 text-qblack flex items-center justify-between">
                  Filtrele
                  <span className="text-xs font-600 text-qgray">{hasAnyFilter ? "Aktif" : "Aç"}</span>
                </summary>
                <div className="border-t border-gray-100 p-3">
                  <SecondHandListFilters
                    initialQuery={query}
                    conditionOptions={conditionOptions}
                    categories={filterCategories}
                  />
                </div>
              </details>
            </div>
            <div className="hidden lg:block">
              <SecondHandListFilters
                initialQuery={query}
                conditionOptions={conditionOptions}
                categories={filterCategories}
              />
            </div>
          </aside>

          <div className="min-w-0 flex-1">
            <div id="ilanlar" className="flex items-center justify-between gap-3 mb-4 scroll-mt-24">
              <div>
                <h2 className="text-lg font-700 text-qblack">İlanlar</h2>
                <p className="text-xs text-qgray">
                  {items.length > 0 ? `${items.length} sonuç` : hasAnyFilter ? "Sonuç bulunamadı" : "Henüz ilan yok"}
                </p>
              </div>
              {hasAnyFilter ? (
                <Link href="/ikinci-el" className="text-sm font-700 text-qblack underline">
                  Filtreleri temizle
                </Link>
              ) : null}
            </div>
            {items.length === 0 ? (
              <div className="py-14 text-center text-qgray border border-dashed border-gray-200 rounded-2xl bg-gray-50/40">
                <p className="text-sm font-700 text-qblack">İlan bulunamadı</p>
                <p className="mt-1 text-sm text-qgray">
                  {hasAnyFilter ? "Filtreleri değiştirip tekrar deneyin." : "Yakında yeni ilanlar eklenecek."}
                </p>
              </div>
            ) : (
              <>
                {showFeaturedBlock ? (
                  <SecondHandForYouBlock enabled={true} conditionOptions={conditionOptions} />
                ) : null}
                {featuredItems.length > 0 && (
                  <div className="mb-6">
                    <div className="flex items-center justify-between mb-3">
                      <h3 className="text-sm font-800 text-qblack">Öne çıkan ilanlar</h3>
                      <span className="text-xs text-qgray">Admin seçimi</span>
                    </div>
                    <div className="grid grid-cols-2 lg:grid-cols-3 gap-2.5 sm:gap-5">
                      {featuredItems.map((item) => (
                        <ListingCard
                          key={item.id}
                          item={item}
                          featured
                          conditionLabel={conditionOptions[item.condition] || item.condition}
                        />
                      ))}
                    </div>
                  </div>
                )}

                <div className="grid grid-cols-2 lg:grid-cols-3 gap-2.5 sm:gap-5">
                  {normalItems.map((item) => (
                    <ListingCard
                      key={item.id}
                      item={item}
                      conditionLabel={conditionOptions[item.condition] || item.condition}
                    />
                  ))}
                </div>
              </>
            )}

            {lastPage > 1 && (
              <div className="mt-10 flex justify-center items-center gap-4">
                {currentPage > 1 ? (
                  <Link
                    href={buildPageHref(currentPage - 1)}
                    className="px-4 py-2 border border-qgray-border rounded-md text-sm hover:bg-gray-50"
                  >
                    Önceki
                  </Link>
                ) : (
                  <span className="px-4 py-2 text-qgray text-sm opacity-50">Önceki</span>
                )}
                <span className="text-sm text-qgray">
                  Sayfa {currentPage} / {lastPage}
                </span>
                {currentPage < lastPage ? (
                  <Link
                    href={buildPageHref(currentPage + 1)}
                    className="px-4 py-2 border border-qgray-border rounded-md text-sm hover:bg-gray-50"
                  >
                    Sonraki
                  </Link>
                ) : (
                  <span className="px-4 py-2 text-qgray text-sm opacity-50">Sonraki</span>
                )}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
