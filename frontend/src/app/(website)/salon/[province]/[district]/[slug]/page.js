import SalonWebsiteClient from "@/components/SalonCrm/SalonWebsiteClient";
import { fetchSalonWebsite } from "@/api/salonWebsitePublic";

export const dynamic = "force-dynamic";
export const dynamicParams = true;

export async function generateMetadata({ params }) {
  const { province, district, slug } = await params;
  const data = await fetchSalonWebsite(province, district, slug);

  if (!data || data.status === "not_found") {
    return {
      title: "Salon sitesi | Seyfibaba",
      description: "Salon web sitesi bulunamadı.",
      robots: { index: false, follow: false },
    };
  }

  if (data.status === "closed" || data.status === "subscription_inactive") {
    return {
      title: `${data.salon_name || "Salon"} | Seyfibaba`,
      description: data.message || "Salon sitesi şu an kapalı.",
      robots: { index: false, follow: false },
    };
  }

  const salon = data.salon || {};
  return {
    title: salon.seo_title || `${salon.name || "Salon"} | Seyfibaba`,
    description:
      salon.seo_description ||
      `${salon.name || "Salon"} randevu, hizmetler ve iletişim.`,
    alternates: {
      canonical: `/salon/${province}/${district}/${slug}`,
    },
  };
}

export default async function SalonPublicWebsitePage({ params }) {
  const { province, district, slug } = await params;
  const data = await fetchSalonWebsite(province, district, slug);

  return (
    <div className="w-full bg-[#fdfdfd] min-h-[70vh] py-10">
      <div className="container-x mx-auto px-4">
        <SalonWebsiteClient data={data} />
      </div>
    </div>
  );
}
