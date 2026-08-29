<script setup lang="ts">
import CatalogCard from "~/components/common/CatalogCard.vue";
import InfoCard from "~/components/common/InfoCard.vue";
import PageIntro from "~/components/common/PageIntro.vue";
import { getCatalogProducts, formatPrice } from "~/utils/catalog";

const route = useRoute();
const config = useRuntimeConfig();

const products = computed(() => getCatalogProducts(config.public.imageBase));
const product = computed(
  () => products.value.find((item) => item.slug === route.params.slug) || products.value[0]
);
const relatedProducts = computed(() =>
  products.value.filter((item) => item.slug !== product.value.slug).slice(0, 3)
);
</script>

<template>
  <main>
    <PageIntro
      eyebrow="Detay"
      title="Urun detay iskeleti"
      description="Galeri, fiyat, varyant, satici ve teslimat bolumlerini tasiyacak ana detay ekraninin temel yapisi."
    />

    <section class="page-section">
      <div class="container product-detail">
        <div class="product-detail__gallery panel">
          <img :src="product.image" :alt="product.name" class="product-detail__image" />
          <div class="product-detail__thumbs">
            <div v-for="index in 3" :key="index" class="product-detail__thumb">
              <img :src="product.image" :alt="`${product.name} ${index}`" />
            </div>
          </div>
        </div>

        <div class="product-detail__summary panel stack-md">
          <div>
            <span class="section-heading__eyebrow">{{ product.brand }}</span>
            <h1 class="product-detail__title">{{ product.name }}</h1>
            <p class="panel__muted">{{ product.summary }}</p>
          </div>

          <div class="product-detail__price">
            <strong>{{ formatPrice(product.offerPrice || product.price) }}</strong>
            <span v-if="product.offerPrice">{{ formatPrice(product.price) }}</span>
          </div>

          <div class="detail-specs">
            <div><span>Kategori</span><strong>{{ product.category }}</strong></div>
            <div><span>Stok</span><strong>{{ product.stock }} adet</strong></div>
            <div><span>Teslimat</span><strong>7-10 is gunu</strong></div>
            <div><span>Garanti</span><strong>2 yil</strong></div>
          </div>

          <div class="detail-actions">
            <NuxtLink class="button button--primary" to="/sepet">Sepete ekle</NuxtLink>
            <NuxtLink class="button button--ghost" to="/favoriler">Favorilere kaydet</NuxtLink>
            <NuxtLink class="button button--ghost" to="/karsilastir">Karsilastir</NuxtLink>
          </div>
        </div>
      </div>
    </section>

    <section class="page-section">
      <div class="container detail-columns">
        <div class="panel stack-md">
          <h2 class="panel__title">Urun icerigi</h2>
          <p class="panel__muted">
            Bu alan urun aciklamasi, teknik ozellikler, taksit secenekleri ve yorumlar icin ayrildi.
            Laravel API baglantisi sonraki adimda bu bloklari dolduracak.
          </p>
          <div class="info-grid">
            <InfoCard title="Teknik Ozellikler" text="Olcu, malzeme, renk, motor tipi ve aksesuarlar." />
            <InfoCard title="Kargo ve Kurulum" text="Sehir bazli teslimat ve montaj akislari burada gosterilecek." />
          </div>
        </div>

        <aside class="panel stack-sm">
          <h2 class="panel__title">Satici kutusu</h2>
          <p class="panel__muted">Satici puani, mesaj gonderme, stok ve magaza bilgisi burada yer alacak.</p>
          <InfoCard title="Satici" text="Seyfibaba Studio - 4.9 puan" />
          <InfoCard title="Iade" text="14 gun cayma ve destek sureci burada gosterilecek." accent="linear-gradient(135deg, #111827, #374151)" />
        </aside>
      </div>
    </section>

    <section class="page-section">
      <div class="container stack-md">
        <div class="section-heading">
          <div>
            <span class="section-heading__eyebrow">Benzer Urunler</span>
            <h2>Ilgini cekebilecek urunler</h2>
          </div>
        </div>
        <div class="catalog-grid">
          <CatalogCard v-for="item in relatedProducts" :key="item.id" :product="item" />
        </div>
      </div>
    </section>
  </main>
</template>
