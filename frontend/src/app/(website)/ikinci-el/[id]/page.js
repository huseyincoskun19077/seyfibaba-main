import { cache } from "react";
import { fetchSecondHandListing, getSecondHandListingIdFromParam, getSecondHandListingSeoPath } from "@/api/secondHandPublic";
import SecondHandDetailSection from "@/components/SecondHand/SecondHandDetailSection";
import { secondHandPublicOrigin } from "@/utils/secondHandSite";
import apiRoutes from "@/appConfig/apiRoutes";
import Link from "next/link";

export const revalidate = 120;

const getListing = cache(async (id) => fetchSecondHandListing(id));

async function safeGetListing(id) {
  try {
    return await getListing(id);
  } catch {
    return null;
  }
}

export async function generateMetadata({ params }) {
  const { id } = await params;
  const listingId = getSecondHandListingIdFromParam(id);
  const data = listingId ? await safeGetListing(listingId) : null;
  const title = data?.listing?.title;
  const desc = data?.listing?.description;
  const base = secondHandPublicOrigin();
  const seoPath = data?.listing ? getSecondHandListingSeoPath(data.listing) : (listingId || String(id || ""));
  const url = `${base}/ikinci-el/${seoPath}`;
  const images = data?.listing?.images || [];
  const firstImageId = images?.[0]?.id;
  const ogImage = firstImageId ? `${apiRoutes.secondHandListingImage}${firstImageId}` : null;

  let metadataBase;
  try {
    metadataBase = new URL(base);
  } catch {
    metadataBase = new URL(secondHandPublicOrigin());
  }

  return {
    metadataBase,
    title: title ? `${title} | İkinci El` : "İkinci El İlanı",
    description: typeof desc === "string" && desc.length > 0 ? desc.slice(0, 160) : "İkinci el ilan detayı",
    alternates: {
      canonical: url,
    },
    openGraph: {
      // Next.js metadata validator does not accept "product" as OpenGraph type.
      type: "website",
      url,
      title: title ? `${title} | İkinci El` : "İkinci El İlanı",
      description: typeof desc === "string" && desc.length > 0 ? desc.slice(0, 160) : "İkinci el ilan detayı",
      siteName: "Seyfibaba",
      locale: "tr_TR",
      images: ogImage ? [{ url: ogImage }] : undefined,
    },
    twitter: {
      card: ogImage ? "summary_large_image" : "summary",
      title: title ? `${title} | İkinci El` : "İkinci El İlanı",
      description: typeof desc === "string" && desc.length > 0 ? desc.slice(0, 160) : "İkinci el ilan detayı",
      images: ogImage ? [ogImage] : undefined,
    },
  };
}

export default async function IkinciElDetailPage({ params }) {
  const { id } = await params;
  const listingId = getSecondHandListingIdFromParam(id);
  const data = listingId ? await safeGetListing(listingId) : null;

  if (!data?.listing) {
    return (
      <div className="container-x mx-auto py-16">
        <div className="max-w-xl mx-auto rounded-xl border border-gray-200 bg-white p-6 text-center">
          <h1 className="text-xl font-700 text-qblack mb-2">İlan görüntülenemiyor</h1>
          <p className="text-sm text-qgray mb-5">
            Bu ilan henüz yayında olmayabilir, kaldırılmış olabilir veya bağlantı geçersizdir.
          </p>
          <Link href="/ikinci-el" className="inline-flex items-center justify-center px-4 py-2 rounded-md bg-qyellow text-qblack font-700">
            İkinci El ilanlara dön
          </Link>
        </div>
      </div>
    );
  }

  return <SecondHandDetailSection data={data} />;
}
