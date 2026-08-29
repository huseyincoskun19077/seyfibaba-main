import { createServer } from "http";
import { parse } from "url";
import httpProxy from "http-proxy";
import next from "next";

const port = parseInt(process.env.PORT || "3001", 10);
const backendPort = parseInt(process.env.BACKEND_PORT || "8000", 10);

// Development modunda çalıştır
const dev = true;
const app = next({ dev });
const handle = app.getRequestHandler();

const proxy = httpProxy.createProxyServer({
  target: `http://127.0.0.1:${backendPort}`,
  changeOrigin: true,
});

app.prepare().then(() => {
  createServer((req, res) => {
    const parsedUrl = parse(req.url, true);
    const { pathname } = parsedUrl;

    if (pathname.startsWith("/api/") || pathname === "/api") {
      proxy.web(req, res);
      return;
    }

    if (pathname.startsWith("/uploads/")) {
      proxy.web(req, res);
      return;
    }

    handle(req, res, parsedUrl);
  }).listen(port, () => {
    console.log(`> Ready on http://localhost:${port} (dev mode)`);
    console.log(`> API proxy -> http://127.0.0.1:${backendPort}`);
  });
});