#!/bin/bash
# Pre-compress static files for production
echo "Compressing static assets..."
find .next/static -type f \( -name "*.js" -o -name "*.css" -o -name "*.json" -o -name "*.svg" \) | while read f; do
  gzip -9 -k -f "$f" 2>/dev/null
  node --input-type=module -e "import { readFileSync, writeFileSync } from 'fs'; import { brotliCompressSync, constants } from 'zlib'; const input = process.argv[1]; const data = readFileSync(input); const output = brotliCompressSync(data, { params: { [constants.BROTLI_PARAM_QUALITY]: 11 } }); writeFileSync(input + '.br', output);" "$f" 2>/dev/null
done
echo "Done. Compressed files:"
echo "gzip files: $(find .next/static -name '*.gz' | wc -l)"
echo "brotli files: $(find .next/static -name '*.br' | wc -l)"
