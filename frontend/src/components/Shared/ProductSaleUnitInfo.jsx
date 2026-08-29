import CurrencyConvert from "@/components/Shared/CurrencyConvert";
import {
  getSaleUnitQty,
  getUnitPrice,
  isEffectiveOfferPrice,
} from "@/utils/productPricing";

export default function ProductSaleUnitInfo({
  product,
  price,
  offerPrice,
  className = "",
}) {
  const units = getSaleUnitQty(product);
  if (units <= 1) return null;

  const packPrice = isEffectiveOfferPrice(offerPrice, price)
    ? Number(offerPrice)
    : Number(price);
  const unitPrice = getUnitPrice(packPrice, units);
  if (unitPrice == null) return null;

  return (
    <p
      className={`text-[10px] md:text-xs text-[#707070] font-600 leading-snug ${className}`.trim()}
    >
      Birim <CurrencyConvert price={unitPrice} /> · x{units} adet
    </p>
  );
}
