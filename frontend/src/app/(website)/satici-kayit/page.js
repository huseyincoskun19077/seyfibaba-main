import SellerQuickRegister from "@/components/SellerQuickRegister";

export async function generateMetadata() {
  return {
    title: "Satıcı Olmak İçin Başvurun | Kuaför Tedarik",
    description:
      "Kuaför Tedarik satıcı başvuru formu. Firma bilgilerinizi doldurun, yasal metinleri onaylayın ve satıcı paneline başlayın.",
    alternates: {
      canonical: "/satici-kayit",
    },
  };
}

export default function SaticiKayitPage() {
  return <SellerQuickRegister />;
}
