/**
 * GitHub: hsndmr/turkiye-city-county-district-neighborhood — data.json (~3.5 MB)
 * Çıktı: public/data/tr-turkiye-address.json (CDN yerine yerel servis için)
 */
import fs from "fs";
import https from "https";
import path from "path";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const DEST = path.join(__dirname, "../public/data/tr-turkiye-address.json");
const URL =
  "https://raw.githubusercontent.com/hsndmr/turkiye-city-county-district-neighborhood/main/data.json";

function download(url, dest) {
  return new Promise((resolve, reject) => {
    const file = fs.createWriteStream(dest);
    https
      .get(url, (res) => {
        if (res.statusCode !== 200) {
          reject(new Error(`HTTP ${res.statusCode}`));
          return;
        }
        res.pipe(file);
        file.on("finish", () => file.close(resolve));
      })
      .on("error", (err) => {
        fs.unlink(dest, () => reject(err));
      });
  });
}

fs.mkdirSync(path.dirname(DEST), { recursive: true });
console.log("İndiriliyor:", URL);
await download(URL, DEST);
console.log("Kaydedildi:", DEST);
