import getSellers from "@/api/getSellers";
import Sallers from "@/components/Sellers";
import { buildPageTitle } from "@/utils/pageTitle";
import { notFound } from "next/navigation";

export const revalidate = 300;

export async function generateMetadata() {
  return {
    title: buildPageTitle("Satıcılar"),
    description:
      "Kuaför Tedarik onaylı satıcıları — kuaför, berber ve güzellik salonu malzemeleri.",
    alternates: {
      canonical: "/sellers",
    },
  };
}

export default async function SellersPage() {
  const data = await getSellers();
  if (!data?.sellers) {
    notFound();
  }

  const sellersData = Array.isArray(data.sellers?.data)
    ? data.sellers.data
    : Array.isArray(data.sellers)
      ? data.sellers
      : [];

  return <Sallers sellersData={sellersData} />;
}
