import SellerLanding from "@/components/SellerLanding";

export async function generateMetadata() {
  return {
    title: "Satıcı Ol | Seyfibaba'da Satışa Başlayın",
    description:
      "Kuaför, berber ve güzellik sektörüne ürün satıyorsanız Seyfibaba'da mağaza açın. Türkiye geneli görünürlük, ürün yükleme desteği, WhatsApp bilgi hattı ve şeffaf komisyon.",
    alternates: {
      canonical: "/satici",
    },
    openGraph: {
      title: "Seyfibaba'da Satışa Başlayın",
      description:
        "Ürünlerinizi Türkiye genelindeki kuaför, berber ve güzellik salonlarıyla buluşturun. Kayıt olun veya WhatsApp ile bilgi alın.",
      url: "/satici",
      type: "website",
    },
  };
}

export default function SaticiLandingPage() {
  return <SellerLanding />;
}
