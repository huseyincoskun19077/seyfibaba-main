<script setup lang="ts">
defineProps<{
  title: string;
  text: string;
  accent?: string;
}>();
</script>

<template>
  <article class="info-card">
    <div class="info-card__accent" :style="{ background: accent || 'linear-gradient(135deg, #f4b400, #ffd76b)' }" />
    <h3>{{ title }}</h3>
    <p>{{ text }}</p>
  </article>
</template>
