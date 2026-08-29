import faq from "@/api/faq";
import FaqComponent from "@/components/Faq";
import { cache } from "react";
import JsonLd, { generateFAQSchema } from "@/components/Helpers/JsonLd";

export const dynamic = 'force-dynamic'; // Static export için gerekli

export const getFaqData = cache(async () => {
  return await faq();
});

// generate seo metadata
export async function generateMetadata() {
  const data = await getFaqData();
  const { seoSetting } = data;
  return {
    title: seoSetting?.seo_title || "Sıkça Sorulan Sorular",
    description: seoSetting?.seo_description,
    alternates: {
      canonical: "/faq",
    },
  };
}

// main page
export default async function FaqPage() {
  const data = await getFaqData();
  
  const faqs = data?.faqs?.map(item => ({
    question: item.question,
    answer: item.answer
  })) || [];
  
  const faqSchema = generateFAQSchema(faqs);
  return (
    <>
      <JsonLd data={faqSchema} />
      <FaqComponent datas={data} />
    </>
  );
}
