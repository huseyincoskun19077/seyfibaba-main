import CardPage from "@/components/CartPage/index";

export const dynamic = 'force-dynamic';

// generate seo metadata
export async function generateMetadata() {
  return {
    title: "Sepetim",
    description: "Alışveriş sepetinizi görüntüleyin, ürünleri düzenleyin ve siparişinizi tamamlayın.",
    alternates: {
      canonical: "/cart",
    },
    robots: { index: false, follow: true },
  };
}
// main page
export default async function CartPage() {
  return <CardPage />;
}
