export function isFlashSaleActive(flashSale) {
  if (!flashSale || Number(flashSale.status) !== 1) return false;
  if (!flashSale.end_time) return false;
  const endMs = new Date(flashSale.end_time).getTime();
  return Number.isFinite(endMs) && endMs > Date.now();
}
