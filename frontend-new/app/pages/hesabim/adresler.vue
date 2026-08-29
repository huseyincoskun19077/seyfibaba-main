<script setup lang="ts">
import AccountSidebar from "~/components/account/AccountSidebar.vue";
import InfoCard from "~/components/common/InfoCard.vue";
import PageIntro from "~/components/common/PageIntro.vue";
</script>

<template>
  <main>
    <PageIntro
      eyebrow="Adresler"
      title="Teslimat adresleri"
      description="Kayitli adresler, yeni adres ekleme ve varsayilan secim akisi burada yonetilecek."
    />

    <section class="page-section">
      <div class="container account-layout">
        <AccountSidebar />

        <div class="stack-md">
          <div class="info-grid">
            <InfoCard title="Ofis Adresi" text="Istanbul / Umraniye - montaj ve teslimat icin kullanilan ana adres." />
            <InfoCard title="Sube Adresi" text="Ankara / Cankaya - ikinci teslimat noktasi." accent="linear-gradient(135deg, #111827, #374151)" />
          </div>
          <div class="panel">
            <h2 class="panel__title">Yeni adres formu</h2>
            <p class="panel__muted">Il, ilce, mahalle, acik adres, vergi bilgisi ve telefon alanlari eklenecek.</p>
          </div>
        </div>
      </div>
    </section>
  </main>
</template>
