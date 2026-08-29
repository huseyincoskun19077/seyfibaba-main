<script setup lang="ts">
import type { CatalogProduct } from "~/utils/catalog";
import { formatPrice } from "~/utils/catalog";

defineProps<{
  product: CatalogProduct;
}>();
</script>

<template>
  <article class="catalog-card">
    <NuxtLink :to="`/urun/${product.slug}`" class="catalog-card__image">
      <span class="catalog-card__badge">
        {{ product.offerPrice ? "Kampanya" : "Hazir Stok" }}
      </span>
      <img :src="product.image" :alt="product.name" />
    </NuxtLink>
    <div class="catalog-card__body">
      <div class="catalog-card__meta-row">
        <span class="catalog-card__meta">{{ product.brand }}</span>
        <span class="catalog-card__stock">{{ product.stock }} adet stok</span>
      </div>
      <h3>
        <NuxtLink :to="`/urun/${product.slug}`">{{ product.name }}</NuxtLink>
      </h3>
      <p>{{ product.summary }}</p>
      <div class="catalog-card__tags">
        <span>{{ product.category }}</span>
        <span>Kurulum destegi</span>
      </div>
      <div class="catalog-card__footer">
        <div class="catalog-card__price">
          <strong>{{ formatPrice(product.offerPrice || product.price) }}</strong>
          <span v-if="product.offerPrice">{{ formatPrice(product.price) }}</span>
        </div>
        <NuxtLink :to="`/urun/${product.slug}`" class="catalog-card__action">Incele</NuxtLink>
      </div>
    </div>
  </article>
</template>
