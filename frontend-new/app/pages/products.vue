<script setup lang="ts">
import CatalogCard from "~/components/common/CatalogCard.vue";
import InfoCard from "~/components/common/InfoCard.vue";
import PageIntro from "~/components/common/PageIntro.vue";
import { getCatalogProducts } from "~/utils/catalog";

const config = useRuntimeConfig();
const route = useRoute();
const filterGroups = [
  "Berber koltuklari",
  "Yikama setleri",
  "Bankolar",
  "Bekleme gruplari",
  "Dolap sistemleri",
];
const sortGroups = ["Onerilen", "Yeni gelenler", "Fiyata gore", "Indirim oranina gore"];

const products = computed(() => getCatalogProducts(config.public.imageBase));
const search = computed(() => String(route.query.search || "").toLowerCase());

const filteredProducts = computed(() => {
  if (!search.value) return products.value;
  return products.value.filter(
    (item) =>
      item.name.toLowerCase().includes(search.value) ||
      item.brand.toLowerCase().includes(search.value) ||
      item.category.toLowerCase().includes(search.value)
  );
});
</script>

<template>
  <main>
    <PageIntro
      eyebrow="Katalog"
      title="Salonunuza uygun urunleri kesfedin"
      description="Kategori, marka, kampanya ve hizli arama akisini ayni sayfada toplayan yeni Seyfibaba katalog deneyimi."
    />

    <section class="page-section">
      <div class="container catalog-layout">
        <aside class="panel stack-sm catalog-filter-panel">
          <div>
            <span class="section-heading__eyebrow">Filtreler</span>
            <h2 class="panel__title">Kategori ve hizli secimler</h2>
          </div>
          <div class="catalog-chip-list">
            <NuxtLink v-for="item in filterGroups" :key="item" :to="`/products?search=${encodeURIComponent(item)}`" class="catalog-chip">
              {{ item }}
            </NuxtLink>
          </div>
          <InfoCard title="Marka Secimi" text="Seyfibaba Studio, ozel uretim urunler ve salon projelerine uygun secimler." />
          <InfoCard title="Filtre Aksiyonlari" text="Fiyat, teslimat, stok, taksit, renk ve olcu filtreleri bu alanin altina baglanacak." accent="linear-gradient(135deg, #111827, #374151)" />
        </aside>

        <div class="stack-md">
          <div class="panel panel--row catalog-toolbar">
            <div>
              <span class="section-heading__eyebrow">Sonuc</span>
              <h2 class="panel__title">{{ filteredProducts.length }} urun listeleniyor</h2>
            </div>
            <div class="catalog-sort-list">
              <span v-for="item in sortGroups" :key="item" class="catalog-sort-pill">{{ item }}</span>
            </div>
          </div>

          <div class="catalog-grid">
            <CatalogCard v-for="product in filteredProducts" :key="product.id" :product="product" />
          </div>
        </div>
      </div>
    </section>
  </main>
</template>
