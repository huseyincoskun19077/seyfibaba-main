<script setup lang="ts">
import InfoCard from "~/components/common/InfoCard.vue";
import PageIntro from "~/components/common/PageIntro.vue";
</script>

<template>
  <main>
    <PageIntro
      eyebrow="Checkout"
      title="Odeme ve siparis tamamlama"
      description="Adres, kargo, fatura ve iyzico odeme akisinin baglanacagi checkout iskeleti."
    />

    <section class="page-section">
      <div class="container detail-columns">
        <div class="panel stack-md">
          <h2 class="panel__title">Teslimat ve fatura</h2>
          <div class="info-grid">
            <InfoCard title="Adres Secimi" text="Kayitli adresler, yeni adres ekleme ve varsayilan secim akisi." />
            <InfoCard title="Kargo Secenekleri" text="Sehir bazli teslimat, montaj ve tahmini sevkiyat suresi." />
            <InfoCard title="Odeme Yontemi" text="Iyzico, havale/EFT, kapida odeme gibi secenekler bu alana gelecek." />
          </div>
        </div>

        <aside class="panel stack-sm">
          <h2 class="panel__title">Guven modulu</h2>
          <p class="panel__muted">3D Secure, siparis notu, mesafeli satis ve KVKK onaylari burada yonetilecek.</p>
          <NuxtLink class="button button--primary button--block" to="/siparisler">Demo siparisi tamamla</NuxtLink>
        </aside>
      </div>
    </section>
  </main>
</template>
