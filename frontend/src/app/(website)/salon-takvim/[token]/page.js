import SalonCalendarClient from "@/components/SalonCrm/SalonCalendarClient";

export const dynamic = "force-dynamic";
export const dynamicParams = true;

export async function generateMetadata({ params }) {
  const { token } = await params;
  return {
    title: "Salon takvimi | Seyfibaba",
    description:
      "Kuaför ve güzellik salonu dolu saatlerini canlı takip edin. Randevu için Seyfibaba uygulamasını indirin.",
    robots: { index: false, follow: false },
    alternates: {
      canonical: `/salon-takvim/${token}`,
    },
  };
}

export default async function SalonTakvimPage({ params }) {
  const { token } = await params;
  return (
    <div className="w-full bg-[#fdfdfd] min-h-[70vh] py-10">
      <div className="container-x mx-auto px-4">
        <SalonCalendarClient token={token} />
      </div>
    </div>
  );
}
