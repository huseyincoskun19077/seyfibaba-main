<script setup lang="ts">
import { trustHighlights } from "~/utils/navigation";
</script>

<template>
  <section class="trust-bar">
    <div class="container trust-bar__grid">
      <article v-for="item in trustHighlights" :key="item.title" class="trust-bar__item">
        <strong>{{ item.title }}</strong>
        <p>{{ item.text }}</p>
      </article>
    </div>
  </section>
</template>
