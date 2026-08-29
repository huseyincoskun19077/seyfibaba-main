<script setup lang="ts">
import { categoryNavigation, mobileNavigation, primaryNavigation } from "~/utils/navigation";

const route = useRoute();
const searchTerm = ref("");
const menuOpen = ref(false);

const quickSearches = ["Berber koltugu", "Yikama seti", "Bekleme koltugu"];

const desktopActions = [
  { label: "Favoriler", to: "/favoriler" },
  { label: "Karsilastir", to: "/karsilastir" },
  { label: "Hesabim", to: "/hesabim" },
];

const isActive = (target: string) => {
  if (target === "/") return route.path === "/";
  return route.path.startsWith(target.split("?")[0]);
};

const searchHref = computed(() =>
  searchTerm.value.trim()
    ? `/products?search=${encodeURIComponent(searchTerm.value.trim())}`
    : "/products"
);

watch(
  () => route.fullPath,
  () => {
    menuOpen.value = false;
  }
);
</script>

<template>
  <header class="store-header">
    <div class="store-header__notice">
      <div class="container store-header__notice-inner">
        <p>Profesyonel salon ekipmanlari, proje destegi ve kurumsal teklif akisi</p>
        <div class="store-header__notice-links">
          <a href="tel:+905555555555">+90 555 555 55 55</a>
          <NuxtLink to="/siparisler">Siparis Takibi</NuxtLink>
        </div>
      </div>
    </div>

    <div class="container store-header__desktop">
      <div class="store-header__brand-row">
        <NuxtLink to="/" class="store-brand">
          <span class="store-brand__mark">S</span>
          <span class="store-brand__copy">
            <strong>Seyfibaba</strong>
            <small>Berber ve kuafor ekipmanlari</small>
          </span>
        </NuxtLink>

        <div class="store-search-shell">
          <div class="store-search">
            <label class="sr-only" for="store-search-input">Urun ara</label>
            <input
              id="store-search-input"
              v-model="searchTerm"
              type="search"
              placeholder="Berber koltugu, yikama seti, kuafor bankosu..."
            />
            <NuxtLink :to="searchHref" class="store-search__button">Ara</NuxtLink>
          </div>

          <div class="store-search__quick">
            <span>Hizli ara:</span>
            <NuxtLink
              v-for="item in quickSearches"
              :key="item"
              :to="`/products?search=${encodeURIComponent(item)}`"
              class="store-search__quick-link"
            >
              {{ item }}
            </NuxtLink>
          </div>
        </div>

        <div class="store-header__actions">
          <NuxtLink
            v-for="item in desktopActions"
            :key="item.to"
            :to="item.to"
            class="store-action"
            :class="{ 'is-active': isActive(item.to) }"
          >
            {{ item.label }}
          </NuxtLink>

          <NuxtLink to="/sepet" class="store-action store-action--cart" :class="{ 'is-active': isActive('/sepet') }">
            <span>Sepet</span>
            <small>3</small>
          </NuxtLink>
        </div>
      </div>

      <div class="store-header__nav-row">
        <div class="store-header__nav-left">
          <div class="store-category-menu">
            <button
              type="button"
              class="store-category-menu__trigger"
              :aria-expanded="menuOpen"
              @click="menuOpen = !menuOpen"
            >
              <span class="store-category-menu__icon">≡</span>
              <span>Tum Kategoriler</span>
            </button>

            <div v-if="menuOpen" class="store-category-menu__panel">
              <NuxtLink
                v-for="item in categoryNavigation"
                :key="item.to"
                :to="item.to"
                class="store-category-menu__item"
              >
                {{ item.label }}
              </NuxtLink>
            </div>
          </div>

          <nav class="store-nav" aria-label="Ana menu">
            <NuxtLink
              v-for="item in primaryNavigation"
              :key="item.to"
              :to="item.to"
              class="store-nav__link"
              :class="{ 'is-active': isActive(item.to) }"
            >
              {{ item.label }}
            </NuxtLink>
          </nav>
        </div>

        <div class="store-header__nav-right">
          <span class="store-header__meta">Ayni gun teklif hazirlama</span>
          <NuxtLink to="/products?highlight=second-hand" class="store-header__cta">
            Ikinci El
          </NuxtLink>
        </div>
      </div>
    </div>

    <div class="store-mobile">
      <div class="container store-mobile__inner">
        <div class="store-mobile__top">
          <NuxtLink to="/" class="store-brand store-brand--mobile">
            <span class="store-brand__mark">S</span>
            <span class="store-brand__copy">
              <strong>Seyfibaba</strong>
            </span>
          </NuxtLink>

          <div class="store-mobile__actions">
            <NuxtLink to="/favoriler" class="store-mobile__chip">Fav</NuxtLink>
            <NuxtLink to="/sepet" class="store-mobile__chip">Sepet</NuxtLink>
          </div>
        </div>

        <div class="store-search store-search--mobile">
          <label class="sr-only" for="store-search-mobile">Urun ara</label>
          <input id="store-search-mobile" v-model="searchTerm" type="search" placeholder="Urun ara" />
          <NuxtLink :to="searchHref" class="store-search__button">Ara</NuxtLink>
        </div>

        <div class="store-mobile__categories">
          <NuxtLink
            v-for="item in categoryNavigation.slice(0, 4)"
            :key="item.to"
            :to="item.to"
            class="store-mobile__category"
          >
            {{ item.label }}
          </NuxtLink>
        </div>
      </div>
    </div>

    <nav class="store-mobile-nav" aria-label="Mobil menu">
      <NuxtLink
        v-for="item in mobileNavigation"
        :key="item.to"
        :to="item.to"
        class="store-mobile-nav__item"
        :class="{ 'is-active': isActive(item.to) }"
      >
        {{ item.label }}
      </NuxtLink>
    </nav>
  </header>
</template>

<style scoped>
.store-header {
  position: sticky;
  top: 0;
  z-index: 70;
  background: rgba(255, 255, 255, 0.97);
  border-bottom: 1px solid rgba(17, 24, 39, 0.08);
  box-shadow: 0 10px 28px rgba(17, 24, 39, 0.05);
  backdrop-filter: blur(12px);
}

.store-header__notice {
  background: #111827;
  color: rgba(255, 255, 255, 0.88);
  font-size: 0.8rem;
}

.store-header__notice-inner {
  min-height: 38px;
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: center;
}

.store-header__notice-inner p {
  margin: 0;
}

.store-header__notice-links {
  display: flex;
  gap: 14px;
  align-items: center;
  white-space: nowrap;
}

.store-header__desktop {
  display: grid;
  gap: 18px;
  padding: 18px 0 14px;
}

.store-header__brand-row {
  display: grid;
  grid-template-columns: 240px minmax(320px, 1fr) auto;
  gap: 18px;
  align-items: center;
}

.store-brand {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.store-brand__mark {
  width: 48px;
  height: 48px;
  border-radius: 18px;
  display: grid;
  place-items: center;
  background: linear-gradient(135deg, #111827, #374151);
  color: #fff;
  font-size: 1.2rem;
  font-weight: 800;
  box-shadow: 0 12px 24px rgba(17, 24, 39, 0.16);
}

.store-brand__copy {
  display: grid;
}

.store-brand__copy strong {
  font-size: 1.12rem;
  letter-spacing: -0.02em;
}

.store-brand__copy small {
  color: #6b7280;
  font-size: 0.8rem;
}

.store-search-shell {
  display: grid;
  gap: 10px;
}

.store-search {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 10px;
  align-items: center;
  padding: 8px;
  border-radius: 18px;
  background: #fff;
  border: 1px solid rgba(17, 24, 39, 0.08);
  box-shadow: 0 14px 28px rgba(17, 24, 39, 0.06);
}

.store-search input {
  min-width: 0;
  border: 0;
  outline: 0;
  background: transparent;
  padding: 0 12px;
  font-size: 0.96rem;
}

.store-search__button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 88px;
  height: 42px;
  padding: 0 16px;
  border-radius: 14px;
  background: #f4b400;
  color: #111827;
  font-weight: 800;
}

.store-search__quick {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  color: #6b7280;
  font-size: 0.84rem;
}

.store-search__quick-link {
  padding: 7px 10px;
  border-radius: 999px;
  background: #f8fafc;
  border: 1px solid rgba(17, 24, 39, 0.08);
  color: #374151;
  font-weight: 700;
}

.store-header__actions {
  display: flex;
  gap: 10px;
  align-items: center;
  justify-content: flex-end;
}

.store-action,
.store-header__cta,
.store-mobile__chip,
.store-mobile__category,
.store-mobile-nav__item,
.store-category-menu__trigger,
.store-category-menu__item,
.store-search__quick-link {
  transition:
    transform 0.18s ease,
    background 0.18s ease,
    border-color 0.18s ease,
    color 0.18s ease;
}

.store-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  height: 42px;
  padding: 0 14px;
  border-radius: 14px;
  background: #fff;
  border: 1px solid rgba(17, 24, 39, 0.08);
  color: #111827;
  font-weight: 700;
  white-space: nowrap;
}

.store-action--cart small {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 22px;
  height: 22px;
  padding: 0 6px;
  border-radius: 999px;
  background: #111827;
  color: #fff;
  font-size: 0.74rem;
  font-weight: 800;
}

.store-action.is-active {
  background: #f8fafc;
  border-color: rgba(17, 24, 39, 0.18);
}

.store-header__nav-row {
  min-height: 56px;
  display: flex;
  justify-content: space-between;
  gap: 18px;
  align-items: center;
  border-top: 1px solid rgba(17, 24, 39, 0.06);
  padding-top: 12px;
}

.store-header__nav-left,
.store-header__nav-right {
  display: flex;
  gap: 16px;
  align-items: center;
}

.store-header__meta {
  color: #6b7280;
  font-size: 0.84rem;
  white-space: nowrap;
}

.store-category-menu {
  position: relative;
}

.store-category-menu__trigger {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  height: 44px;
  padding: 0 16px;
  border: 1px solid rgba(17, 24, 39, 0.08);
  border-radius: 14px;
  background: #111827;
  color: #fff;
  font-weight: 700;
}

.store-category-menu__icon {
  font-size: 1rem;
  line-height: 1;
}

.store-category-menu__panel {
  position: absolute;
  left: 0;
  top: calc(100% + 10px);
  width: 280px;
  display: grid;
  gap: 6px;
  padding: 12px;
  border-radius: 20px;
  background: #fff;
  border: 1px solid rgba(17, 24, 39, 0.08);
  box-shadow: 0 18px 36px rgba(17, 24, 39, 0.12);
}

.store-category-menu__item {
  display: flex;
  align-items: center;
  min-height: 42px;
  padding: 0 12px;
  border-radius: 12px;
  color: #374151;
  font-weight: 600;
}

.store-nav {
  display: flex;
  gap: 24px;
  align-items: center;
}

.store-nav__link {
  position: relative;
  padding: 14px 0;
  font-size: 0.92rem;
  font-weight: 700;
  color: #374151;
}

.store-nav__link::after {
  content: "";
  position: absolute;
  left: 0;
  bottom: 6px;
  width: 100%;
  height: 2px;
  background: linear-gradient(90deg, #f4b400, #d93025);
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.18s ease;
}

.store-nav__link:hover::after,
.store-nav__link.is-active::after {
  transform: scaleX(1);
}

.store-header__cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 42px;
  padding: 0 16px;
  border-radius: 14px;
  background: #fff6d8;
  color: #111827;
  font-weight: 800;
  white-space: nowrap;
}

.store-mobile,
.store-mobile-nav {
  display: none;
}

.store-action:hover,
.store-header__cta:hover,
.store-category-menu__trigger:hover,
.store-category-menu__item:hover,
.store-mobile__chip:hover,
.store-mobile__category:hover,
.store-mobile-nav__item:hover,
.store-search__quick-link:hover {
  transform: translateY(-1px);
}

.store-category-menu__item:hover,
.store-mobile__category:hover {
  background: #f8fafc;
}

@media (max-width: 1024px) {
  .store-header__brand-row {
    grid-template-columns: 220px minmax(280px, 1fr);
  }

  .store-header__actions {
    grid-column: 1 / -1;
    justify-content: flex-start;
    flex-wrap: wrap;
  }

  .store-header__nav-row {
    flex-direction: column;
    align-items: flex-start;
  }

  .store-header__nav-left {
    width: 100%;
    flex-wrap: wrap;
  }

  .store-nav {
    flex-wrap: wrap;
    gap: 18px;
  }
}

@media (max-width: 640px) {
  .store-header__notice,
  .store-header__desktop {
    display: none;
  }

  .store-mobile {
    display: block;
    padding: 12px 0 10px;
    background: rgba(255, 255, 255, 0.97);
  }

  .store-mobile__inner {
    display: grid;
    gap: 10px;
  }

  .store-mobile__top {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 10px;
    align-items: center;
  }

  .store-brand--mobile .store-brand__mark {
    width: 42px;
    height: 42px;
    border-radius: 14px;
  }

  .store-mobile__actions {
    display: flex;
    gap: 8px;
  }

  .store-mobile__chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 38px;
    padding: 0 12px;
    border-radius: 12px;
    background: #fff;
    border: 1px solid rgba(17, 24, 39, 0.08);
    color: #111827;
    font-weight: 700;
  }

  .store-search--mobile {
    display: grid;
  }

  .store-mobile__categories {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding-bottom: 4px;
    scrollbar-width: none;
  }

  .store-mobile__categories::-webkit-scrollbar {
    display: none;
  }

  .store-mobile__category {
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 36px;
    padding: 0 12px;
    border-radius: 999px;
    background: #f8fafc;
    border: 1px solid rgba(17, 24, 39, 0.08);
    color: #374151;
    font-size: 0.84rem;
    font-weight: 700;
  }

  .store-mobile-nav {
    position: sticky;
    bottom: 0;
    z-index: 65;
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 6px;
    padding: 10px 12px calc(10px + env(safe-area-inset-bottom));
    border-top: 1px solid rgba(17, 24, 39, 0.08);
    background: rgba(255, 255, 255, 0.98);
  }

  .store-mobile-nav__item {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
    padding: 0 6px;
    border-radius: 12px;
    color: #6b7280;
    font-size: 0.8rem;
    font-weight: 700;
    text-align: center;
  }

  .store-mobile-nav__item.is-active {
    background: #fff6d8;
    color: #111827;
  }
}
</style>
