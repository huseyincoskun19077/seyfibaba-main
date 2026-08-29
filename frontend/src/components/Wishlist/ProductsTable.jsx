"use client";
import Image from "next/image";
import Link from "next/link";
import { useEffect, useState, useMemo } from "react";
import CheckProductIsExistsInFlashSale from "../Shared/CheckProductIsExistsInFlashSale";
import ServeLangItem from "../Helpers/ServeLangItem";
import { useRouter } from "next/navigation";
import { useLazyRemoveFromWishlistApiQuery } from "../../redux/features/product/apiSlice";
import WishlistDltIco from "../Helpers/icons/WishlistDltIco";
import auth from "../../utils/auth";
import appConfig from "@/appConfig";
import { resolveProductImageUrl } from "@/utils/productImage";
import { buildProductPath } from "@/utils/url";

export default function ProductsTable({ className, products }) {
  const router = useRouter();
  const [mainProduct, setMainProducts] = useState(null);

  /**
   * product wishlist functionality
   * @Initializaing useLazyRemoveFromWishlistApiQuery @const removeFromWishlistApi
   * @func removeFromWishlist @params id
   */
  const [removeFromWishlistApi, { isLoading: removeFromWishlistLoading }] =
    useLazyRemoveFromWishlistApiQuery();

  // Remove from wishlist handler
  const removeToWishlist = async (id) => {
    if (auth()) {
      const userToken = auth().access_token;
      await removeFromWishlistApi({ token: userToken, id });
    } else {
      router.push("/login");
    }
  };

  // Calculate product price with variants
  const calculatePrice = (item) => {
    if (!item) return 0;

    const basePrice = item.product.offer_price || item.product.price;
    const variantPrice =
      item.product.active_variants?.reduce((sum, variant) => {
        const variantItemPrice = variant?.active_variant_items?.[0]?.price || 0;
        return sum + parseInt(variantItemPrice);
      }, 0) || 0;

    return parseInt(basePrice) + variantPrice;
  };

  // Process products data
  useEffect(() => {
    if (products) {
      setMainProducts(
        products.map((item) => ({
          ...item,
          totalPrice: item.product.price,
        }))
      );
    } else {
      setMainProducts(null);
    }
  }, [products]);

  // Table headers
  const tableHeaders = useMemo(
    () => [
      {
        key: "product",
        label: ServeLangItem()?.Product,
        className: "py-4 capitalize pl-10 block whitespace-nowrap",
      },
      {
        key: "price",
        label: ServeLangItem()?.Price,
        className: "py-4 capitalize whitespace-nowrap text-center",
      },
      {
        key: "action",
        label: ServeLangItem()?.Action,
        className: "py-4 capitalize whitespace-nowrap text-center block",
      },
    ],
    []
  );

  // Product row component
  const ProductRow = ({ item }) => (
    <tr key={item.id} className="bg-white border-b hover:bg-gray-50">
      <td className="ltr:pl-10 rtl:pr-10 py-4 capitalize w-[380px]">
        <div className="flex space-x-6 rtl:space-x-reverse items-center">
          <div className="w-[80px] h-[80px] overflow-hidden flex justify-center items-center border border-[#EDEDED] relative">
            <Image
              layout="fill"
              src={resolveProductImageUrl(item.product.thumb_image)}
              alt="product"
              className="w-full h-full object-contain"
            />
          </div>
          <div className="flex-1 flex flex-col">
            <Link
              href={buildProductPath(item.product.slug)}
            >
              <p className="font-medium text-[15px] text-qblack hover:text-qyellow cursor-pointer notranslate">
                {item.product.name}
              </p>
            </Link>
          </div>
        </div>
      </td>

      <td className="text-center py-4 capitalize px-2">
        <div className="flex space-x-1 rtl:space-x-reverse items-center justify-center">
          <span suppressHydrationWarning className="text-[15px] font-normal">
            <CheckProductIsExistsInFlashSale
              id={item.product_id}
              price={calculatePrice(item)}
            />
          </span>
        </div>
      </td>

      <td className="text-right py-4 capitalize">
        <div className="flex space-x-1 items-center justify-center">
          <span
            className="cursor-pointer"
            onClick={() => removeToWishlist(item.id)}
          >
            <WishlistDltIco />
          </span>
        </div>
      </td>
    </tr>
  );

  // main Component
  return (
    <div className={`w-full ${className || ""}`}>
      <div className="relative w-full overflow-x-auto border border-[#EDEDED]">
        <table className="w-full text-sm text-left text-gray-500 dark:text-gray-400">
          <tbody>
            {/* table heading */}
            <tr className="text-[13px] font-medium text-black bg-[#F6F6F6] whitespace-nowrap px-2 border-b default-border-bottom uppercase">
              {tableHeaders.map((header) => (
                <td key={header.key} className={header.className}>
                  {header.label}
                </td>
              ))}
            </tr>
            {/*table heading end*/}

            {mainProduct?.map((item) => (
              <ProductRow key={item.id} item={item} />
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
