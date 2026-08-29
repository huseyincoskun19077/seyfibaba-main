import { notFound } from "next/navigation";

export async function generateMetadata() {
  return {
    title: "Sayfa Bulunamadı | Seyfibaba",
    robots: { index: false, follow: false },
  };
}

export default function SellersAliasPage() {
  notFound();
}
