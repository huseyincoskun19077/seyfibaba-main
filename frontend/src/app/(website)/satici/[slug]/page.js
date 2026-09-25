import { notFound } from "next/navigation";
import SellerInfoPage from "@/components/SellerInfoPage";
import {
  getSellerInfoPage,
  getSellerInfoSlugs,
} from "@/data/sellerInfoPages";
import JsonLd from "@/components/Helpers/JsonLd";
import appConfig from "@/appConfig";

export function generateStaticParams() {
  return getSellerInfoSlugs().map((slug) => ({ slug }));
}

export async function generateMetadata({ params }) {
  const { slug } = await params;
  const page = getSellerInfoPage(slug);
  if (!page) {
    return { title: "Sayfa bulunamadı" };
  }

  const path = `/satici/${page.slug}`;
  return {
    title: page.title,
    description: page.description,
    alternates: {
      canonical: path,
    },
    openGraph: {
      title: page.title,
      description: page.description,
      url: path,
      type: "article",
    },
  };
}

export default async function SaticiInfoSlugPage({ params }) {
  const { slug } = await params;
  const page = getSellerInfoPage(slug);
  if (!page) notFound();

  const base = appConfig.APPLICATION_URL || "https://kuafortedarik.com";
  const url = `${base}/satici/${page.slug}`;

  const breadcrumbSchema = {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    itemListElement: [
      {
        "@type": "ListItem",
        position: 1,
        name: "Ana Sayfa",
        item: base,
      },
      {
        "@type": "ListItem",
        position: 2,
        name: "Satıcı Ol",
        item: `${base}/satici`,
      },
      {
        "@type": "ListItem",
        position: 3,
        name: page.h1,
        item: url,
      },
    ],
  };

  const articleSchema = {
    "@context": "https://schema.org",
    "@type": "Article",
    headline: page.h1,
    description: page.description,
    mainEntityOfPage: url,
    author: {
      "@type": "Organization",
      name: "Kuaför Tedarik",
    },
    publisher: {
      "@type": "Organization",
      name: "Kuaför Tedarik",
      url: base,
    },
  };

  return (
    <>
      <JsonLd data={breadcrumbSchema} />
      <JsonLd data={articleSchema} />
      <SellerInfoPage page={page} />
    </>
  );
}
