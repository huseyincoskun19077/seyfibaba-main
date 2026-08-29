import getLegalDocument from "@/api/getLegalDocument";
import LegalDocumentPage from "@/components/Legal/LegalDocumentPage";
import { LEGAL_SLUG_LIST } from "@/config/legalDocuments";
import { notFound } from "next/navigation";
import { cache } from "react";

export const dynamic = "force-dynamic";

const getDocument = cache(async (slug) => getLegalDocument(slug));

export async function generateStaticParams() {
  return LEGAL_SLUG_LIST.map((slug) => ({ slug }));
}

export async function generateMetadata({ params }) {
  const { slug } = await params;
  const document = await getDocument(slug);

  if (!document) {
    return {
      title: "Yasal Belge | Seyfibaba",
      robots: { index: false, follow: false },
    };
  }

  return {
    title: document.meta_title || `${document.title} | Seyfibaba`,
    description: document.meta_description || `${document.title} — Seyfibaba`,
    alternates: {
      canonical: `/legal/${slug}`,
    },
    openGraph: {
      title: document.meta_title || document.title,
      description: document.meta_description,
      url: `https://seyfibaba.com/legal/${slug}`,
    },
  };
}

export default async function LegalDocumentRoutePage({ params }) {
  const { slug } = await params;
  const document = await getDocument(slug);

  if (!document) {
    notFound();
  }

  return <LegalDocumentPage document={document} />;
}
