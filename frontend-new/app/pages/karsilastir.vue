<script setup lang="ts">
import PageIntro from "~/components/common/PageIntro.vue";
import { getCatalogProducts, getCompareFeatures } from "~/utils/catalog";

const config = useRuntimeConfig();
const products = computed(() => getCatalogProducts(config.public.imageBase).slice(0, 2));
const features = getCompareFeatures();
</script>

<template>
  <main>
    <PageIntro
      eyebrow="Karsilastirma"
      title="Urun karsilastirma ekranı"
      description="Ozellik, fiyat, teslimat ve stok bazli karsilastirma tablosunun yeni storefront karsiligi."
    />

    <section class="page-section">
      <div class="container stack-md">
        <div class="compare-hero">
          <article v-for="product in products" :key="product.id" class="panel stack-sm">
            <img :src="product.image" :alt="product.name" class="compare-hero__image" />
            <span class="section-heading__eyebrow">{{ product.brand }}</span>
            <h2 class="panel__title">{{ product.name }}</h2>
          </article>
        </div>

        <div class="panel">
          <table class="compare-table">
            <thead>
              <tr>
                <th>Ozellik</th>
                <th>{{ products[0]?.name }}</th>
                <th>{{ products[1]?.name }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="feature in features" :key="feature.label">
                <td>{{ feature.label }}</td>
                <td>{{ feature.left }}</td>
                <td>{{ feature.right }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </main>
</template>
