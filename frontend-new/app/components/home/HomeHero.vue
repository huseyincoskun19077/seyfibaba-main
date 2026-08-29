<script setup lang="ts">
import type { ServiceItem, SliderItem } from "~/types/home";
import { isEnabled, resolveMediaUrl } from "~/utils/media";

const props = defineProps<{
  slides: SliderItem[];
  services: ServiceItem[];
  sidebarOne?: SliderItem | null;
  sidebarTwo?: SliderItem | null;
}>();

const config = useRuntimeConfig();
const currentSlide = ref(0);
let intervalHandle: ReturnType<typeof setInterval> | null = null;
const featurePills = [
  "Koltuk, banko, dolap ve aksesuar",
  "Kurumsal teklif ve toplu alim destegi",
  "Salon kurulumuna uygun kategori akisi",
];
const heroStats = [
  { label: "Aktif kategori", value: "120+" },
  { label: "Salon projesi", value: "2.400+" },
  { label: "Teklif donusu", value: "24 saat" },
];

const visibleSlides = computed(() =>
  (props.slides || []).filter((slide) => Boolean(slide?.image))
);

const sideCards = computed(() =>
  [props.sidebarOne, props.sidebarTwo].filter(
    (item): item is SliderItem => Boolean(item?.image) && isEnabled(item?.status ?? 1)
  )
);

const activeSlide = computed(() => visibleSlides.value[currentSlide.value] || null);

const advance = () => {
  if (visibleSlides.value.length <= 1) return;
  currentSlide.value = (currentSlide.value + 1) % visibleSlides.value.length;
};

onMounted(() => {
  if (visibleSlides.value.length > 1) {
    intervalHandle = setInterval(advance, 4000);
  }
});

onBeforeUnmount(() => {
  if (intervalHandle) clearInterval(intervalHandle);
});
</script>

<template>
  <section class="hero">
    <div class="container">
      <div class="hero__layout">
        <div class="hero__slider">
          <Transition name="fade" mode="out-in">
            <div v-if="activeSlide" :key="activeSlide.id || currentSlide" class="hero__slide">
              <img
                class="hero__slide-image"
                :src="resolveMediaUrl(activeSlide.image, config.public.imageBase)"
                :alt="activeSlide.title_one || activeSlide.title_two || 'Seyfibaba slider'"
              />
              <div class="hero__overlay">
                <div class="hero__content">
                  <span v-if="activeSlide.badge" class="hero__badge">{{ activeSlide.badge }}</span>
                  <div class="hero__eyebrow">Seyfibaba Storefront</div>
                  <h1 class="hero__title">
                    {{ activeSlide.title_one || "Berber ve Kuafor Ekipmanlari" }}
                  </h1>
                  <p class="hero__text">
                    {{ activeSlide.title_two || "Profesyonel salon ekipmanlarini tek yerde inceleyin." }}
                  </p>
                  <div class="hero__actions">
                    <NuxtLink class="hero__button" :to="activeSlide.product_slug ? `/urun/${activeSlide.product_slug}` : '/products'">
                      Simdi Alisveris Yap
                    </NuxtLink>
                    <NuxtLink class="hero__button hero__button--ghost" to="/products">
                      Kategorileri Incele
                    </NuxtLink>
                  </div>
                  <div class="hero__pill-list">
                    <span v-for="item in featurePills" :key="item" class="hero__pill">{{ item }}</span>
                  </div>
                </div>
              </div>
            </div>
          </Transition>

          <div v-if="visibleSlides.length > 1" class="hero__dots">
            <button
              v-for="(_, index) in visibleSlides"
              :key="index"
              type="button"
              class="hero__dot"
              :class="{ 'hero__dot--active': index === currentSlide }"
              @click="currentSlide = index"
            />
          </div>
        </div>

        <div v-if="sideCards.length" class="hero__sidecards">
          <NuxtLink
            v-for="card in sideCards"
            :key="card.id || card.image"
            class="hero__sidecard"
            :to="card.product_slug ? `/urun/${card.product_slug}` : '/products'"
          >
            <img
              :src="resolveMediaUrl(card.image, config.public.imageBase)"
              :alt="card.title_one || card.title_two || 'Seyfibaba kampanya gorseli'"
            />
            <div class="hero__sidecard-content">
              <span class="hero__sidecard-label">One Cikan Categori</span>
              <strong>{{ card.title_one || "Kampanyayi Incele" }}</strong>
              <small>Projeye uygun urun secimlerini gor</small>
            </div>
          </NuxtLink>
        </div>
      </div>

      <div class="hero__stats">
        <article v-for="item in heroStats" :key="item.label" class="hero__stat">
          <strong>{{ item.value }}</strong>
          <span>{{ item.label }}</span>
        </article>
      </div>

      <div v-if="services.length" class="service-strip">
        <article v-for="service in services" :key="service.id" class="service-strip__item">
          <div class="service-strip__icon">{{ service.icon || "*" }}</div>
          <div>
            <h3>{{ service.title }}</h3>
            <p>{{ service.description }}</p>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>
