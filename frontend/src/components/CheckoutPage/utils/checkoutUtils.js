"use client";
import DateFormat from "@/utils/DateFormat";
import settings from "@/utils/settings";

export const calculateTotalPrice = (subTotal) => {
  return subTotal && subTotal.reduce((prev, curr) => prev + curr, 0);
};

/**
 * Calculate discount amount based on coupon type and total price
 * @param {Object} couponCode - Coupon object with offer_type and discount
 * @param {number} totalPrice - Total price before discount
 * @returns {number} Discount amount
 */
export const calculateDiscountAmount = (couponCode, totalPrice) => {
  if (!couponCode || !totalPrice) return 0;

  if (Number(couponCode.offer_type) === 2) {
    // Fixed amount discount
    return parseInt(couponCode.discount);
  } else {
    // Percentage discount
    return (parseInt(couponCode.discount) / 100) * parseInt(totalPrice);
  }
};

/**
 * Calculate total weight from cart products
 * @param {Array} cartProducts - Array of cart products
 * @returns {number} Total weight
 */
export const calculateTotalWeight = (cartProducts) => {
  if (!cartProducts || cartProducts.length === 0) return 0;

  const weightList = cartProducts.map(
    (item) => parseInt(item.product.weight) * parseInt(item.qty)
  );

  return weightList.reduce((prev, curr) => parseInt(prev) + parseInt(curr), 0);
};

/**
 * Calculate total quantity from cart products
 * @param {Array} cartProducts - Array of cart products
 * @returns {number} Total quantity
 */
export const calculateTotalQuantity = (cartProducts) => {
  if (!cartProducts || cartProducts.length === 0) return 0;

  const qtyList = cartProducts.map((item) => parseInt(item.qty));

  return qtyList.reduce((prev, curr) => parseInt(prev) + parseInt(curr), 0);
};

/**
 * Calculate product price including variants and offers
 * @param {Object} item - Cart item with product and variants
 * @returns {number} Calculated price
 */
export const calculateProductPrice = (item) => {
  if (!item) return 0;

  // Calculate variant prices
  const variantPrices = item.variants
    .filter((variant) => variant.variant_item)
    .map((variant) => parseInt(variant.variant_item.price) || 0);

  const variantSum = variantPrices.reduce((p, c) => p + c, 0);

  // Use offer price if available, otherwise use regular price
  const basePrice = item.product.offer_price || item.product.price;
  const totalPrice = parseFloat(basePrice) + variantSum;

  return totalPrice * parseInt(item.qty);
};

/**
 * Check if product exists in flash sale and calculate discounted price
 * @param {number} productId - Product ID
 * @param {number} originalPrice - Original product price
 * @param {Object} websiteSetup - Website setup object with flash sale data
 * @returns {number} Final price after flash sale discount
 */
export const checkProductExistsInFlashSale = (
  productId,
  originalPrice,
  websiteSetup
) => {
  if (!websiteSetup) return originalPrice;

  const flashSaleOffer = websiteSetup.payload.flashSale?.offer;
  const flashSaleProducts = websiteSetup.payload.flashSaleProducts || [];

  const isInFlashSale = flashSaleProducts.find(
    (item) => parseInt(item.product_id) === parseInt(productId)
  );

  if (flashSaleOffer && isInFlashSale) {
    const discountPercentage = parseInt(flashSaleOffer);
    const discountAmount = (discountPercentage / 100) * originalPrice;
    return originalPrice - discountAmount;
  }

  return originalPrice;
};

/**
 * Calculate location-based shipping price
 * @param {Object} address - Address object with distance and price range
 * @returns {number} Calculated shipping price
 */
export const calculateLocationShippingPrice = (address) => {
  if (!address) return 0;

  return parseInt(address.distance_in_km * address.per_km_price_range).toFixed(
    2
  );
};

/**
 * Filter shipping rules by city ID
 * @param {Array} shippingRules - All shipping rules
 * @param {number} cityId - City ID to filter by
 * @returns {Array} Filtered shipping rules
 */
export const filterShippingRulesByCity = (shippingRules, cityId) => {
  if (!shippingRules) return [];

  const cityRules = shippingRules.filter(
    (rule) => parseInt(rule.city_id) === cityId
  );
  const defaultRules = shippingRules.filter(
    (rule) => parseInt(rule.city_id) === 0
  );

  return [...defaultRules, ...cityRules];
};

/**
 * Validate shipping rule conditions
 * @param {Object} rule - Shipping rule object
 * @param {string} ruleType - Type of rule (price, weight, qty)
 * @param {number} value - Value to check against rule conditions
 * @returns {boolean} Whether rule conditions are met
 */
export const validateShippingRule = (rule, ruleType, value) => {
  const fromCondition = parseInt(rule.condition_from);
  const toCondition = parseInt(rule.condition_to);

  if (value < fromCondition) return false;

  // -1 means unlimited upper bound
  if (toCondition === -1) return true;

  return value <= toCondition;
};

/**
 * Reset form data to initial state
 * @returns {Object} Initial form data state
 */
export const getInitialFormData = () => {
  return {
    firstName: "",
    lastName: "",
    email: "",
    phone: "",
    address: "",
    home: true,
    office: false,
  };
};

/**
 * Get web settings with bank transfer info
 * @returns {Object} Settings object including bank transfer discount
 */
export const getWebSettings = () => {
  const settingsData = settings();
  return {
    ...settingsData,
    bankTransferDiscountPercent: settingsData?.bank_transfer_discount_percent || 3,
    bankTransferInfo: settingsData?.bank_transfer_info || null,
  };
};

/**
 * Reset dropdown data to initial state
 * @returns {Object} Initial dropdown state
 */
export const getInitialDropdownData = () => {
  return {
    countryDropdown: null,
    country: null,
    stateDropdown: null,
    state: null,
    cityDropdown: null,
    city: null,
  };
};

/**
 * Reset coupon data to initial state
 * @returns {Object} Initial coupon data state
 */
export const getInitialCouponData = () => {
  return {
    inputCoupon: "",
    couponCode: null,
    discountCoupon: 0,
  };
};

/**
 * Calculate bank transfer discount
 * @param {number} totalPrice - Total price before discount
 * @param {number} discountPercent - Discount percentage (default 3)
 * @returns {Object} Discount amount and new total
 */
export const calculateBankTransferDiscount = (totalPrice, discountPercent = 3) => {
  if (!totalPrice || totalPrice <= 0) return { discount: 0, newTotal: totalPrice };
  
  const discount = (parseFloat(discountPercent) / 100) * parseFloat(totalPrice);
  return {
    discount: discount,
    newTotal: totalPrice - discount
  };
};
