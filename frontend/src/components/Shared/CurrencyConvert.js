function CurrencyConvert({ price }) {
  // TL (Türk Lirası) sabit para birimi - ₺ simgesi kullanılıyor
  // Fixed currency: TL (Turkish Lira) - using ₺ symbol
  const TL_SYMBOL = "₺";
  const TL_POSITION = "left"; // Simge solda gösterilecek
  
  if (price) {
    const priceTypeConst = parseFloat(price).toFixed(2);
    
    // TL formatında göster (simge solda)
    if (TL_POSITION === "left") {
      return (
        <span className="notranslate">{`${TL_SYMBOL}${priceTypeConst}`}</span>
      );
    } else {
      return (
        <span className="notranslate">{`${priceTypeConst}${TL_SYMBOL}`}</span>
      );
    }
  } else {
    return <span className="notranslate">{TL_SYMBOL}0</span>;
  }
}

export default CurrencyConvert;
