import React, { Suspense } from "react";
import PaymentFailed from "../../../components/PaymentFailed";

export const metadata = {
  title: "Ödeme Başarısız",
  robots: { index: false, follow: false },
  alternates: {
    canonical: "/payment-failed",
  },
};

function PaymentFailedPage() {
  return (
    <Suspense fallback={<div className="min-h-[70vh]" />}>
      <PaymentFailed />
    </Suspense>
  );
}

export default PaymentFailedPage;
