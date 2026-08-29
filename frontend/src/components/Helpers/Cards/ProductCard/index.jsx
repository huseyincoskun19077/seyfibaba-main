"use client";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useContext, useEffect, useMemo, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { toast } from "react-toastify";
import auth from "@/utils/auth";
import settings from "@/utils/settings";
import { addItem } from "@/redux/features/cart/cartSlice";

// Product card style components
import ColumnV1 from "./ColumnV1";
import RowV1 from "./RowV1";
import RowV2 from "./RowV2";
import ServeLangItem from "../../ServeLangItem";
import {
  useLazyProductQuickViewApiQuery,
  useLazyAddToCompareApiQuery,
} from "@/redux/features/product/apiSlice";
import useWishlist from "@/hooks/useWishlist";
import ProductQuickView from "./ProductQuickView";
import LoginContext from "@/components/Contexts/LoginContext";
import { useFlyingCart } from "@/components/Contexts/FlyingCartContext";
import appConfig from "@/appConfig";
import { isEffectiveOfferPrice, normalizeOfferPrice } from "@/utils/productPricing";

/**
 * Redirect component shown after adding an item to the cart.
 * @returns {JSX.Element}
 */
const Redirect = () => (
  <div className="flex space-x-2 items-center">
    <span className="text-sm text-gray-500">{ServeLangItem()?.Item_added}</span>
    <Link href="/cart">
      <span className="text-xs border-b border-qyellow text-qyellow mr-2 cursor-pointer">
        {ServeLangItem()?.Go_To_Cart}
      </span>
    </Link>
  </div>
);

/**
 * Custom hook to check if a product is in flash sale.
 * @param {Object} datas - Product data
 * @returns {boolean|null} - Whether the product is in flash sale
 */
const useFlashSale = (datas) => {
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const [isProductInFlashSale, setData] = useState(null);
  useEffect(() => {
    if (websiteSetup) {
      const getId = websiteSetup.payload.flashSaleProducts.find(
        (item) => parseInt(item.product_id) === parseInt(datas.id)
      );
      setData(!!getId);
    }
  }, [websiteSetup, datas.id]);
  return isProductInFlashSale;
};

/**
 * Custom hook to handle product variants and pricing logic.
 * @param {Object} datas - Product data
 * @returns {Object} - Variants, selected variants, price, and offer price
 */
const useProductPricing = (datas) => {
  const varients = datas && datas.variants.length > 0 && datas.variants;
  const [getFirstVarients, setFirstVarients] = useState(
    varients && varients.map((v) => v.active_variant_items[0])
  );
  const [price, setPrice] = useState(null);
  const [offerPrice, setOffer] = useState(null);

  useEffect(() => {
    if (varients) {
      // Calculate prices for variants
      const prices = varients.map((v) =>
        v.active_variant_items.length > 0 && v.active_variant_items[0].price
          ? v.active_variant_items[0].price
          : 0
      );
      if (isEffectiveOfferPrice(datas.offer_price, datas.price)) {
        const sumOfferPrice = parseFloat(
          prices.reduce((prev, curr) => parseInt(prev) + parseInt(curr), 0) +
            parseFloat(datas.offer_price)
        );
        setPrice(datas.price);
        setOffer(sumOfferPrice);
      } else {
        const sumPrice = parseFloat(
          prices.reduce((prev, curr) => parseInt(prev) + parseInt(curr), 0) +
            parseFloat(datas.price)
        );
        setPrice(sumPrice);
        setOffer(null);
      }
    } else {
      setPrice(datas && datas.price);
      setOffer(normalizeOfferPrice(datas?.offer_price, datas?.price));
    }
  }, [datas, varients]);

  return { varients, getFirstVarients, setFirstVarients, price, offerPrice };
};

/**
 * Product Card Component
 * @param {Object} datas - Product data
 * @param {string} styleType - Card style type @default "column-v1" @values "column-v1", "row-v1", "row-v2"
 * @returns {JSX.Element}
 */
export default function ProductCard({
  datas,
  styleType = "column-v1",
  compact = false,
}) {
  // Hooks and state
  const loginPopupBoard = useContext(LoginContext);
  const router = useRouter();
  const dispatch = useDispatch();
  const isProductInFlashSale = useFlashSale(datas);
  const { cart } = useSelector((state) => state.cart);
  const { map_status, commission_type } = settings();
  const { varients, getFirstVarients, setFirstVarients, price, offerPrice } =
    useProductPricing(datas);
  const [quickViewModal, setQuickView] = useState(false);
  const [quickViewData, setQuickViewData] = useState(null);
  const { triggerFlyingCart } = useFlyingCart();

  // Use shared wishlist hook
  const {
    wishlisted,
    arWishlist,
    addToWishlist,
    removeToWishlist,
    addToWishlistLoading,
    removeFromWishlistLoading,
  } = useWishlist(datas);

  // Lock body scroll when quick view modal is open
  useEffect(() => {
    document.body.style.overflow = quickViewModal ? "hidden" : "unset";
    return () => {
      document.body.style.overflow = "unset";
    };
  }, [quickViewModal]);

  /**
   * product quick view functionality
   * @Initializaing useLazyProductQuickViewApiQuery @func productQuickViewApi @param slug
   * @func quickViewHandler
   */
  const [productQuickViewApi, { isLoading: quickViewLoading }] =
    useLazyProductQuickViewApiQuery();
  const quickViewHandler = async (slug) => {
    const res = await productQuickViewApi({ slug: slug });
    if (res.status === "fulfilled") {
      setQuickViewData(res.data ? res.data : null);
      setQuickView(true);
    } else {
      toast.error(res?.error?.data?.message);
    }
  };

  /**
   * Add product to cart with variant and vendor logic.
   * Handles MAP/commission settings and vendor restrictions.
   * @param {number} id
   * @param {Event} event - The click event for position calculation
   */
  const addToCart = (id, event) => {
    const vendor_id = datas?.vendor_id;
    // Prepare selected variants for cart
    const parentVarients = getFirstVarients
      ? getFirstVarients.length > 0 &&
        getFirstVarients.map((v) => ({
          ...v,
          product_variant_name: varients.find(
            (item) => Number(item.id) === Number(v.product_variant_id)
          )?.name,
        }))
      : null;
    // Build product object for cart
    const product = {
      product_id: id,
      qty: 1,
      product: {
        id: id,
        vendor_id: vendor_id,
        name: datas?.title,
        price: datas?.price,
        offer_price: datas?.offer_price,
        thumb_image: datas?.image?.replace(appConfig.BASE_URL, ""),
        slug: datas?.slug,
      },
      variants:
        parentVarients && parentVarients.length
          ? parentVarients.map((item) => ({
              variant_id: Number(item.product_variant_id),
              variant_item_id: item.id,
              product_id: id,
              variant_item: {
                id: item.id,
                product_variant_name: item.product_variant_name,
                name: item.name,
                price: item.price,
              },
            }))
          : [],
    };

    if (cart) {
      // Check if product or vendor already in cart
      const checkProduct = cart?.cartProducts.length
        ? cart?.cartProducts.find((item) => item.product_id === id)
        : null;
      const vendorProduct = cart?.cartProducts.length
        ? cart?.cartProducts.find(
            (item) => item.product.vendor_id === vendor_id
          )
        : null;
      const enableMapOrCommission =
        (map_status && Number(map_status) === 1) ||
        (commission_type && commission_type === "subscription");

      if (enableMapOrCommission) {
        if (!vendorProduct) {
          if (checkProduct) {
            toast.error("Ürün zaten sepetinizde.");
          } else {
            dispatch(addItem(product));
            toast.success("Ürün sepete eklendi!");
            // Trigger flying cart animation with event
            triggerFlyingCartAnimation(event);
          }
        } else {
          toast.error("Aynı satıcıdan ikinci bir ürün sepete eklenemez.");
        }
      } else {
        if (checkProduct) {
          toast.error("Ürün zaten sepetinizde.");
        } else {
          dispatch(addItem(product));
          toast.success("Ürün sepete eklendi!");
          // Trigger flying cart animation with event
          triggerFlyingCartAnimation(event);
        }
      }
    }
  };

  /**
   * Trigger flying cart animation
   * Gets the position of the product card and fixed cart button to animate
   */
  const triggerFlyingCartAnimation = (event) => {
    // Small delay to ensure DOM is ready
    setTimeout(() => {
      // Get the product card element (the button that was clicked)
      const productCard = document.querySelector(
        `[data-product-id="${datas.id}"]`
      );
      const fixedCartButton = document.querySelector(".fixed-cart-wrapper");

      if (productCard && fixedCartButton) {
        const cartRect = fixedCartButton.getBoundingClientRect();

        // Use the exact click position from the event
        const startPosition = {
          x: event ? event.clientX : 0,
          y: event ? event.clientY : 0,
        };

        const endPosition = {
          x: cartRect.left + cartRect.width / 2,
          y: cartRect.top + cartRect.height / 2,
        };

        triggerFlyingCart(
          datas.image?.replace(appConfig.BASE_URL, ""),
          startPosition,
          endPosition
        );
      }
    }, 100); // Increased delay to ensure DOM is fully ready
  };

  /**
   * product compare functionality
   * @Initializaing useLazyAddToCompareApiQuery @const addToCompareApi
   * @func addToCompare @params id
   */

  const [addToCompareApi, { isLoading: addToCompareLoading }] =
    useLazyAddToCompareApiQuery();

  const addToCompare = async (id) => {
    if (auth()) {
      const userToken = auth().access_token;
      const data = {
        token: userToken,
        id: id,
      };
      await addToCompareApi(data);
    } else {
      loginPopupBoard.handlerPopup(true);
    }
  };

  /**
   * Store Common Props @const commonProps
   * Render Product Card based on styleType prop using @switch
   * Pass common props to the component @return
   */

  // Common props for all card style components
  const commonProps = {
    styleType,
    datas,
    offerPrice,
    price,
    quickViewHandler,
    arWishlist,
    addToWishlist,
    removeToWishlist,
    wishlisted,
    addToCompare,
    compact,
  };

  // Render product card based on styleType prop
  switch (styleType) {
    case "column-v1":
      return (
        <>
          {/* product card */}
          <ColumnV1
            {...commonProps}
            addToCart={addToCart}
            isProductInFlashSale={isProductInFlashSale}
          />
          {/* product details quick view modal */}
          {quickViewModal && quickViewData && (
            <ProductQuickView
              quickViewModal={quickViewModal}
              quickViewData={quickViewData}
              setQuickView={setQuickView}
            />
          )}
        </>
      );
    case "row-v1":
      return (
        <>
          {/* product card */}
          <RowV1
            {...commonProps}
            isProductInFlashSale={isProductInFlashSale}
            addToCart={addToCart}
          />
          {/* product details quick view modal   */}
          {quickViewModal && quickViewData && (
            <ProductQuickView
              quickViewModal={quickViewModal}
              quickViewData={quickViewData}
              setQuickView={setQuickView}
            />
          )}
        </>
      );
    case "row-v2":
      return (
        <RowV2
          styleType={styleType}
          datas={datas}
          offerPrice={offerPrice}
          price={price}
          isProductInFlashSale={isProductInFlashSale}
        />
      );
    default:
      return (
        <ColumnV1
          {...commonProps}
          addToCart={addToCart}
          isProductInFlashSale={isProductInFlashSale}
        />
      );
  }
}
