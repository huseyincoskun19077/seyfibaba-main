<script setup lang="ts">
import InfoCard from "~/components/common/InfoCard.vue";
import PageIntro from "~/components/common/PageIntro.vue";

const route = useRoute();
</script>

<template>
  <main>
    <PageIntro
      eyebrow="Siparis Detayi"
      :title="`Siparis ${String(route.params.id)}`"
      description="Kargo durumlari, urun satirlari, fatura ve destek hareketleri burada gosterilecek."
    />

    <section class="page-section">
      <div class="container info-grid">
        <InfoCard title="Kargo Durumu" text="Hazirlaniyor -> Kargoya verildi -> Teslim edildi adimlari." />
        <InfoCard title="Siparis Kalemleri" text="Urun satirlari, fiyat dagilimi ve varsa kupon kirilimi." />
        <InfoCard title="Destek ve Iade" text="Mesajlasma, talep olusturma ve iade durumu alanlari." />
      </div>
    </section>
  </main>
</template>
