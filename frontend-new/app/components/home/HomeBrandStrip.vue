<script setup lang="ts">
import type { BrandItem } from "~/types/home";

defineProps<{
  brands: BrandItem[];
}>();
</script>

<template>
  <section v-if="brands.length" class="home-section">
    <div class="container">
      <div class="section-heading">
        <div>
          <span class="section-heading__eyebrow">Markalar</span>
          <h2>Markalara Gore Alisveris</h2>
        </div>
      </div>

      <div class="brand-strip">
        <NuxtLink
          v-for="brand in brands.slice(0, 12)"
          :key="brand.id"
          :to="brand.slug ? `/products?brand=${brand.slug}` : '/products'"
          class="brand-pill"
        >
          {{ brand.name }}
        </NuxtLink>
      </div>
    </div>
  </section>
</template>
