<script setup lang="ts">
import type { ProductItem } from "~/types/home";
import { resolveMediaUrl } from "~/utils/media";

const props = defineProps<{
  title: string;
  eyebrow?: string;
  products: ProductItem[];
  link?: string;
}>();

const config = useRuntimeConfig();

const formatCurrency = (value: number | string | null | undefined) => {
  const amount = Number(value || 0);
  return new Intl.NumberFormat("tr-TR", {
    style: "currency",
    currency: "TRY",
    maximumFractionDigits: 0,
  }).format(amount);
};

const visibleProducts = computed(() => props.products.slice(0, 8));
</script>

<template>
  <section v-if="visibleProducts.length" class="home-section">
    <div class="container">
      <div class="section-heading">
        <div>
          <span v-if="eyebrow" class="section-heading__eyebrow">{{ eyebrow }}</span>
          <h2>{{ title }}</h2>
        </div>
        <NuxtLink v-if="link" :to="link" class="section-link">Hepsini gor</NuxtLink>
      </div>

      <div class="product-grid">
        <article v-for="product in visibleProducts" :key="product.id" class="product-card">
          <NuxtLink :to="`/urun/${product.slug}`" class="product-card__image">
            <span class="product-card__badge">
              {{ product.offer_price && Number(product.offer_price) < Number(product.price || 0) ? "Indirimli" : "One Cikan" }}
            </span>
            <img
              v-if="product.thumb_image"
              :src="resolveMediaUrl(product.thumb_image, config.public.imageBase)"
              :alt="product.name"
            />
          </NuxtLink>
          <div class="product-card__body">
            <div class="product-card__meta-row">
              <span class="product-card__meta-tag">Salon ekipmani</span>
              <span class="product-card__meta-tag product-card__meta-tag--muted">Hizli sevkiyat</span>
            </div>
            <h3>
              <NuxtLink :to="`/urun/${product.slug}`">{{ product.name }}</NuxtLink>
            </h3>
            <div class="product-card__price">
              <span class="product-card__price-current">
                {{ formatCurrency(product.offer_price || product.price) }}
              </span>
              <span
                v-if="product.offer_price && Number(product.offer_price) < Number(product.price || 0)"
                class="product-card__price-old"
              >
                {{ formatCurrency(product.price) }}
              </span>
            </div>
            <div class="product-card__actions">
              <NuxtLink :to="`/urun/${product.slug}`" class="product-card__cta">Detay</NuxtLink>
              <NuxtLink to="/sepet" class="product-card__ghost">Sepete Ekle</NuxtLink>
            </div>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>
