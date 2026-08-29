import SalonCrmPromo from "@/components/SalonCrmPromo";

export const metadata = {
  title: "Salon CRM",
  description:
    "Seyfibaba Salon CRM ile randevu, personel, kasa ve müşteri takibini mobil uygulamadan yönetin. Kuaför ve berber salonları için ücretsiz tanıtım.",
  alternates: {
    canonical: "/salon-crm",
  },
};

export default function SalonCrmPage() {
  return <SalonCrmPromo />;
}
