import { notFound } from "next/navigation";
import { serverApiGet } from "@/utils/serverApiFetch";

export default async function getProductDetails(slug) {
  let res;
  try {
    res = await serverApiGet(`product/${encodeURIComponent(slug)}`);
  } catch {
    notFound();
  }
  try {
    const data = await res.json();
    if (!data?.product?.id) {
      notFound();
    }
    return data;
  } catch {
    notFound();
  }
}
