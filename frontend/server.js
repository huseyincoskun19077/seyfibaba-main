import { createServer } from "http";
import { parse } from "url";
import { createReadStream, existsSync, statSync } from "fs";
import { join, resolve, sep } from "path";
import {
  constants as zlibConstants,
  createBrotliCompress,
  createGzip,
} from "zlib";
import httpProxy from "http-proxy";
import next from "next";

const port = parseInt(process.env.PORT || "3001", 10);
const backendPort = parseInt(process.env.BACKEND_PORT || "8000", 10);
const proxyTarget =
  process.env.BACKEND_PROXY_TARGET || `http://127.0.0.1:${backendPort}`;
const proxyHost = String(process.env.BACKEND_PROXY_HOST || "").trim();

const app = next({ dev: false });
const handle = app.getRequestHandler();

const proxy = httpProxy.createProxyServer({
  target: proxyTarget,
  changeOrigin: !proxyHost,
  secure: false,
  headers: proxyHost ? { Host: proxyHost } : {},
});

const laravelPublic = resolve(join(process.cwd(), "..", "backend", "public"));

// Proxy hata handler - backend erişilemez olsa bile server crash etmesin
proxy.on("error", (err, req, res) => {
  console.error(`Proxy error for ${req.url}:`, err.message);
  if (res && !res.headersSent) {
    res.writeHead(502, { "Content-Type": "application/json" });
    res.end(JSON.stringify({ error: "Backend unavailable" }));
  }
});

const MIME_TYPES = {
  ".js": "application/javascript",
  ".css": "text/css",
  ".json": "application/json",
  ".svg": "image/svg+xml",
  ".html": "text/html",
  ".txt": "text/plain",
  ".map": "application/json",
  ".woff2": "font/woff2",
  ".woff": "font/woff",
  ".ttf": "font/ttf",
  ".png": "image/png",
  ".jpg": "image/jpeg",
  ".jpeg": "image/jpeg",
  ".webp": "image/webp",
  ".avif": "image/avif",
  ".ico": "image/x-icon",
};

const COMPRESSIBLE = new Set([".js", ".css", ".json", ".svg", ".html", ".txt", ".map"]);

const staticDir = join(process.cwd(), ".next", "static");

function getExtension(path) {
  const i = path.lastIndexOf(".");
  return i > 0 ? path.substring(i) : "";
}

app.prepare().then(() => {
  createServer((req, res) => {
    const parsedUrl = parse(req.url, true);
    const { pathname } = parsedUrl;

    // Redirect /api/ (with trailing slash) to /api
    if (pathname === "/api/") {
      res.writeHead(301, { Location: "/api" });
      res.end();
      return;
    }

    // Next.js kendi API route'ları - Laravel'e proxy'lenmemeli
    const NEXTJS_API_ROUTES = ["/api/indexnow", "/api/log-error", "/api/revalidate"];
    const isNextjsApiRoute = NEXTJS_API_ROUTES.some((r) => pathname === r || pathname.startsWith(r + "/"));

    // Proxy /api/* requests to Laravel backend (Next.js API route'ları hariç)
    if (!isNextjsApiRoute && (pathname.startsWith("/api/") || pathname === "/api")) {
      proxy.web(req, res);
      return;
    }

    if (pathname.startsWith("/uploads/")) {
      const decoded = decodeURIComponent(pathname);
      const filePath = resolve(join(laravelPublic, decoded));
      const rootWithSep = laravelPublic.endsWith(sep)
        ? laravelPublic
        : laravelPublic + sep;
      if (filePath === laravelPublic || filePath.startsWith(rootWithSep)) {
        try {
          if (existsSync(filePath) && statSync(filePath).isFile()) {
            const ext = getExtension(filePath).toLowerCase();
            res.setHeader(
              "Content-Type",
              MIME_TYPES[ext] || "application/octet-stream"
            );
            res.setHeader("Cache-Control", "public, max-age=86400");
            res.setHeader("Content-Length", statSync(filePath).size);
            res.writeHead(200);
            createReadStream(filePath).pipe(res);
            return;
          }
        } catch {
          /* fall through to proxy */
        }
      }
      proxy.web(req, res);
      return;
    }

    // Handle _next/static files with compression
    if (pathname.startsWith("/_next/static/")) {
      const relativePath = pathname.replace("/_next/static/", "");
      const filePath = join(staticDir, relativePath);

      if (existsSync(filePath)) {
        const ext = getExtension(filePath);
        const contentType = MIME_TYPES[ext] || "application/octet-stream";
        const acceptEncoding = req.headers["accept-encoding"] || "";

        res.setHeader("Content-Type", contentType);
        res.setHeader("Cache-Control", "public, max-age=31536000, immutable");
        res.setHeader("Vary", "Accept-Encoding");

        if (COMPRESSIBLE.has(ext) && acceptEncoding.includes("br")) {
          const brPath = filePath + ".br";
          if (existsSync(brPath)) {
            res.setHeader("Content-Encoding", "br");
            res.setHeader("Content-Length", statSync(brPath).size);
            res.writeHead(200);
            createReadStream(brPath).pipe(res);
            return;
          }

          res.setHeader("Content-Encoding", "br");
          res.writeHead(200);
          createReadStream(filePath)
            .pipe(
              createBrotliCompress({
                params: {
                  [zlibConstants.BROTLI_PARAM_QUALITY]: 5,
                },
              })
            )
            .pipe(res);
          return;
        }

        if (COMPRESSIBLE.has(ext) && acceptEncoding.includes("gzip")) {
          const gzPath = filePath + ".gz";
          if (existsSync(gzPath)) {
            // Serve pre-compressed file
            res.setHeader("Content-Encoding", "gzip");
            res.setHeader("Content-Length", statSync(gzPath).size);
            res.writeHead(200);
            createReadStream(gzPath).pipe(res);
            return;
          }
          // On-the-fly gzip
          res.setHeader("Content-Encoding", "gzip");
          res.writeHead(200);
          createReadStream(filePath).pipe(createGzip({ level: 6 })).pipe(res);
          return;
        }

        // No compression - serve directly
        res.setHeader("Content-Length", statSync(filePath).size);
        res.writeHead(200);
        createReadStream(filePath).pipe(res);
        return;
      }
    }

    // All other requests go to Next.js
    handle(req, res, parsedUrl);
  }).listen(port, () => {
    console.log(`> Ready on http://localhost:${port}`);
    console.log(`> API proxy -> ${proxyTarget}${proxyHost ? ` Host:${proxyHost}` : ""}`);
    console.log(`> Uploads dir -> ${laravelPublic}`);
  });
});
