import { permanentRedirect } from "next/navigation";
import { buildProductPath } from "@/utils/url";

export const dynamic = 'force-dynamic'; // Static export için gerekli

export default async function SingleProduct({ searchParams }) {
  const params = await searchParams;
  const slug = params.slug;

  permanentRedirect(buildProductPath(slug));
}
