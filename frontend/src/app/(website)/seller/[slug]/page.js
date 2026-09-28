import sellerDetails from "@/api/sellerDetails";
import AllProductPage from "@/components/AllProductPage";
import { buildPageTitle } from "@/utils/pageTitle";
import { cache } from "react";

export const revalidate = 60;

const getSellerData = cache(async (slug, query) => {
  return await sellerDetails(slug, query);
});

export async function generateMetadata({ params, searchParams }) {
  const { slug } = await params;
  const query = (await searchParams) || {};
  const data = await getSellerData(slug, query);
  const shopName = data?.seller?.shop_name || "Satıcı";

  return {
    title: buildPageTitle(data?.seller?.seo_title || shopName),
    description:
      data?.seller?.seo_description ||
      `${shopName} mağazası — kuaför, berber ve güzellik salonu malzemeleri.`,
    alternates: {
      canonical: `/seller/${slug}`,
    },
  };
}

export default async function SellerPage({ params, searchParams }) {
  const { slug } = await params;
  const query = (await searchParams) || {};
  const data = await getSellerData(slug, query);
  const shopName = data?.seller?.shop_name || "Satıcı";

  return (
    <AllProductPage
      response={data}
      sellerInfo={data}
      listingTitle={shopName}
    />
  );
}
