import React, { useEffect, useState } from "react";
import { useSelector } from "react-redux";
import CurrencyConvert from "./CurrencyConvert";

function CheckProductIsExistsInFlashSale({
  id,
  price,
  sign = true,
  className,
}) {
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const [flashSale, setData] = useState(null);
  const [calPrice, setPrice] = useState(null);
  useEffect(() => {
    if (websiteSetup) {
      setData({
        flashSale: websiteSetup.payload.flashSale,
        flashSaleActive: websiteSetup.payload.flashSaleActive,
        flashSaleProducts: websiteSetup.payload.flashSaleProducts,
      });
    }
  }, [websiteSetup]);
  const calcProductPrice = (id, price) => {
    // console.log(id, price);
    // console.log(flashSale);
    if (flashSale && flashSale.flashSaleActive) {
      const getId = flashSale.flashSaleProducts.find(
        (item) => parseInt(item.product_id) === parseInt(id)
      );
      if (getId) {
        const offer = parseInt(flashSale.flashSale.offer);
        const discountPrice = (offer / 100) * price;
        const mainPrice = parseFloat(price) - discountPrice;
        setPrice(mainPrice);
      } else {
        setPrice(price);
      }
    } else {
      setPrice(price);
    }
  };
  useEffect(() => {
    if (id && price) {
      calcProductPrice(id, price);
    }
  });
  return <CurrencyConvert price={parseFloat(calPrice)} />;
}

export default CheckProductIsExistsInFlashSale;
