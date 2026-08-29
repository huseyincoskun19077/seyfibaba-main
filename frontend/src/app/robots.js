import appConfig from "@/appConfig";

export default function robots() {
  return {
    rules: {
      userAgent: "*",
      allow: "/",
      disallow: [
        "/admin/",
        "/user/",
        "/cart",
        "/checkout",
        "/order-success",
        "/payment-failed",
        "/profile",
        "/tracking-order",
        "/wishlist",
        "/become-seller",
        "/seller/",
        "/sellers",
        "/seller-products",
        "/products-compare",
        "/login",
        "/signup",
        "/forgot-password",
        "/verify-you",
        "/search?*",
        "/api/",
      ],
    },
    sitemap: `${appConfig.APPLICATION_URL}/sitemap.xml`,
  };
}

