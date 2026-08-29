import Link from "next/link";
import apiRoutes from "@/appConfig/apiRoutes";
import { getSecondHandListingSeoPath } from "@/api/secondHandPublic";
import SecondHandMessageToSeller from "./SecondHandMessageToSeller";
import SecondHandReportListing from "./SecondHandReportListing";
import SecondHandTrackInterest from "./SecondHandTrackInterest";

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

export default function SecondHandDetailSection({ data }) {
  const listing = data?.listing;
  const conditionOptions = data?.condition_options || {};
  const similar = data?.similar_listings || [];
  if (!listing) return null;

  const images = listing.images || [];
  const conditionLabel = conditionOptions[listing.condition] || listing.condition;

  return (
    <div className="w-full pb-16 pt-4 sm:pt-8">
      <SecondHandTrackInterest listing={listing} />
      <div className="container-x mx-auto">
        <nav className="text-sm text-qgray mb-6">
          <Link href="/" className="hover:text-qyellow">
            Anasayfa
          </Link>
          <span className="mx-2">/</span>
          <Link href="/ikinci-el" className="hover:text-qyellow">
            İkinci El
          </Link>
          <span className="mx-2">/</span>
          <span className="text-qblack">#{listing.id}</span>
        </nav>

        <div className="grid lg:grid-cols-2 gap-8 lg:gap-12">
          <div>
            {images.length > 0 ? (
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                {images.map((img, idx) => {
                  const src = imageSrc(img.id);
                  return (
                    <div
                      key={img.id}
                      className={`${idx === 0 ? "sm:col-span-2" : ""} aspect-[4/3] sm:aspect-square bg-[#f4f4f6] rounded-xl overflow-hidden`}
                    >
                      {src ? (
                        // eslint-disable-next-line @next/next/no-img-element
                        <img src={src} alt="" className="w-full h-full object-contain" />
                      ) : null}
                    </div>
                  );
                })}
              </div>
            ) : (
              <div className="aspect-square bg-qgray-border rounded-lg flex items-center justify-center text-qgray">
                Görsel yok
              </div>
            )}
          </div>

          <div>
            <h1 className="text-xl sm:text-2xl md:text-3xl font-bold text-qblack mb-3 sm:mb-4">{listing.title}</h1>
            <div className="text-2xl sm:text-3xl font-bold text-qblack mb-4 sm:mb-6">{formatTry(listing.price)}</div>

            <div className="flex flex-wrap gap-2 mb-6 text-sm">
              <span className="px-3 py-1 rounded-full bg-gray-100 text-qblack">{conditionLabel}</span>
              {listing.city?.name && (
                <span className="px-3 py-1 rounded-full bg-gray-100 text-qblack">{listing.city.name}</span>
              )}
              {listing.province && (
                <span className="px-3 py-1 rounded-full bg-gray-100 text-qblack">İl: {listing.province}</span>
              )}
              {listing.district && listing.province && (
                <span className="px-3 py-1 rounded-full bg-gray-100 text-qblack">İlçe: {listing.district}</span>
              )}
              {listing.district && !listing.province && (
                <span className="px-3 py-1 rounded-full bg-gray-100 text-qblack">{listing.district}</span>
              )}
              {listing.views_count != null && (
                <span className="px-3 py-1 rounded-full bg-gray-100 text-qgray">
                  {listing.views_count} görüntülenme
                </span>
              )}
            </div>

            {(listing.seller_business_name || listing.seller_verified) && (
              <p className="text-sm text-qgray mb-6 flex items-center gap-2 flex-wrap">
                {listing.seller_business_name ? (
                  <span>
                    İş yeri adı: <span className="text-qblack font-500">{listing.seller_business_name}</span>
                  </span>
                ) : null}
                {listing.seller_verified ? (
                  <span className="inline-flex items-center gap-1 rounded-full bg-green-600/10 text-green-800 text-[11px] font-700 px-2 py-0.5 border border-green-600/20">
                    <span className="w-2 h-2 rounded-full bg-green-600" aria-hidden />
                    Doğrulanmış
                  </span>
                ) : null}
                {listing.seller_c2c_listings_active != null ? (
                  <span className="inline-flex items-center rounded-full bg-gray-100 text-qblack text-[11px] font-700 px-2 py-0.5 border border-gray-200">
                    Aktif ilan: {Number(listing.seller_c2c_listings_active) || 0}
                  </span>
                ) : null}
                {listing.seller_c2c_listings_total != null ? (
                  <span className="inline-flex items-center rounded-full bg-gray-100 text-qblack text-[11px] font-700 px-2 py-0.5 border border-gray-200">
                    Toplam ilan: {Number(listing.seller_c2c_listings_total) || 0}
                  </span>
                ) : null}
              </p>
            )}

            {listing.description && (
              <div className="prose prose-sm max-w-none text-qblack border-t border-qgray-border pt-6">
                <h2 className="text-lg font-600 mb-2">Açıklama</h2>
                <div className="whitespace-pre-wrap text-sm leading-relaxed">{listing.description}</div>
              </div>
            )}

            <SecondHandReportListing listingId={listing.id} />
            <SecondHandMessageToSeller listingId={listing.id} sellerUserId={listing.user?.id} />
          </div>
        </div>

        {Array.isArray(similar) && similar.length > 0 ? (
          <div className="mt-12">
            <div className="flex items-end justify-between gap-3 mb-4">
              <h2 className="text-lg font-800 text-qblack">Benzer ilanlar</h2>
              <Link href="/ikinci-el?sort=new" className="text-xs text-qgray underline hover:text-qblack">
                Yeni düşenlere bak
              </Link>
            </div>
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
              {similar.slice(0, 8).map((it) => {
                const thumbId = it?.images?.[0]?.id;
                const thumb = thumbId ? imageSrc(thumbId) : null;
                return (
                  <Link
                    key={it.id}
                    href={`/ikinci-el/${getSecondHandListingSeoPath(it)}`}
                    className="group rounded-xl border border-gray-200 bg-white overflow-hidden hover:shadow-sm transition"
                  >
                    <div className="aspect-square bg-gray-50">
                      {thumb ? (
                        // eslint-disable-next-line @next/next/no-img-element
                        <img src={thumb} alt="" className="w-full h-full object-cover group-hover:scale-[1.02] transition-transform" />
                      ) : (
                        <div className="w-full h-full flex items-center justify-center text-xs text-qgray">Görsel yok</div>
                      )}
                    </div>
                    <div className="p-3">
                      <div className="text-xs text-qgray truncate">{it.city?.name || ""}</div>
                      <div className="text-sm font-700 text-qblack truncate">{it.title || "İlan"}</div>
                      <div className="text-sm font-900 text-qblack mt-1">{formatTry(it.price)}</div>
                    </div>
                  </Link>
                );
              })}
            </div>
          </div>
        ) : null}
      </div>
    </div>
  );
}
