import { redirect } from "next/navigation";

export async function generateMetadata() {
  return {
    title: "Satıcı Ol",
    description: "Seyfibaba pazaryerinde satıcı olun. Ürünlerinizi binlerce müşteriye ulaştırın.",
    alternates: {
      canonical: "/satici-kayit",
    },
  };
}

export default function BecomeSellerRedirectPage() {
  redirect("/satici-kayit");
}
