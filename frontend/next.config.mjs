import { fileURLToPath } from "node:url";

/** @type {import('next').NextConfig} */
// Production build için - sunucudaki API kullanılacak
const baseUrl = process.env.NEXT_PUBLIC_BASE_URL || "https://admin.seyfibaba.com";
const { hostname, protocol } = new URL(baseUrl);

// next/image: build sırasında NEXT_PUBLIC_BASE_URL yanlış kalırsa bile canlı görseller izinli olsun.
// Ek hostlar: NEXT_IMAGE_EXTRA_HOSTS=cdn1.com,cdn2.com (virgülle)
const DEFAULT_IMAGE_HOSTS = [
  "admin.seyfibaba.com",
  "seyfibaba.com",
  "www.seyfibaba.com",
  "cdn.dsmcdn.com",
  "*.dsmcdn.com",
];
const extraImageHosts = String(process.env.NEXT_IMAGE_EXTRA_HOSTS || "")
  .split(",")
  .map((h) => h.trim())
  .filter(Boolean);
const imageHostSet = new Set([hostname, ...DEFAULT_IMAGE_HOSTS, ...extraImageHosts]);

/**
 * @returns {import('next').NextConfig['images']['remotePatterns']}
 */
function buildImageRemotePatterns() {
  /** @type {import('next').NextConfig['images']['remotePatterns']} */
  const patterns = [];
  for (const host of imageHostSet) {
    if (!host) continue;
    patterns.push({
      protocol: "https",
      hostname: host,
      pathname: "/**",
    });
    if (isLoopbackHost(host)) {
      patterns.push({
        protocol: "http",
        hostname: host,
        pathname: "/**",
      });
    }
  }
  try {
    patterns.push(buildRemotePattern(baseUrl));
  } catch {
    /* ignore invalid baseUrl */
  }
  for (const alias of localBackendAliases) {
    try {
      patterns.push(buildRemotePattern(alias));
    } catch {
      /* ignore invalid alias */
    }
  }
  return patterns;
}

// Bu VPS'te admin.seyfibaba.com hosts ile 127.0.0.1'e düşer.
// Next image optimizer sunucudan çekerken private IP diye keser; tarayıcıdan direkt URL çalışır.
// Optimizer'ı kapatmak kalıcı çözüm (NEXT_IMAGE_UNOPTIMIZED=0 ile tekrar açılır).
const imageUnoptimized = process.env.NEXT_IMAGE_UNOPTIMIZED !== "0";
const backendPort =
  new URL(baseUrl).port || (protocol === "https:" ? "443" : "80");
const backendOrigin = `${protocol}//${hostname}${backendPort ? `:${backendPort}` : ""}`;
const localBackendAliases = isLoopbackHost(hostname)
  ? getLoopbackAliases(protocol, backendPort)
  : [];
const connectSrcOrigins = [backendOrigin, ...localBackendAliases].join(" ");
const isDev = process.env.NODE_ENV === "development";

function isLoopbackHost(value) {
  return value === "127.0.0.1" || value === "localhost";
}

function getLoopbackAliases(scheme, port) {
  const resolvedPort = port ? `:${port}` : "";
  return [
    `${scheme}//127.0.0.1${resolvedPort}`,
    `${scheme}//localhost${resolvedPort}`,
  ];
}

function buildRemotePattern(urlString) {
  const parsed = new URL(urlString);
  return {
    protocol: parsed.protocol.replace(":", ""),
    hostname: parsed.hostname,
    ...(parsed.port ? { port: parsed.port } : {}),
  };
}

const contentSecurityPolicy = [
  `default-src 'self' https: ${isDev ? "http:" : ""} data: blob: 'unsafe-inline' 'unsafe-eval'`,
  `img-src 'self' https: ${isDev ? "http:" : ""} data: blob:`,
  `media-src 'self' https: ${isDev ? "http:" : ""} data: blob:`,
  "font-src 'self' https: data:",
  "style-src 'self' https: 'unsafe-inline'",
  `script-src 'self' https: ${isDev ? "http:" : ""} 'unsafe-inline' 'unsafe-eval'`,
  `connect-src 'self' https: ${isDev ? "http:" : ""} wss: ${isDev ? "ws:" : ""} ${connectSrcOrigins}`,
  "frame-src 'self' https://www.google.com https://*.google.com https://maps.google.com https://*.google.de https://www.youtube.com",
  "object-src 'none'",
  "base-uri 'self'",
  "form-action 'self' https:",
  "frame-ancestors 'self'",
  "upgrade-insecure-requests",
].join("; ");

const nextConfig = {
  // Standalone mod - runtime'da API çek
  output: "standalone",
  poweredByHeader: false,
  reactStrictMode: true,
  turbopack: {
    root: fileURLToPath(new URL(".", import.meta.url)),
  },
  experimental: {
    optimizePackageImports: [
      "@fortawesome/free-solid-svg-icons",
      "@fortawesome/free-brands-svg-icons",
      "react-toastify",
      "react-share",
      "date-fns",
      "swiper",
      "yet-another-react-lightbox",
      "react-facebook-pixel",
      "cookies-next",
    ],
  },
  images: {
    unoptimized: imageUnoptimized,
    // admin.seyfibaba.com hosts'ta 127.0.0.1'e gider; Next optimizer SSRF diye keser.
    dangerouslyAllowLocalIP: true,
    formats: ["image/avif", "image/webp"],
    minimumCacheTTL: 86400,
    deviceSizes: [640, 750, 828, 1080, 1200, 1920],
    imageSizes: [16, 32, 48, 64, 96, 128, 256],
    qualities: [60, 75],
    remotePatterns: buildImageRemotePatterns(),
  },
  async redirects() {
    return [
      // /profile/address gibi doğrudan bağlantıları hash tabına yönlendir
      {
        source: "/profile/:tab(dashboard|profile|order|address|wishlist|reviews|password|second-hand)",
        destination: "/profile#:tab",
        permanent: false,
      },
    ];
  },
  async headers() {
    return [
      {
        source: "/(.*)",
        headers: [
          { key: "Strict-Transport-Security", value: "max-age=31536000; includeSubDomains; preload" },
          { key: "Content-Security-Policy", value: contentSecurityPolicy },
          { key: "X-Content-Type-Options", value: "nosniff" },
          { key: "X-Frame-Options", value: "SAMEORIGIN" },
          { key: "X-XSS-Protection", value: "1; mode=block" },
          { key: "Referrer-Policy", value: "strict-origin-when-cross-origin" },
          { key: "Permissions-Policy", value: "camera=(), microphone=(), geolocation=()" },
        ],
      },
      // /_next/static ve /_next/image Cache-Control Next.js tarafından yönetilir — custom header kaldırıldı
      {
        source: "/",
        headers: [
          { key: "Cache-Control", value: "public, s-maxage=60, stale-while-revalidate=120" },
        ],
      },
      {
        source: "/about",
        headers: [
          { key: "Cache-Control", value: "public, s-maxage=300, stale-while-revalidate=600" },
        ],
      },
      {
        source: "/products",
        headers: [
          { key: "Cache-Control", value: "public, s-maxage=300, stale-while-revalidate=600" },
        ],
      },
      {
        source: "/search",
        headers: [
          { key: "Cache-Control", value: "public, s-maxage=300, stale-while-revalidate=600" },
        ],
      },
      {
        source: "/urun/:slug*",
        headers: [
          { key: "Cache-Control", value: "public, s-maxage=300, stale-while-revalidate=600" },
        ],
      },
    ];
  },
};

export default nextConfig;
