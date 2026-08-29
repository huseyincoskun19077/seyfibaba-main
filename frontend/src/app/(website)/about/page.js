import about from "@/api/about";
import About from "@/components/About";
import JsonLd, { generateSpeakableSchema } from "@/components/Helpers/JsonLd";
import appConfig from "@/appConfig";
import { cache } from "react";

export const revalidate = 300;

export const getAboutData = cache(async () => {
  return await about();
});

// generate seo metadata
export async function generateMetadata() {
  const data = await getAboutData();
  const seoSetting = data?.seoSetting;
  return {
    title: seoSetting?.seo_title || "Hakkımızda",
    description:
      seoSetting?.seo_description ||
      "Seyfibaba'nin berber ve kuafor profesyonelleri icin kurdugu pazaryeri yapisini, hizmet alanlarini ve operasyon anlayisini kesfedin.",
    alternates: {
      canonical: "/about",
    },
  };
}

// main page
export default async function aboutPage() {
  const data = await getAboutData();
  const speakableSchema = generateSpeakableSchema({
    url: `${appConfig.APPLICATION_URL}/about`,
    cssSelectors: ["#about-editorial-content", "#about-mission-heading"],
  });

  return (
    <>
      {speakableSchema && <JsonLd data={speakableSchema} />}
      <About aboutData={data} />
    </>
  );
}
