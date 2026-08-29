"use client";

import { useEffect, useState } from "react";
import Image from "next/image";
import Link from "next/link";
import { useDispatch, useSelector } from "react-redux";
import { deleteItemAction } from "../../redux/features/cart/cartSlice";
import useRefreshCartPrices from "@/hooks/useRefreshCartPrices";
import CheckProductIsExistsInFlashSale from "../Shared/CheckProductIsExistsInFlashSale";
import ServeLangItem from "../Helpers/ServeLangItem";
import CurrencyConvert from "../Shared/CurrencyConvert";
import CartDeleteIco from "../Helpers/icons/CartDeleteIco";
import { getProductImageProps } from "@/utils/productImage";

export default function Cart({ className }) {
  // Redux state selectors
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const { cart } = useSelector((state) => state.cart);
  const dispatch = useDispatch();

  useRefreshCartPrices(cart?.cartProducts, { enabled: Boolean(cart?.cartProducts?.length), notify: false });

  // Local state for cart management
  const [getCarts, setGetCarts] = useState(null);
  const [getAllPrice, setGetAllPrice] = useState(null);
  const [totalPrice, setTotalPrice] = useState(null);
  // Calculate total price when individual prices change
  useEffect(() => {
    setTotalPrice(
      getAllPrice?.reduce(
        (prev, curr) => parseFloat(prev) + parseFloat(curr)
      ) || 0
    );
  }, [getAllPrice]);

  // Update cart items when cart state changes
  useEffect(() => {
    setGetCarts(cart?.cartProducts || null);
  }, [cart]);
  /**
   * Check if product exists in flash sale and calculate discounted price
   *
   * @param {number} id - Product ID
   * @param {number} price - Original product price
   * @returns {number} Final price after flash sale discount
   */
  const checkProductExistsInFlashSale = (id, price) => {
    if (!websiteSetup?.payload?.flashSale) return price;

    const flashSaleOffer = websiteSetup.payload.flashSale.offer;
    const flashSaleProduct = websiteSetup.payload.flashSaleProducts?.find(
      (item) => parseInt(item.product_id) === parseInt(id)
    );

    if (flashSaleOffer && flashSaleProduct) {
      const discountPrice = (parseInt(flashSaleOffer) / 100) * price;
      return price - discountPrice;
    }

    return price;
  };
  // Calculate prices for all cart items including variants and flash sale discounts
  useEffect(() => {
    if (getCarts?.length > 0) {
      setGetAllPrice(
        getCarts.map((v) => {
          const basePrice = v.product.offer_price || v.product.price;
          const variantPrice =
            v.variants?.reduce(
              (sum, item) =>
                sum +
                (item.variant_item ? parseInt(item.variant_item.price) : 0),
              0
            ) || 0;
          const unitPrice = parseInt(basePrice) + variantPrice;
          const totalPrice = unitPrice * (v.qty || 1);
          return checkProductExistsInFlashSale(v.product_id, totalPrice);
        })
      );
    } else {
      setGetAllPrice(null);
    }
  }, [getCarts]);

  /**
   * Remove item from cart
   *
   * @param {number} id - Product ID to remove
   */
  const deleteItem = (id) => {
    dispatch(deleteItemAction(id));
  };

  /**
   * Calculate individual item price including variants
   *
   * @param {Object} item - Cart item object
   * @returns {number} Calculated price
   */
  const price = (item) => {
    if (!item) return 0;

    const basePrice = item.product.offer_price || item.product.price;
    const variantPrice =
      item.variants?.reduce(
        (sum, variant) =>
          sum +
          (variant.variant_item ? parseInt(variant.variant_item.price) : 0),
        0
      ) || 0;

    return parseInt(basePrice) + variantPrice;
  };
  return (
    <div
      style={{ boxShadow: " 0px 15px 50px 0px rgba(0, 0, 0, 0.14)" }}
      className={`cart-wrappwer w-[300px] bg-white border-t-[3px] ${
        className || ""
      }`}
    >
      <div className="w-full h-full">
        {/* Cart Items List */}
        <div className="product-items h-[310px] overflow-y-scroll">
          <ul>
            {getCarts?.map((item, i) => (
              <li key={i} className="w-full h-full flex justify-between">
                {/* Product Image and Details */}
                <div className="flex space-x-[6px] justify-center items-center px-4 my-[20px]">
                  {/* Product Thumbnail */}
                  <div className="w-[65px] h-full relative">
                    <Image
                      fill
                      {...getProductImageProps(item.product.thumb_image)}
                      alt={item.product.name}
                      sizes="65px"
                      className="w-full h-full object-contain"
                    />
                  </div>

                  {/* Product Information */}
                  <div className="flex-1 h-full flex flex-col justify-center ">
                    <p className="title mb-2 text-[13px] font-600 text-qblack leading-4 line-clamp-2 hover:text-qyellow notranslate">
                      {item.product.name}
                    </p>

                    {/* Product Price */}
                    <p className="price">
                      <span
                        suppressHydrationWarning
                        className="offer-price text-qred font-600 text-[15px] ml-2"
                      >
                        {
                          <CheckProductIsExistsInFlashSale
                            id={item.product_id}
                            price={price(item)}
                          />
                        }
                      </span>
                    </p>
                  </div>
                </div>
                {/* Delete Button */}
                <span
                  onClick={() => deleteItem(item.product_id)}
                  className="mt-[20px] mr-[15px] inline-flex cursor-pointer"
                >
                  <CartDeleteIco />
                </span>
              </li>
            ))}
          </ul>
          {/* Empty Cart Message */}
          {!getCarts?.length && (
            <p className="text-sm text-gray-400 mt-10 text-center">
              {ServeLangItem()?.No_items_found}
            </p>
          )}
        </div>
        {/* Divider */}
        <div className="w-full px-4 mt-[20px] mb-[12px]">
          <div className="h-[1px] bg-[#F0F1F3]"></div>
        </div>

        {/* Cart Actions */}
        <div className="product-actions px-4 mb-[30px]">
          {/* Total Price Display */}
          <div className="total-equation flex justify-between items-center mb-[28px]">
            <span className="text-[15px] font-500 text-qblack">
              {ServeLangItem()?.Total || "Toplam"}
            </span>
            <span
              suppressHydrationWarning
              className="text-[15px] font-500 text-qred "
            >
              <CurrencyConvert price={totalPrice || 0} />
            </span>
          </div>
          {/* Action Buttons */}
          <div className="product-action-btn">
            {/* View Cart Button */}
            <Link href="/cart">
              <div className="gray-btn w-full h-[50px] mb-[10px] cursor-pointer ">
                <span>{ServeLangItem()?.View_Cart}</span>
              </div>
            </Link>

            {/* Checkout Button */}
            <Link href="/checkout">
              <div className="w-full h-[50px] cursor-pointer">
                <div className="yellow-btn">
                  <span className="text-sm">
                    {ServeLangItem()?.Checkout_Now}
                  </span>
                </div>
              </div>
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}
