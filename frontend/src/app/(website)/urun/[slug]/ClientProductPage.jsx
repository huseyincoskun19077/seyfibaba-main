"use client";

import { useEffect, useState } from "react";
import SingleProductPage from "@/components/SingleProductPage";
import { useLazyGetProductBySlugApiQuery } from "@/redux/features/product/apiSlice";

/**
 * SSR details for SEO/first paint; on every client open refetch like mobile
 * so price/stock are never stuck in CDN/HTML cache.
 */
export default function ClientProductPage({ details }) {
  const [liveDetails, setLiveDetails] = useState(details);
  const [trigger] = useLazyGetProductBySlugApiQuery();

  useEffect(() => {
    setLiveDetails(details);
  }, [details]);

  useEffect(() => {
    const slug = details?.product?.slug;
    if (!slug) return undefined;

    let cancelled = false;
    trigger(slug, false)
      .unwrap()
      .then((data) => {
        if (!cancelled && data?.product) {
          setLiveDetails(data);
        }
      })
      .catch(() => {});

    return () => {
      cancelled = true;
    };
  }, [details?.product?.slug, details?.product?.id, trigger]);

  return <SingleProductPage details={liveDetails} />;
}
