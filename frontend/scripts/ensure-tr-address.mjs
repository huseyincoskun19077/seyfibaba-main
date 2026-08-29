/**
 * Build öncesi: public/data/tr-turkiye-address.json yoksa indirir.
 */
import { execSync } from "child_process";
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";

const root = path.join(path.dirname(fileURLToPath(import.meta.url)), "..");
const dest = path.join(root, "public/data/tr-turkiye-address.json");

if (fs.existsSync(dest)) {
  process.exit(0);
}

console.log("tr-turkiye-address.json bulunamadı, indiriliyor…");
execSync("node scripts/fetch-tr-address.mjs", { stdio: "inherit", cwd: root });
