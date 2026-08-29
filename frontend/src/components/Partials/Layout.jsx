import { headers } from "next/headers";
import getSetupData from "@/api/setup";
import { isSecondHandHost } from "@/utils/secondHandSite";
import LayoutClient from "./LayoutClient";

/**
 * Server Component Layout Wrapper
 * 
 * Fetches initial website setup data on the server to improve:
 * 1. TTFB (Time to First Byte)
 * 2. LCP (Largest Contentful Paint)
 * 3. SEO (Search Engine Optimization)
 */
export default async function Layout({ children, childrenClasses }) {
  const websiteSetupData = await getSetupData();
  const h = await headers();
  const host = h.get("x-forwarded-host") || h.get("host") || "";
  const isSecondHandSite = isSecondHandHost(host);

  return (
    <LayoutClient 
      childrenClasses={isSecondHandSite ? "pb-8" : childrenClasses} 
      websiteSetupData={websiteSetupData}
      isSecondHandSite={isSecondHandSite}
    >
      {children}
    </LayoutClient>
  );
}
