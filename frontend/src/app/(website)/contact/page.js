import contact from "@/api/contact";
import Contact from "@/components/Contact";
import { cache } from "react";

export const dynamic = 'force-dynamic'; // Static export için gerekli

export const getContactData = cache(async () => {
  return await contact();
});

// generate seo metadata
export async function generateMetadata() {
  const data = await getContactData();
  const { seoSetting } = data;
  return {
    title: seoSetting?.seo_title || "İletişim",
    description: seoSetting?.seo_description,
    alternates: {
      canonical: "/contact",
    },
  };
}

// main page
export default async function contactPage() {
  const data = await getContactData();
  return <Contact datas={data} />;
}
