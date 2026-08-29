<script setup lang="ts">
defineProps<{
  offer?: number | string | null;
  endTime?: string | null;
}>();

const formatDate = (value?: string | null) => {
  if (!value) return "";
  try {
    return new Intl.DateTimeFormat("tr-TR", {
      dateStyle: "long",
      timeStyle: "short",
    }).format(new Date(value));
  } catch {
    return value;
  }
};
</script>

<template>
  <section v-if="offer || endTime" class="home-section">
    <div class="container">
      <div class="flash-sale">
        <div>
          <span class="section-heading__eyebrow">Kampanya</span>
          <h2>Indirimli Urunler</h2>
          <p>
            Secili urunlerde
            <strong v-if="offer"> %{{ offer }} </strong>
            indirim firsatini kacirmayin.
          </p>
        </div>
        <div v-if="endTime" class="flash-sale__meta">
          <span>Kampanya bitis</span>
          <strong>{{ formatDate(endTime) }}</strong>
        </div>
      </div>
    </div>
  </section>
</template>
