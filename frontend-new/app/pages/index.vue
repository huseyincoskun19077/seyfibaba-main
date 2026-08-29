<script setup lang="ts">
import InfoCard from "~/components/common/InfoCard.vue";
import HomeBrandStrip from "~/components/home/HomeBrandStrip.vue";
import HomeCategoryGrid from "~/components/home/HomeCategoryGrid.vue";
import HomeFlashSale from "~/components/home/HomeFlashSale.vue";
import HomeHero from "~/components/home/HomeHero.vue";
import HomeProductSection from "~/components/home/HomeProductSection.vue";
import { EMPTY_HOMEPAGE, type HomePayload } from "~/types/home";

const config = useRuntimeConfig();

const { data: homepage } = await useAsyncData<HomePayload>(
  "homepage",
  async () => {
    try {
      return await $fetch<HomePayload>(`${config.public.apiBase}/`);
    } catch {
      return EMPTY_HOMEPAGE;
    }
  },
  {
    default: () => EMPTY_HOMEPAGE,
    server: true,
  }
);

const home = computed(() => homepage.value || EMPTY_HOMEPAGE);

const discountedProducts = computed(() =>
  (home.value.allProducts || []).filter((product) => {
    const offer = Number(product.offer_price || 0);
    const price = Number(product.price || 0);
    return offer > 0 && offer < price;
  })
);

const editorialHighlights = [
  {
    title: "Salon Kurulum Paketleri",
    text: "Koltuk, banko, yikama ve bekleme alanini bir arada planlayabileceginiz koleksiyonlar.",
    accent: "linear-gradient(135deg, #111827, #374151)",
  },
  {
    title: "Kurumsal Teklif Akisi",
    text: "Toplu alim, proje bazli liste ve marka karsilastirma surecini hizlandiran storefront yapi.",
    accent: "linear-gradient(135deg, #f4b400, #ffd76b)",
  },
  {
    title: "Hizli Kategori Gecisi",
    text: "Mobil ve masaustunde urun bulmayi kolaylastiran sade kategori ve arama deneyimi.",
    accent: "linear-gradient(135deg, #d93025, #ff7f72)",
  },
];

useSeoMeta({
  title: "Berber ve Kuafor Malzemeleri",
  description:
    "Berber malzemeleri, kuafor ekipmanlari, koltuk ve salon mobilyalari icin Nuxt tabanli yeni Seyfibaba anasayfa prototipi.",
  ogTitle: "Seyfibaba - Yeni Anasayfa",
  ogDescription:
    "Nuxt ile yeniden kurgulanan Seyfibaba storefront anasayfa prototipi.",
  ogType: "website",
});
</script>

<template>
  <main>
    <HomeHero
      :slides="home.sliders"
      :services="home.services"
      :sidebar-one="home.sliderBannerOne"
      :sidebar-two="home.sliderBannerTwo"
    />

    <section class="home-editorial">
      <div class="container home-editorial__inner">
        <div class="section-heading">
          <div>
            <span class="section-heading__eyebrow">One Cikan Akislar</span>
            <h2>Salonunuz icin hizli secim alanlari</h2>
          </div>
        </div>

        <div class="info-grid">
          <NuxtLink v-for="item in editorialHighlights" :key="item.title" to="/products">
            <InfoCard :title="item.title" :text="item.text" :accent="item.accent" />
          </NuxtLink>
        </div>
      </div>
    </section>

    <HomeCategoryGrid
      :categories="home.homepage_categories"
      title="Kategoriler"
    />

    <HomeProductSection
      title="Tum Urunler"
      eyebrow="Magaza Vitrini"
      :products="home.allProducts"
      link="/products"
    />

    <HomeFlashSale
      :offer="home.flashSale?.offer"
      :end-time="home.flashSale?.end_time"
    />

    <HomeProductSection
      title="Indirimli Urunler"
      eyebrow="Firsatlar"
      :products="discountedProducts"
      link="/products?highlight=discounted"
    />

    <HomeProductSection
      title="Yeni Gelenler"
      eyebrow="Yeni"
      :products="home.newArrivalProducts"
      link="/products?highlight=new_arrival"
    />

    <HomeProductSection
      title="En Cok Incelenenler"
      eyebrow="One Cikanlar"
      :products="home.topRatedProducts"
      link="/products?highlight=top_product"
    />

    <HomeBrandStrip :brands="home.brands" />
  </main>
</template>
