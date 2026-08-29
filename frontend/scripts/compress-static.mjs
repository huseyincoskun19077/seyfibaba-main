import { existsSync, readdirSync, readFileSync, statSync, writeFileSync } from "node:fs";
import { join, extname } from "node:path";
import { brotliCompressSync, constants, gzipSync } from "node:zlib";

const STATIC_DIR = ".next/static";
const COMPRESSIBLE_EXTENSIONS = new Set([".js", ".css", ".json", ".svg"]);

function getStaticFiles(directory) {
  const entries = readdirSync(directory);
  const files = [];

  for (const entry of entries) {
    const fullPath = join(directory, entry);
    const stats = statSync(fullPath);

    if (stats.isDirectory()) {
      files.push(...getStaticFiles(fullPath));
      continue;
    }

    if (COMPRESSIBLE_EXTENSIONS.has(extname(fullPath))) {
      files.push(fullPath);
    }
  }

  return files;
}

function compressFile(filePath) {
  const source = readFileSync(filePath);

  writeFileSync(`${filePath}.gz`, gzipSync(source, { level: 9 }));
  writeFileSync(
    `${filePath}.br`,
    brotliCompressSync(source, {
      params: {
        [constants.BROTLI_PARAM_QUALITY]: 11,
      },
    })
  );
}

console.log("Compressing static assets...");

if (!existsSync(STATIC_DIR)) {
  console.log(`Skipped: '${STATIC_DIR}' not found.`);
  process.exit(0);
}

const files = getStaticFiles(STATIC_DIR);

for (const file of files) {
  compressFile(file);
}

console.log("Done. Compressed files:");
console.log(`gzip files: ${files.length}`);
console.log(`brotli files: ${files.length}`);
