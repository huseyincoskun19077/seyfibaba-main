import SellerLanding from "@/components/SellerLanding";

export async function generateMetadata() {
  return {
    title: "Satıcı Merkezi | Kuaför Tedarik",
    description:
      "Kuaför Tedarik satıcı merkezi: komisyon, hakediş, ürün yükleme, kargo ve başvuru. Kurumsal satıcı rehberi ve SSS.",
    alternates: {
      canonical: "/satici",
    },
    openGraph: {
      title: "Kuaför Tedarik Satıcı Merkezi",
      description:
        "Kuaför, berber ve güzellik sektörüne satış için başvuru, rehber ve SSS.",
      url: "/satici",
      type: "website",
    },
  };
}

export default function SaticiLandingPage() {
  return <SellerLanding />;
}
