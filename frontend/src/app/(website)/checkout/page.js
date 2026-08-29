import CheakoutPage from "@/components/CheckoutPage";

export const dynamic = 'force-dynamic'; // Static export için gerekli

// generate seo metadata
export async function generateMetadata() {
  return {
    title: "Sipariş Tamamla",
    description: "Siparişinizi güvenle tamamlayın. Adres ve ödeme bilgilerinizi girin.",
    alternates: {
      canonical: "/checkout",
    },
    robots: { index: false, follow: true },
  };
}

// main page
export default async function CheckoutPage() {
  return <CheakoutPage />;
}
