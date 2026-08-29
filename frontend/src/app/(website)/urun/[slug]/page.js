import { cache } from "react";
import appConfig from "@/appConfig";
import { resolveProductImageUrl } from "@/utils/productImage";
import getProductDetails from "@/api/getProductDetails";
import JsonLd, {
  generateProductSchema,
} from "@/components/Helpers/JsonLd";
import ClientProductPage from "./ClientProductPage";
import {
  buildProductPath,
  buildProductUrl,
  normalizeProductSlug,
} from "@/utils/url";
import { notFound, permanentRedirect } from "next/navigation";

export const dynamic = "force-dynamic";

const getProductDetailsData = cache(async (slug) => getProductDetails(slug));

export async function generateMetadata({ params }) {
  try {
    const { slug } = await params;
    const data = await getProductDetailsData(slug);
    const product = data?.product;
    const canonicalSlug = normalizeProductSlug(product?.slug || slug) || slug;
    const title = product?.seo_title || product?.name || "Ürün";
    const description = product?.seo_description || product?.short_description || "";
    const imageUrl = product?.thumb_image ? resolveProductImageUrl(product.thumb_image) : null;

    return {
      title,
      description,
      openGraph: {
        type: "website",
        siteName: "Seyfibaba",
        title,
        description,
        url: buildProductUrl(appConfig.APPLICATION_URL, canonicalSlug),
        images: imageUrl
          ? [
              {
                url: imageUrl,
                width: 800,
                height: 800,
                alt: product?.name || title,
              },
            ]
          : [],
      },
      twitter: {
        card: "summary_large_image",
        title,
        description,
        images: imageUrl ? [imageUrl] : [],
      },
      alternates: {
        canonical: buildProductPath(canonicalSlug),
      },
    };
  } catch {
    return { title: "Ürün" };
  }
}

export default async function ProductDetailsPage({ params }) {
  const { slug } = await params;
  let data;
  try {
    data = await getProductDetailsData(slug);
  } catch {
    notFound();
  }
  if (!data?.product?.id) {
    notFound();
  }
  const canonicalSlug = normalizeProductSlug(data?.product?.slug || slug);

  if (canonicalSlug && canonicalSlug !== slug) {
    permanentRedirect(buildProductPath(canonicalSlug));
  }

  let productSchema = null;
  try {
    productSchema = generateProductSchema(data);
  } catch {
    productSchema = null;
  }

  return (
    <>
      {productSchema ? <JsonLd data={productSchema} /> : null}
      <ClientProductPage details={data} />
    </>
  );
}
