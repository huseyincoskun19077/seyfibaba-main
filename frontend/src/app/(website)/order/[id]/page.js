import OrderPageClient from "@/components/OrderCom/OrderPageClient";

export async function generateMetadata({ params }) {
  const { id } = await params;

  return {
    title: `Sipariş #${id}`,
    robots: { index: false, follow: false },
    alternates: {
      canonical: `/order/${id}`,
    },
  };
}

export async function generateStaticParams() {
  return [];
}

export const dynamic = "force-dynamic";

export default async function OrderDetailsPage({ params }) {
  const { id } = await params;

  return <OrderPageClient orderId={id} />;
}
