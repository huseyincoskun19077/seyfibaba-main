<script setup lang="ts">
const route = useRoute();

const labelMap: Record<string, string> = {
  products: "Urunler",
  urun: "Urun Detay",
  sepet: "Sepet",
  odeme: "Odeme",
  favoriler: "Favoriler",
  karsilastir: "Karsilastir",
  siparisler: "Siparisler",
  hesabim: "Hesabim",
  adresler: "Adresler",
  profil: "Profil",
};

const breadcrumbs = computed(() => {
  if (route.path === "/") return [];

  const segments = route.path.split("/").filter(Boolean);
  return segments.map((segment, index) => {
    const path = `/${segments.slice(0, index + 1).join("/")}`;
    const label =
      labelMap[segment] ||
      decodeURIComponent(segment)
        .replace(/-/g, " ")
        .replace(/\b\w/g, (char) => char.toUpperCase());

    return { label, to: path };
  });
});
</script>

<template>
  <section v-if="breadcrumbs.length" class="breadcrumbs">
    <div class="container">
      <nav aria-label="Breadcrumb" class="breadcrumbs__nav">
        <NuxtLink to="/" class="breadcrumbs__link">Anasayfa</NuxtLink>
        <template v-for="item in breadcrumbs" :key="item.to">
          <span class="breadcrumbs__separator">/</span>
          <NuxtLink :to="item.to" class="breadcrumbs__link">
            {{ item.label }}
          </NuxtLink>
        </template>
      </nav>
    </div>
  </section>
</template>
