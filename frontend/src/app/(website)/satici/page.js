import SellerLanding from "@/components/SellerLanding";

export async function generateMetadata() {
  return {
    title: "Satıcı Ol | Seyfibaba Pazaryeri",
    description:
      "Kuaför, berber ve güzellik malzemesi satıyorsanız Seyfibaba'da mağaza açın. Hızlı üyelik ve satıcı girişi.",
    alternates: {
      canonical: "/satici",
    },
    openGraph: {
      title: "Seyfibaba'da Satıcı Olun",
      description:
        "Sektörel pazaryerinde mağazanızı açın. Üye olun veya satıcı girişi yapın.",
      url: "/satici",
      type: "website",
    },
  };
}

export default function SaticiLandingPage() {
  return <SellerLanding />;
}
