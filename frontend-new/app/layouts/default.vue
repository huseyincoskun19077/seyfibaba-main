<script setup lang="ts">
import AppBreadcrumbs from "~/components/layout/AppBreadcrumbs.vue";
import AppFooter from "~/components/layout/AppFooter.vue";
import AppHeader from "~/components/layout/AppHeader.vue";
import AppTrustBar from "~/components/layout/AppTrustBar.vue";
</script>

<template>
  <div class="app-layout">
    <AppHeader />
    <AppTrustBar />
    <div class="app-layout__content">
      <AppBreadcrumbs />
      <slot />
    </div>
    <AppFooter />
  </div>
</template>
