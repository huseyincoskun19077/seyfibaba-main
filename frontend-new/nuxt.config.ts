// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: "2025-07-15",
  devtools: { enabled: true },
  modules: ["@nuxt/ui"],
  css: ["~/assets/css/main.css"],
  app: {
    head: {
      htmlAttrs: {
        lang: "tr",
      },
      meta: [
        { name: "viewport", content: "width=device-width, initial-scale=1" },
      ],
    },
  },
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || "http://127.0.0.1:8001/api",
      imageBase: process.env.NUXT_PUBLIC_IMAGE_BASE || "http://127.0.0.1:8001",
      siteUrl: process.env.NUXT_PUBLIC_SITE_URL || "http://localhost:3001",
    },
  },
});
