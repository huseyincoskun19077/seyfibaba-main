import React, { Suspense } from "react";
import { notFound } from "next/navigation";
import CustomPageCom from "../../../components/CustomPageCom";
import apiRoutes from "@/appConfig/apiRoutes";

export const dynamic = "force-dynamic";

const DEFAULT_LANGUAGE_CODE = "tr";

/**
 * Generate metadata for the custom page from website setup data
 */
export async function generateMetadata({ params }) {
  const { slug } = await params;
  
  try {
    const response = await fetch(
      `${apiRoutes.websiteSetup}?lang_code=${DEFAULT_LANGUAGE_CODE}`,
      { next: { revalidate: 3600 } }
    );
    const data = await response.json();
    
    const page = data?.customPages?.find((item) => item.slug === slug);
    
    if (page) {
      return {
        title: page.page_name,
        description: page.seo_description || page.page_name,
        alternates: {
          canonical: `/${slug}`,
        },
        openGraph: {
          title: page.page_name,
          description: page.seo_description || page.page_name,
        },
      };
    }
  } catch (error) {
    // metadata generation failed silently
  }

  return {
    title: "Page",
    alternates: {
      canonical: `/${slug}`,
    },
  };
}

// Simple wrapper for CustomPageCom
function PageWrapContent({ slug }) {
  return (
    <>
      <CustomPageCom slug={slug} />
    </>
  );
}

// Server Component (Default Export)
export default async function PageWrap({ params }) {
  const { slug } = await params;

  // Check if custom page exists, otherwise 404
  const reserved = new Set([
    "salon-takvim",
    "salon-crm",
    "ikinci-el",
    "urun",
    "kategori",
  ]);
  if (reserved.has(slug)) {
    notFound();
  }
  try {
    const response = await fetch(
      `${apiRoutes.websiteSetup}?lang_code=${DEFAULT_LANGUAGE_CODE}`,
      { next: { revalidate: 3600 } }
    );
    const data = await response.json();
    const page = data?.customPages?.find((item) => item.slug === slug);
    if (!page) {
      notFound();
    }
  } catch {
    notFound();
  }

  return (
    <Suspense
      fallback={
        <div className="w-full h-screen flex justify-center items-center">
          <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-gray-900"></div>
        </div>
      }
    >
      <PageWrapContent slug={slug} />
    </Suspense>
  );
}
