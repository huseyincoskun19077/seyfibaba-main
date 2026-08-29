<script setup lang="ts">
import { accountNavigation } from "~/utils/navigation";

const route = useRoute();
</script>

<template>
  <aside class="account-sidebar">
    <div class="account-sidebar__profile">
      <div class="account-sidebar__avatar">S</div>
      <div>
        <strong>Seyfibaba Musteri</strong>
        <p>Profesyonel salon alicisi</p>
      </div>
    </div>

    <nav class="account-sidebar__nav" aria-label="Hesap menu">
      <NuxtLink
        v-for="item in accountNavigation"
        :key="item.to"
        :to="item.to"
        class="account-sidebar__link"
        :class="{ 'is-active': route.path === item.to }"
      >
        {{ item.label }}
      </NuxtLink>
    </nav>
  </aside>
</template>
