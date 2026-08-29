<script setup lang="ts">
defineProps<{
  eyebrow?: string;
  title: string;
  description: string;
}>();
</script>

<template>
  <section class="page-intro">
    <div class="container">
      <div class="page-intro__panel">
        <span v-if="eyebrow" class="section-heading__eyebrow">{{ eyebrow }}</span>
        <h1>{{ title }}</h1>
        <p>{{ description }}</p>
      </div>
    </div>
  </section>
</template>
