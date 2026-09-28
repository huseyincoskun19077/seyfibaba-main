import React from "react";
import wordCount from "@/utils/wordCount";
import ServeLangItem from "@/components/Helpers/ServeLangItem";
import CurrencyConvert from "@/components/Shared/CurrencyConvert";
import CheckProductIsExistsInFlashSale from "@/components/Shared/CheckProductIsExistsInFlashSale";
import FreeShippingBar from "@/components/Shared/FreeShippingBar";
import { calculateProductPrice } from "../utils/checkoutUtils";

const OrderSummary = ({
  // Cart data
  carts,
  subTotal,
  totalPrice,
  discountCoupon,
  mainTotalPrice,

  // Shipping data
  shippingRulesByCityId,
  selectedRule,
  shippingCharge,
  locationShippingPrice,
  webSettings,
  sellerShippingGroups = [],

  // Shipping handlers
  selectedRuleHandler,

  // Product calculations
  price,
  totalWeight,
  totalQty,
}) => {
  /**
   * Render shipping rules based on type
   * @param {Object} rule - Shipping rule object
   * @param {number} index - Rule index
   * @returns {JSX.Element} Shipping rule component
   */
  const renderShippingRule = (rule, index) => {
    const isSelected = selectedRule === rule.id.toString();

    // Check if rule conditions are met based on type
    let isConditionMet = false;
    let conditionValue = 0;

    if (rule.type === "base_on_price") {
      conditionValue = totalPrice;
      isConditionMet =
        parseInt(rule.condition_from) <= parseInt(totalPrice) &&
        (parseInt(rule.condition_to) >= parseInt(totalPrice) ||
          parseInt(rule.condition_to) === -1);
    } else if (rule.type === "base_on_weight") {
      conditionValue = totalWeight;
      isConditionMet =
        parseInt(rule.condition_from) <= parseInt(totalWeight) &&
        (parseInt(rule.condition_to) >= parseInt(totalWeight) ||
          parseInt(rule.condition_to) === -1);
    } else if (rule.type === "base_on_qty") {
      conditionValue = totalQty;
      isConditionMet =
        parseInt(rule.condition_from) <= totalQty &&
        (parseInt(rule.condition_to) >= totalQty ||
          parseInt(rule.condition_to) === -1);
    }

    if (!isConditionMet) return null;

    return (
      <div key={index} className="flex justify-between items-center">
        <div className="flex space-x-2.5 rtl:space-x-reverse items-center">
          <div className="input-radio">
            <input
              onChange={(e) => selectedRuleHandler(e, rule.shipping_fee)}
              value={rule.id}
              type="radio"
              name="price"
              className="accent-pink-500"
              checked={isSelected}
            />
          </div>
          <span className="text-[15px] text-normal text-qgraytwo">
            {rule.shipping_rule}
          </span>
        </div>
        <span
          suppressHydrationWarning
          className="text-[15px] text-normal text-qgraytwo"
        >
          <CurrencyConvert price={rule.shipping_fee} />
        </span>
      </div>
    );
  };

  return (
    <div className="w-full px-10 py-[30px] border border-[#EDEDED]">
      {/* Product List Header */}
      <div className="sub-total mb-6">
        <div className="flex justify-between mb-5">
          <p className="text-[13px] font-medium text-qblack uppercase">
            Ürün
          </p>
          <p className="text-[13px] font-medium text-qblack uppercase">
            Toplam
          </p>
        </div>
        <div className="w-full h-[1px] bg-[#EDEDED]"></div>
      </div>

      {/* Product List */}
      <div className="product-list w-full mb-[30px]">
        <ul className="flex flex-col space-y-5">
          {carts &&
            carts.length > 0 &&
            carts.map((item, i) => (
              <li key={i}>
                <div className="flex justify-between items-center">
                  <div>
                    <h4
                      title={item.product.name}
                      className="text-[15px] text-qblack line-clamp-1 mb-2.5 notranslate"
                    >
                      {wordCount(`${item.product.name}`)}
                      <sup className="text-[13px] text-qgray ml-2 mt-2">
                        x{parseInt(item.qty)}
                      </sup>
                    </h4>
                    <p className="text-[13px] text-qgray line-clamp-1">
                      {item.variants.length >= 0 &&
                        item.variants.map((variant, i) => (
                          <span key={i}>
                            {variant.variant_item &&
                              variant.variant_item.name + ","}
                          </span>
                        ))}
                    </p>
                  </div>
                  <div>
                    <span
                      suppressHydrationWarning
                      className="text-[15px] text-qblack font-medium"
                    >
                      <CheckProductIsExistsInFlashSale
                        id={item.product_id}
                        price={price(item)}
                      />
                    </span>
                  </div>
                </div>
              </li>
            ))}
        </ul>
      </div>

      <div className="w-full h-[1px] bg-[#EDEDED]"></div>

      {/* Price Summary */}
      <div className="mt-[20px]">
        <div className="flex justify-between mb-5">
          <p className="text-[13px] text-qblack uppercase font-bold">
            Ara Toplam
          </p>
          <p
            suppressHydrationWarning
            className="text-[15px] font-bold text-qblack uppercase"
          >
            <CurrencyConvert price={totalPrice} />
          </p>
        </div>
        <div className="flex justify-between mb-5">
          <p className="text-[13px] text-qblack uppercase font-bold">
            İndirim Kuponu (-)
          </p>
          <p
            suppressHydrationWarning
            className="text-[15px] font-bold text-qblack uppercase"
          >
            <CurrencyConvert price={discountCoupon} />
          </p>
        </div>
      </div>

      {/* Free Shipping Progress Bar */}
      <FreeShippingBar sellerGroups={sellerShippingGroups} />

      {/* Shipping Section */}
      <div className="shipping mb-6 mt-6">
        <span className="text-[15px] font-medium text-qblack mb-[18px] block">
          Kargo (+)
        </span>

        {Number(webSettings?.map_status) === 1 ? (
          <div>
            {locationShippingPrice && (
              <div className="flex justify-between items-center">
                <span className="text-[15px] text-normal text-qgraytwo">
                  Kargo Ücreti
                </span>
                <span
                  suppressHydrationWarning
                  className="text-[15px] text-normal text-qgraytwo"
                >
                  <CurrencyConvert price={Number(locationShippingPrice)} />
                </span>
              </div>
            )}
          </div>
        ) : Array.isArray(sellerShippingGroups) && sellerShippingGroups.length > 0 ? (
          <div className="flex flex-col space-y-2.5">
            {sellerShippingGroups.map((g) => (
              <div
                key={g.vendor_id}
                className="flex justify-between items-center gap-2 text-[14px]"
              >
                <span className="text-qblack font-600 truncate">
                  {g.shop_name || "Satıcı"}
                </span>
                {g.is_free_shipping ? (
                  <span className="text-emerald-600 font-700 shrink-0">Ücretsiz</span>
                ) : (
                  <span suppressHydrationWarning className="text-qgraytwo shrink-0">
                    <CurrencyConvert price={Number(g.shipping_fee || 0)} />
                  </span>
                )}
              </div>
            ))}
            <div className="flex justify-between items-center border-t border-[#EDEDED] pt-2 mt-1">
              <span className="text-[14px] font-700 text-qblack">Toplam kargo</span>
              <span suppressHydrationWarning className="text-[14px] font-700 text-qblack">
                <CurrencyConvert price={Number(shippingCharge || 0)} />
              </span>
            </div>
          </div>
        ) : (
          <div className="flex flex-col space-y-2.5">
            {shippingRulesByCityId && shippingRulesByCityId.length > 0 ? (
              shippingRulesByCityId.map((rule, i) =>
                renderShippingRule(rule, i)
              )
            ) : (
              <div className="flex space-x-2 justify-center items-center text-qblack">
                <p className="text-sm text-center">Kargo hesaplanıyor…</p>
              </div>
            )}
          </div>
        )}
      </div>

      {/* Total */}
      <div className="mt-[30px]">
        <div className="flex justify-between mb-5">
          <p className="text-2xl font-medium text-qblack capitalize">
            Toplam
          </p>
          <p
            suppressHydrationWarning
            className="text-2xl font-medium text-qred"
          >
            <CurrencyConvert price={mainTotalPrice - discountCoupon} />
          </p>
        </div>
      </div>
    </div>
  );
};

export default OrderSummary;
