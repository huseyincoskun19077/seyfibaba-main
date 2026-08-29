<script setup lang="ts">
import AccountSidebar from "~/components/account/AccountSidebar.vue";
import InfoCard from "~/components/common/InfoCard.vue";
import PageIntro from "~/components/common/PageIntro.vue";
</script>

<template>
  <main>
    <PageIntro
      eyebrow="Hesabim"
      title="Musteri paneli"
      description="Profil ozeti, siparis istatistikleri, adresler ve favoriler icin ortak hesap ekranı."
    />

    <section class="page-section">
      <div class="container account-layout">
        <AccountSidebar />

        <div class="stack-md">
          <div class="info-grid">
            <InfoCard title="Aktif Siparis" text="2 aktif siparisin var. Kargo ve hazirlama surecini takip edebilirsin." />
            <InfoCard title="Favoriler" text="Kayitli 7 favori urun. Fiyat degisimlerini burada gorebilirsin." accent="linear-gradient(135deg, #111827, #374151)" />
            <InfoCard title="Destek" text="Acik destek talebi bulunmuyor. Yeni talep akisi eklenecek." accent="linear-gradient(135deg, #d93025, #ff7f72)" />
          </div>

          <div class="panel">
            <h2 class="panel__title">Hizli is akisi</h2>
            <p class="panel__muted">
              Bu blokta tekrar satin alma, siparis gecmisi, adres duzenleme ve kullanici bildirimleri
              yer alacak.
            </p>
          </div>
        </div>
      </div>
    </section>
  </main>
</template>
