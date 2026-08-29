<script setup lang="ts">
import PageIntro from "~/components/common/PageIntro.vue";
import { formatPrice, getCatalogProducts } from "~/utils/catalog";

const config = useRuntimeConfig();
const items = computed(() => getCatalogProducts(config.public.imageBase).slice(0, 2));
const subtotal = computed(() => items.value.reduce((sum, item) => sum + (item.offerPrice || item.price), 0));
</script>

<template>
  <main>
    <PageIntro
      eyebrow="Sepet"
      title="Sepet akisi iskeleti"
      description="Kullanici sepeti, kupon, kargo ve odeme yonlendirmesi icin temel ekran yapisi."
    />

    <section class="page-section">
      <div class="container detail-columns">
        <div class="stack-md">
          <article v-for="item in items" :key="item.id" class="cart-item panel">
            <img :src="item.image" :alt="item.name" class="cart-item__image" />
            <div class="stack-sm">
              <span class="section-heading__eyebrow">{{ item.brand }}</span>
              <h2 class="panel__title">{{ item.name }}</h2>
              <p class="panel__muted">{{ item.summary }}</p>
            </div>
            <div class="cart-item__meta">
              <strong>{{ formatPrice(item.offerPrice || item.price) }}</strong>
              <span>Adet: 1</span>
            </div>
          </article>
        </div>

        <aside class="panel stack-sm">
          <h2 class="panel__title">Siparis ozeti</h2>
          <div class="summary-row"><span>Ara toplam</span><strong>{{ formatPrice(subtotal) }}</strong></div>
          <div class="summary-row"><span>Kargo</span><strong>Ucretsiz</strong></div>
          <div class="summary-row"><span>Kupon</span><strong>- 0 TL</strong></div>
          <div class="summary-row summary-row--total"><span>Genel toplam</span><strong>{{ formatPrice(subtotal) }}</strong></div>
          <NuxtLink class="button button--primary button--block" to="/odeme">Odeme ekranina gec</NuxtLink>
        </aside>
      </div>
    </section>
  </main>
</template>
