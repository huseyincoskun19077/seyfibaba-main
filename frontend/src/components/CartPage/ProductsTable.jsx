"use client";
import Image from "next/image";
import Link from "next/link";
import InputQuantityCom from "../Helpers/InputQuantityCom";
import CheckProductIsExistsInFlashSale from "../Shared/CheckProductIsExistsInFlashSale";
import ServeLangItem from "../Helpers/ServeLangItem";
import CurrencyConvert from "../Shared/CurrencyConvert";
import { getProductImageProps } from "@/utils/productImage";
import { buildProductPath } from "@/utils/url";

/**
 * Calculate variant price from variants array
 * @param {Array} variants - Array of variant objects
 * @returns {number} Total variant price
 */
const calculateVariantPrice = (variants) => {
  if (!variants || variants.length === 0) return 0;

  return variants
    .map((variant) =>
      variant.variant_item ? Number(variant.variant_item.price) : 0
    )
    .reduce((sum, price) => sum + price, 0);
};

/**
 * Calculate base item price including variants
 * @param {Object} item - Cart item object
 * @returns {number} Total item price
 */
const calculateItemPrice = (item) => {
  if (!item) return 0;

  const basePrice = item.product.offer_price || item.product.price;
  const variantPrice = calculateVariantPrice(item.variants);

  return Number(basePrice) + variantPrice;
};

/**
 * Calculate total price for cart item including quantity and variants
 * @param {Object} item - Cart item object
 * @returns {number} Total price for the item
 */
const calculateTotalPrice = (item) => {
  if (!item) return 0;
  return calculateItemPrice(item) * Number(item.qty || 1);
};

/**
 * Table Header Component
 * Renders the header row for the cart products table
 */
const TableHeader = () => (
  <tr className="text-[13px] font-medium text-black bg-[#F6F6F6] whitespace-nowrap px-2 border-b default-border-bottom uppercase">
    <td className="py-4 pl-10 block whitespace-nowrap min-w-[300px]">
      {ServeLangItem()?.Product}
    </td>
    <td className="py-4 whitespace-nowrap text-center">
      {ServeLangItem()?.Price}
    </td>
    <td className="py-4 whitespace-nowrap text-center">
      {ServeLangItem()?.quantity}
    </td>
    <td className="py-4 whitespace-nowrap text-center">
      {ServeLangItem()?.total}
    </td>
    <td className="py-4 whitespace-nowrap text-right w-[114px]"></td>
  </tr>
);

/**
 * Product Image Component
 * Renders the product image with proper styling
 * @param {string} imagePath - Path to the product image
 */
const ProductImage = ({ imagePath }) => (
  <div className="w-[80px] h-[80px] overflow-hidden flex justify-center items-center border border-[#EDEDED] relative">
    <Image
      fill
      {...getProductImageProps(imagePath)}
      alt="product"
      sizes="80px"
      className="w-full h-full object-contain"
    />
  </div>
);

/**
 * Variants Display Component
 * Renders product variants with their prices
 * @param {Array} variants - Array of variant objects
 */
const VariantsDisplay = ({ variants }) => {
  if (!variants || variants.length === 0) return null;

  return (
    <div className="border-t border-qyellow w-full pt-2">
      <p className="font-medium text-sm mb-1">Varyantlar:</p>
      <ul>
        {variants.map((variant, index) => (
          <li key={index} className="flex justify-between items-center">
            <span className="text-qblack text-xs">
              {variant?.variant_item.name}{" "}
            </span>
            <span className="text-qblack text-xs font-medium">
              <CurrencyConvert price={Number(variant?.variant_item.price)} />
            </span>
          </li>
        ))}
      </ul>
    </div>
  );
};

/**
 * Delete Button Component
 * Renders a delete button with X icon
 * @param {Function} onClick - Click handler function
 */
const DeleteButton = ({ onClick }) => (
  <span onClick={onClick} className="cursor-pointer">
    <svg
      width="10"
      height="10"
      viewBox="0 0 10 10"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
    >
      <path
        d="M9.7 0.3C9.3 -0.1 8.7 -0.1 8.3 0.3L5 3.6L1.7 0.3C1.3 -0.1 0.7 -0.1 0.3 0.3C-0.1 0.7 -0.1 1.3 0.3 1.7L3.6 5L0.3 8.3C-0.1 8.7 -0.1 9.3 0.3 9.7C0.7 10.1 1.3 10.1 1.7 9.7L5 6.4L8.3 9.7C8.7 10.1 9.3 10.1 9.7 9.7C10.1 9.3 10.1 8.7 9.7 8.3L6.4 5L9.7 1.7C10.1 1.3 10.1 0.7 9.7 0.3Z"
        fill="#AAAAAA"
      />
    </svg>
  </span>
);

const CartItemRow = ({
  item,
  index,
  deleteItem,
  incrementQty,
  decrementQty,
}) => (
  <tr key={index} className="bg-white border-b hover:bg-gray-50">
    {/* Product information */}
    <td className="pl-10 py-4 w-[380px]">
      <div className="flex space-x-6 items-center">
        <ProductImage imagePath={item.product.thumb_image} />
        <div className="flex-1 flex flex-col">
          <div className="mb-2">
            <Link
              href={buildProductPath(item.product.slug)}
            >
              <p className="font-medium text-[15px] text-qblack hover:text-qyellow cursor-pointer notranslate">
                {item.product.name}
              </p>
            </Link>
          </div>
          <VariantsDisplay variants={item.variants} />
        </div>
      </div>
    </td>

    {/* Price column */}
    <td className="text-center py-4 px-2">
      <div className="flex space-x-1 items-center justify-center">
        <span className="text-[15px] font-normal">
          <CheckProductIsExistsInFlashSale
            id={item.product_id}
            price={calculateItemPrice(item)}
          />
        </span>
      </div>
    </td>

    {/* Quantity column */}
    <td className="py-4">
      <div className="flex justify-center items-center">
        <InputQuantityCom
          decrementQty={decrementQty}
          incrementQty={incrementQty}
          productId={item.product.id}
          cartId={item.product.id}
          qyt={parseInt(item.qty)}
        />
      </div>
    </td>

    {/* Total price column */}
    <td className="text-right py-4">
      <div className="flex space-x-1 items-center justify-center">
        <span className="text-[15px] font-normal">
          <CheckProductIsExistsInFlashSale
            id={item.product_id}
            price={calculateTotalPrice(item)}
          />
        </span>
      </div>
    </td>

    {/* Delete button column */}
    <td className="text-right py-4">
      <div className="flex space-x-1 items-center justify-center">
        <DeleteButton onClick={() => deleteItem(item.product_id)} />
      </div>
    </td>
  </tr>
);

export default function ProductsTable({
  className,
  cartItems,
  deleteItem,
  incrementQty,
  decrementQty,
}) {
  return (
    <div className={`w-full ${className || ""}`}>
      <div className="relative w-full overflow-x-auto border border-[#EDEDED]">
        <table className="w-full text-sm text-left text-gray-500 dark:text-gray-400">
          <tbody>
            {/* Table header */}
            <TableHeader />

            {/* Cart items */}
            {cartItems?.map((item, index) => (
              <CartItemRow
                key={index}
                item={item}
                index={index}
                deleteItem={deleteItem}
                incrementQty={incrementQty}
                decrementQty={decrementQty}
              />
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
