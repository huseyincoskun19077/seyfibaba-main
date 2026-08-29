import appConfig from "@/appConfig";
import { buildProductUrl } from "@/utils/url";
import { getSecondHandListingSeoPath } from "@/api/secondHandPublic";
import { secondHandPublicOrigin } from "@/utils/secondHandSite";

const baseUrl = appConfig.APPLICATION_URL || "https://seyfibaba.com";
const secondHandBaseUrl = secondHandPublicOrigin();

export default async function sitemap() {
  const routes = [
    "", "/products", "/about", "/contact", "/faq",
    "/salon-crm",
    "/terms-condition", "/privacy-policy",
    "/flash-sale",
  ].map((route) => ({
    url: `${baseUrl}${route}`,
    lastModified: new Date(),
    changeFrequency: route === "" ? "daily" : "weekly",
    priority: route === "" ? 1 : 0.7,
  }));

  let categories = [];
  let productEntries = [];
  let secondHandEntries = [];

  try {
    const [homeRes, productsRes, secondHandRes] = await Promise.all([
      fetch(`${appConfig.BASE_URL}api/`, { cache: "no-store" }),
      fetch(`${appConfig.BASE_URL}api/products/sitemap`, { cache: "no-store" }),
      fetch(`${appConfig.BASE_URL}api/second-hand/sitemap`, { cache: "no-store" }),
    ]);

    if (homeRes.ok) {
      const homeData = await homeRes.json();
      categories = (homeData.productCategories || []).map((cat) => ({
        url: `${baseUrl}/products?category=${cat.slug}`,
        lastModified: new Date(),
        changeFrequency: "weekly",
        priority: 0.8,
      }));
    }

    if (productsRes.ok) {
        const productsData = await productsRes.json();
        productEntries = (productsData?.products || [])
          .filter((product) => product.slug && product.slug !== "test-urunu-5-tl")
          .map((product) => ({
            url: buildProductUrl(baseUrl, product.slug),
            lastModified: product.updated_at ? new Date(product.updated_at) : new Date(),
            changeFrequency: "weekly",
            priority: 0.9,
          }));
    }

    if (secondHandRes.ok) {
      const shData = await secondHandRes.json();
      secondHandEntries = (shData?.listings || []).map((row) => ({
        url: `${secondHandBaseUrl}/ikinci-el/${getSecondHandListingSeoPath(row)}`,
        lastModified: row.updated_at ? new Date(row.updated_at) : new Date(),
        changeFrequency: "weekly",
        priority: 0.75,
      }));
    }
  } catch (error) {
    // sitemap generation failed silently
  }

  return [
    ...routes,
    {
      url: `${secondHandBaseUrl}/ikinci-el`,
      lastModified: new Date(),
      changeFrequency: "daily",
      priority: 0.8,
    },
    ...categories,
    ...productEntries,
    ...secondHandEntries,
  ];
}
