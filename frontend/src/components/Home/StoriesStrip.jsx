"use client";

import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useSelector } from "react-redux";
import appConfig from "@/appConfig";
import { resolveProductImageUrl } from "@/utils/productImage";
import { buildProductPath } from "@/utils/url";
import PriceDisplay from "@/components/Shared/PriceDisplay";
import auth from "@/utils/auth";

const ITEM_GAP = 16; // gap-4
const SPEED_PX_PER_SEC = 28; // yavaş sürekli kayma
const STORY_GUEST_KEY = "story_guest_key_v1";

function getStoryGuestKey() {
  if (typeof window === "undefined") return "";
  try {
    let key = localStorage.getItem(STORY_GUEST_KEY);
    if (!key) {
      key = `${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
      localStorage.setItem(STORY_GUEST_KEY, key);
    }
    return key;
  } catch {
    return "";
  }
}

function trackStoryView({
  storyId,
  productIndex = 0,
  productsTotal = 0,
  completed = false,
}) {
  if (!storyId) return;
  try {
    const session = auth();
    const token = session?.access_token || "";
    const headers = {
      Accept: "application/json",
      "Content-Type": "application/json",
    };
    if (token) headers.Authorization = `Bearer ${token}`;
    fetch(`${appConfig.BASE_URL}api/story-view`, {
      method: "POST",
      headers,
      body: JSON.stringify({
        story_id: storyId,
        platform: "web",
        guest_key: token ? null : getStoryGuestKey(),
        product_index: productIndex,
        products_total: productsTotal,
        completed: Boolean(completed),
      }),
      keepalive: true,
    }).catch(() => {});
  } catch {
    /* ignore */
  }
}

function storyImageUrl(image) {
  if (!image) return "";
  return resolveProductImageUrl(image);
}

function storyHref(story) {
  if (story.type === "product_feed") {
    return story.see_all_url || story.link || "/products";
  }
  return story.link || "#";
}

function StoryAvatar({ story, active }) {
  const src = storyImageUrl(story.image);
  const initial = String(story.title || "?").trim().charAt(0).toUpperCase();

  return (
    <span
      className={`box-border flex h-[68px] w-[68px] shrink-0 items-center justify-center rounded-full p-[2.5px] ${
        active ? "ring-2 ring-qyellow ring-offset-1" : ""
      }`}
      style={{ background: "#04334a" }}
    >
      <span className="relative block h-full w-full overflow-hidden rounded-full bg-white">
        {src ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img
            src={src}
            alt=""
            className="absolute inset-0 h-full w-full rounded-full object-cover object-center"
            draggable={false}
          />
        ) : (
          <span className="flex h-full w-full items-center justify-center rounded-full bg-[#04334a]/[0.06] text-lg font-800 text-[#04334a]">
            {initial}
          </span>
        )}
      </span>
    </span>
  );
}

function StoryItem({ story, isActive, onHover, onLeave, onClickProduct }) {
  const isProduct = story.type === "product_feed";
  const href = storyHref(story);

  const inner = (
    <span className="flex h-[102px] w-[72px] shrink-0 flex-col items-center">
      <StoryAvatar story={story} active={isActive} />
      <span className="mt-1.5 h-[30px] w-full text-center text-[11px] leading-tight font-600 text-[#04334a] line-clamp-2">
        {story.title}
      </span>
    </span>
  );

  if (isProduct) {
    return (
      <button
        type="button"
        className="outline-none shrink-0"
        onMouseEnter={() => onHover(story)}
        onFocus={() => onHover(story)}
        onMouseLeave={onLeave}
        onClick={() => onClickProduct(href)}
        aria-expanded={isActive}
        aria-haspopup="dialog"
      >
        {inner}
      </button>
    );
  }

  return (
    <Link
      href={href}
      className="shrink-0"
      onMouseEnter={() => onHover(null)}
    >
      {inner}
    </Link>
  );
}

function buildLoopSet(stories, viewportWidth) {
  if (!stories.length) return [];
  // Tek tur: ekranı dolduracak kadar tekrarla (sağda boşluk kalmasın)
  const itemW = 72 + ITEM_GAP;
  const minWidth = Math.max(viewportWidth * 1.2, itemW * stories.length);
  const needed = Math.max(1, Math.ceil(minWidth / (itemW * stories.length)));
  const set = [];
  for (let r = 0; r < needed; r += 1) {
    stories.forEach((story, index) => {
      set.push({
        ...story,
        _key: `u${r}-${story.id}-${index}`,
      });
    });
  }
  return set;
}

export default function StoriesStrip() {
  const router = useRouter();
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const stories = useMemo(() => {
    const raw =
      websiteSetup?.payload?.stories ||
      websiteSetup?.stories ||
      [];
    if (!Array.isArray(raw)) return [];
    return [...raw]
      .filter((s) => s && (s.status === undefined || Number(s.status) === 1))
      .filter((s) => s.show_on_web === undefined || Number(s.show_on_web) === 1 || s.show_on_web === true)
      .sort((a, b) => {
        const sa = Number(a?.serial ?? 0);
        const sb = Number(b?.serial ?? 0);
        if (sa !== sb) return sa - sb;
        return Number(a?.id ?? 0) - Number(b?.id ?? 0);
      });
  }, [websiteSetup]);

  const [activeId, setActiveId] = useState(null);
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(false);
  const [paused, setPaused] = useState(false);
  const [viewportW, setViewportW] = useState(1200);
  const activeIdRef = useRef(null);
  const productsRef = useRef([]);
  const closeTimer = useRef(null);
  const viewportRef = useRef(null);
  const trackRef = useRef(null);
  const offsetRef = useRef(0);
  const halfWidthRef = useRef(0);
  const rafRef = useRef(null);
  const lastTsRef = useRef(0);
  const pausedRef = useRef(false);

  useEffect(() => {
    activeIdRef.current = activeId;
  }, [activeId]);
  useEffect(() => {
    productsRef.current = products;
  }, [products]);

  const activeStory = stories.find((s) => s.id === activeId) || null;

  // İki özdeş tur yan yana → sola kayınca sağdan aynı düzen gelir, son yok
  const loopSet = useMemo(
    () => buildLoopSet(stories, viewportW),
    [stories, viewportW]
  );
  const displayStories = useMemo(() => {
    if (!loopSet.length) return [];
    const a = loopSet.map((s, i) => ({ ...s, _key: `a-${s._key}-${i}` }));
    const b = loopSet.map((s, i) => ({ ...s, _key: `b-${s._key}-${i}` }));
    return [...a, ...b];
  }, [loopSet]);

  useEffect(() => {
    pausedRef.current = paused || !!activeId;
  }, [paused, activeId]);

  useEffect(() => {
    const el = viewportRef.current;
    if (!el) return undefined;
    const measure = () => setViewportW(el.clientWidth || 1200);
    measure();
    const ro = typeof ResizeObserver !== "undefined" ? new ResizeObserver(measure) : null;
    if (ro) ro.observe(el);
    window.addEventListener("resize", measure);
    return () => {
      if (ro) ro.disconnect();
      window.removeEventListener("resize", measure);
    };
  }, [stories.length]);

  useEffect(() => {
    const track = trackRef.current;
    if (!track || !displayStories.length) return undefined;

    const measureHalf = () => {
      // İki eşit tur: yarısı bir tur genişliği
      halfWidthRef.current = track.scrollWidth / 2;
    };
    measureHalf();
    const t = window.setTimeout(measureHalf, 50);

    const tick = (ts) => {
      if (!lastTsRef.current) lastTsRef.current = ts;
      const dt = Math.min(64, ts - lastTsRef.current);
      lastTsRef.current = ts;

      if (!pausedRef.current) {
        const half = halfWidthRef.current;
        if (half > 0) {
          offsetRef.current += (SPEED_PX_PER_SEC * dt) / 1000;
          if (offsetRef.current >= half) {
            offsetRef.current -= half;
          }
          track.style.transform = `translate3d(${-offsetRef.current}px,0,0)`;
        }
      }

      rafRef.current = requestAnimationFrame(tick);
    };

    rafRef.current = requestAnimationFrame(tick);
    return () => {
      window.clearTimeout(t);
      if (rafRef.current) cancelAnimationFrame(rafRef.current);
      lastTsRef.current = 0;
    };
  }, [displayStories]);

  const clearCloseTimer = () => {
    if (closeTimer.current) {
      clearTimeout(closeTimer.current);
      closeTimer.current = null;
    }
  };

  const closePopup = useCallback(() => {
    clearCloseTimer();
    const id = activeIdRef.current;
    const list = productsRef.current;
    if (id) {
      const total = Array.isArray(list) ? list.length : 0;
      trackStoryView({
        storyId: id,
        productIndex: total > 0 ? total - 1 : 0,
        productsTotal: total,
        completed: total > 0,
      });
    }
    setActiveId(null);
    setProducts([]);
    setPaused(false);
  }, []);

  const scheduleClose = () => {
    clearCloseTimer();
    closeTimer.current = setTimeout(() => {
      closePopup();
    }, 100);
  };

  const loadProducts = useCallback(async (feed) => {
    if (!feed) return;
    setLoading(true);
    try {
      const res = await fetch(
        `${appConfig.BASE_URL}api/story-products?feed=${encodeURIComponent(feed)}&limit=16`
      );
      const data = await res.json();
      setProducts(Array.isArray(data?.products) ? data.products : []);
    } catch {
      setProducts([]);
    } finally {
      setLoading(false);
    }
  }, []);

  const openHover = (story) => {
    clearCloseTimer();
    setPaused(true);
    if (!story || story.type !== "product_feed") {
      closePopup();
      return;
    }
    const prev = activeIdRef.current;
    if (prev && prev !== story.id) {
      const list = productsRef.current;
      const total = Array.isArray(list) ? list.length : 0;
      trackStoryView({
        storyId: prev,
        productIndex: total > 0 ? total - 1 : 0,
        productsTotal: total,
        completed: total > 0,
      });
    }
    setActiveId(story.id);
    loadProducts(story.feed || "popular");
  };

  useEffect(() => () => clearCloseTimer(), []);

  useEffect(() => {
    if (!activeId) return undefined;
    const onKey = (e) => {
      if (e.key === "Escape") closePopup();
    };
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, [activeId, closePopup]);

  if (!stories.length) {
    return null;
  }

  const seeAllHref = storyHref(activeStory || {});

  return (
    <section
      className="relative z-20 w-full"
      onMouseLeave={scheduleClose}
      onMouseEnter={clearCloseTimer}
    >
      <div className="container-x mx-auto">
      <div
        ref={viewportRef}
        className="w-full overflow-hidden rounded-2xl bg-[#f4f7f9] border border-[#04334a]/10 py-3 md:py-3.5"
        onMouseEnter={() => setPaused(true)}
        onMouseLeave={() => {
          if (!activeId) setPaused(false);
        }}
      >
        <div
          ref={trackRef}
          className="flex flex-nowrap items-start gap-4 will-change-transform"
          style={{ width: "max-content" }}
        >
          {displayStories.map((story) => (
            <StoryItem
              key={story._key}
              story={story}
              isActive={activeId === story.id}
              onHover={openHover}
              onLeave={() => {}}
              onClickProduct={(href) => router.push(href)}
            />
          ))}
        </div>
      </div>
      </div>

      {activeStory && activeStory.type === "product_feed" ? (
        <div
          className="absolute left-0 right-0 top-full z-50 container-x mx-auto pb-4"
          onMouseEnter={clearCloseTimer}
          role="dialog"
          aria-label={activeStory.title}
        >
          <div
            className="w-full bg-white rounded-2xl border border-[#04334a]/12 overflow-hidden"
            style={{ boxShadow: "0 16px 48px rgba(4, 51, 74, 0.18)" }}
          >
            <div className="flex items-center justify-between gap-3 px-4 py-3 border-b border-[#04334a]/10 bg-[#04334a]/[0.03]">
              <h3 className="text-sm font-800 text-[#04334a] truncate">
                {activeStory.title}
              </h3>
              <Link
                href={seeAllHref}
                className="shrink-0 text-xs font-800 text-[#04334a] bg-qyellow px-3 py-1.5 rounded-lg hover:brightness-95"
                onClick={closePopup}
              >
                Tümünü gör
              </Link>
            </div>

            <div className="flex gap-3 p-4 overflow-x-auto overflow-style-none scroll-smooth">
              {loading ? (
                <p className="text-sm text-[#04334a]/50 py-8 px-2">
                  Yükleniyor…
                </p>
              ) : products.length === 0 ? (
                <p className="text-sm text-[#04334a]/50 py-8 px-2">
                  Ürün bulunamadı
                </p>
              ) : (
                products.map((product) => {
                  const hasOffer =
                    product.offer_price &&
                    Number(product.offer_price) > 0 &&
                    Number(product.offer_price) < Number(product.price);

                  return (
                    <Link
                      key={product.id}
                      href={buildProductPath(product.slug)}
                      className="w-[140px] md:w-[156px] shrink-0 rounded-xl bg-white border border-[#04334a]/10 overflow-hidden hover:shadow-md transition-shadow"
                      onClick={closePopup}
                    >
                      <div className="aspect-square bg-neutral-50">
                        {/* eslint-disable-next-line @next/next/no-img-element */}
                        <img
                          src={resolveProductImageUrl(product.thumb_image)}
                          alt=""
                          className="h-full w-full object-cover"
                        />
                      </div>
                      <div className="p-2.5">
                        <p className="text-[12px] font-600 text-[#04334a] line-clamp-2 min-h-[32px]">
                          {product.short_name || product.name}
                        </p>
                        <div className="mt-1.5">
                          <PriceDisplay
                            price={product.price}
                            offerPrice={hasOffer ? product.offer_price : null}
                            size="sm"
                            layout="stack"
                          />
                        </div>
                      </div>
                    </Link>
                  );
                })
              )}
            </div>
          </div>
        </div>
      ) : null}
    </section>
  );
}
