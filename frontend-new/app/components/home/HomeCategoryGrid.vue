<script setup lang="ts">
import type { CategoryItem } from "~/types/home";
import { resolveMediaUrl } from "~/utils/media";

defineProps<{
  title?: string;
  categories: CategoryItem[];
}>();

const config = useRuntimeConfig();
</script>

<template>
  <section class="home-section">
    <div class="container">
      <div class="section-heading">
        <div>
          <span class="section-heading__eyebrow">Kategoriler</span>
          <h2>{{ title || "Kategoriler" }}</h2>
        </div>
        <NuxtLink to="/products" class="section-link">Tum urunler</NuxtLink>
      </div>

      <div class="category-grid">
        <NuxtLink
          v-for="category in categories.slice(0, 8)"
          :key="category.id"
          :to="`/products?category=${category.slug}`"
          class="category-card"
        >
          <div class="category-card__image">
            <span class="category-card__badge">Kategori</span>
            <img
              v-if="category.image"
              :src="resolveMediaUrl(category.image, config.public.imageBase)"
              :alt="`${category.name} kategori gorseli`"
            />
          </div>
          <div class="category-card__content">
            <h3>{{ category.name }}</h3>
            <p>{{ category.description || "Salonunuz icin uygun urunleri inceleyin." }}</p>
            <span class="category-card__link">Urunleri gor</span>
          </div>
        </NuxtLink>
      </div>
    </div>
  </section>
</template>
