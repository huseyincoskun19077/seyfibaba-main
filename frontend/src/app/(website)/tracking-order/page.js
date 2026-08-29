import TrackingOrder from "@/components/TrackingOrder/index";

export const dynamic = 'force-dynamic';

// generate seo metadata
export async function generateMetadata() {
  return {
    title: "Sipariş Takibi",
    description: "Sipariş numaranız ile siparişinizin güncel durumunu takip edin.",
    alternates: {
      canonical: "/tracking-order",
    },
    robots: { index: false, follow: true },
  };
}

// main page
export default async function trackingOrderPage() {
  return <TrackingOrder />;
}
