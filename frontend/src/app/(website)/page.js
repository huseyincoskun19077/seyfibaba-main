import { cache } from "react";
import home from "@/api/home";
import Home from "@/components/Home";

export const revalidate = 60;

// api data cache for reducing multiple request to the api
export const getHomeData = cache(async () => {
  return await home();
});

// Static metadata — ensures description lands in <head> for SEO
export const metadata = {
  title: "Berber ve Kuaför Malzemeleri",
  description: "Berber malzemeleri, kuaför ekipmanları, berber koltuğu ve salon mobilyaları. Profesyoneller için en uygun fiyatlı alışveriş sitesi.",
  alternates: {
    canonical: "/",
  },
};

import JsonLd, {
  generateOrganizationSchema,
  generateSpeakableSchema,
  generateStoreSchema,
  generateWebSiteSchema,
} from "@/components/Helpers/JsonLd";
import appConfig from "@/appConfig";
import { resolveProductImageUrl } from "@/utils/productImage";

// main page
export default async function HomePage() {
  const data = await getHomeData();
  const orgSchema = generateOrganizationSchema();
  const websiteSchema = generateWebSiteSchema();
  const storeSchema = generateStoreSchema();
  const speakableSchema = generateSpeakableSchema({
    url: appConfig.APPLICATION_URL,
    cssSelectors: [
      "#home-geo-headline",
      "#home-geo-intro",
      "#home-geo-intro-secondary",
    ],
  });
  const homeSchemaGraph = {
    "@context": "https://schema.org",
    "@graph": [orgSchema, websiteSchema, storeSchema, speakableSchema].filter(Boolean),
  };

  // Preload LCP hero image — must match the exact URL that Next.js <Image> will request
  const firstSliderImage = data?.sliders?.[0]?.image;
  const heroPreloadUrl = firstSliderImage
    ? resolveProductImageUrl(firstSliderImage)
    : null;

  return (
    <>
      {heroPreloadUrl && (
        <link
          rel="preload"
          as="image"
          href={heroPreloadUrl}
          fetchPriority="high"
        />
      )}
      <JsonLd data={homeSchemaGraph} />
      <Home homepageData={data} />
    </>
  );
}
