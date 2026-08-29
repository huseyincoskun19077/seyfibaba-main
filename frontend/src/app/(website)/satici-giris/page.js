import SellerLogin from "../../../components/Auth/SellerLogin";

export const dynamic = "force-dynamic";

export async function generateMetadata() {
  return {
    title: "Satıcı Girişi",
    description:
      "Seyfibaba satıcı paneline giriş yapın. Telefon veya e-posta ile mağazanızı yönetin.",
    alternates: {
      canonical: "/satici-giris",
    },
    robots: { index: false, follow: true },
  };
}

export default async function SellerLoginPage() {
  return <SellerLogin />;
}
