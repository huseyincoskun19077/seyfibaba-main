import SellerQuickRegister from "@/components/SellerQuickRegister";

export async function generateMetadata() {
  return {
    title: "Satıcı Ol | Hızlı Kayıt",
    description:
      "Seyfibaba'da hızlı satıcı kaydı oluşturun. SMS ile gelen şifreyle satıcı paneline giriş yapın.",
    alternates: {
      canonical: "/satici-kayit",
    },
  };
}

export default function SaticiKayitPage() {
  return <SellerQuickRegister />;
}
