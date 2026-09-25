import { formatMoneyTR } from "@/utils/priceFormat";

function CurrencyConvert({ price }) {
  return (
    <span className="sb-price__current notranslate" suppressHydrationWarning>
      {formatMoneyTR(price ?? 0)}
    </span>
  );
}

export default CurrencyConvert;
