import { redirect } from "next/navigation";

export async function generateMetadata() {
  return {
    title: "Satıcı Ol",
    description: "Kuaför Tedarik pazaryerinde satıcı olun. Ürünlerinizi binlerce müşteriye ulaştırın.",
    alternates: {
      canonical: "/satici-kayit",
    },
  };
}

export default function BecomeSellerRedirectPage() {
  redirect("/satici-kayit");
}
