<script setup lang="ts">
import PageIntro from "~/components/common/PageIntro.vue";
import { formatPrice, getSampleOrders } from "~/utils/catalog";

const orders = getSampleOrders();
</script>

<template>
  <main>
    <PageIntro
      eyebrow="Siparisler"
      title="Siparis takip ve gecmis ekranı"
      description="Siparis listesi, durum takibi, detay ve iade akislarini tasiyacak yeni sayfa yapisi."
    />

    <section class="page-section">
      <div class="container stack-md">
        <article v-for="order in orders" :key="order.id" class="panel order-card">
          <div>
            <span class="section-heading__eyebrow">{{ order.status }}</span>
            <h2 class="panel__title">{{ order.id }}</h2>
            <p class="panel__muted">{{ order.date }} • {{ order.items }} urun</p>
          </div>
          <div class="order-card__meta">
            <strong>{{ formatPrice(order.total) }}</strong>
            <NuxtLink class="button button--ghost" :to="`/siparisler/${order.id}`">Detay</NuxtLink>
          </div>
        </article>
      </div>
    </section>
  </main>
</template>
