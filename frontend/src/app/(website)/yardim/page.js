import { Suspense } from "react";
import { cache } from "react";
import faq from "@/api/faq";
import HelpCenter from "@/components/HelpCenter";
import JsonLd, { generateFAQSchema } from "@/components/Helpers/JsonLd";
import appConfig from "@/appConfig";

export const dynamic = "force-dynamic";

export const getFaqData = cache(async () => {
  return await faq();
});

export async function generateMetadata() {
  return {
    title: "Yardım Merkezi | Kuaför Tedarik",
    description:
      "Sipariş, iade, kargo, hesap ve alışveriş sorularınızın cevapları. Kuaför Tedarik yardım merkezinden destek talebi oluşturun.",
    alternates: {
      canonical: "/yardim",
    },
    openGraph: {
      title: "Yardım Merkezi | Kuaför Tedarik",
      description:
        "Sana nasıl yardımcı olabiliriz? SSS ve destek talebi tek yerde.",
      url: `${appConfig.APPLICATION_URL || "https://kuafortedarik.com"}/yardim`,
      type: "website",
    },
  };
}

export default async function YardimPage() {
  const data = await getFaqData();
  const faqs = Array.isArray(data?.faqs) ? data.faqs : [];

  const schemaFaqs = faqs.map((item) => ({
    question: item.question,
    answer: item.answer || item.ans || "",
  }));
  const faqSchema = generateFAQSchema(schemaFaqs);

  return (
    <>
      {schemaFaqs.length ? <JsonLd data={faqSchema} /> : null}
      <Suspense
        fallback={
          <div className="container-x mx-auto py-16 text-center text-sm text-[#04334a]/50">
            Yükleniyor…
          </div>
        }
      >
        <HelpCenter faqs={faqs} />
      </Suspense>
    </>
  );
}
