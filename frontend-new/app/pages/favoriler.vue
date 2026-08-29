<script setup lang="ts">
import CatalogCard from "~/components/common/CatalogCard.vue";
import PageIntro from "~/components/common/PageIntro.vue";
import { getCatalogProducts } from "~/utils/catalog";

const config = useRuntimeConfig();
const favorites = computed(() => getCatalogProducts(config.public.imageBase).slice(0, 3));
</script>

<template>
  <main>
    <PageIntro
      eyebrow="Favoriler"
      title="Favori urunler ekranı"
      description="Kullanici favorileri, stok takibi ve listeyi sepete tasima aksiyonlari icin temel ekran."
    />

    <section class="page-section">
      <div class="container stack-md">
        <div class="panel panel--row">
          <div>
            <span class="section-heading__eyebrow">Kayitli Liste</span>
            <h2 class="panel__title">{{ favorites.length }} urun favorilerde</h2>
          </div>
          <NuxtLink class="button button--ghost" to="/products">Listeye yeni urun ekle</NuxtLink>
        </div>

        <div class="catalog-grid">
          <CatalogCard v-for="product in favorites" :key="product.id" :product="product" />
        </div>
      </div>
    </section>
  </main>
</template>
