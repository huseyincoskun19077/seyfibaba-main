"use client";
import { useContext, useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { toast } from "react-toastify";
import BreadcrumbCom from "../BreadcrumbCom";
import EmptyCardError from "../EmptyCardError";
import PageTitle from "../Helpers/PageTitle";
import ProductsTable from "./ProductsTable";
import ServeLangItem from "../Helpers/ServeLangItem";
import LoginContext from "../Contexts/LoginContext";
import auth from "../../utils/auth";
import FreeShippingBar from "../Shared/FreeShippingBar";
import { useLazyApplyCouponApiQuery } from "@/redux/features/order/apiSlice";
import {
  clearCartAction,
  deleteItemAction,
  updateAllItems,
} from "../../redux/features/cart/cartSlice";
import useRefreshCartPrices from "@/hooks/useRefreshCartPrices";

function CartPage() {
  // Redux hooks
  const dispatch = useDispatch();
  const { cart } = useSelector((state) => state.cart);

  // React hooks
  const router = useRouter();
  const loginPopupBoard = useContext(LoginContext);
  const [cartItems, setCartItems] = useState([]);
  const [couponInput, setCouponInput] = useState("");
  const [appliedCoupon, setAppliedCoupon] = useState(() => {
    if (typeof window === "undefined") return null;
    try { return JSON.parse(localStorage.getItem("coupon") || "null"); } catch { return null; }
  });

  const [applyCouponApi, { isLoading: isApplyCouponLoading }] = useLazyApplyCouponApiQuery();

  useRefreshCartPrices(cart?.cartProducts, { enabled: true, notify: true });

  const totalPrice = cartItems.reduce((sum, item) => sum + (parseFloat(item.totalPrice) || 0), 0);

  const handleApplyCoupon = async () => {
    if (!couponInput.trim()) {
      toast.error("Lütfen bir kupon kodu girin.");
      return;
    }
    if (!auth()) {
      toast.error("Kupon kullanmak için önce giriş yapmalısınız.");
      return;
    }
    const res = await applyCouponApi({ token: auth()?.access_token, coupon: couponInput.trim() });
    if (res.status === "fulfilled" && res.data) {
      if (totalPrice >= parseInt(res.data.coupon?.min_purchase_price || 0)) {
        setAppliedCoupon(res.data.coupon);
        localStorage.setItem("coupon", JSON.stringify(res.data.coupon));
        localStorage.setItem("coupon_set_date", new Date().toLocaleDateString());
        setCouponInput("");
        toast.success("Kupon başarıyla uygulandı!");
      } else {
        toast.error("Toplam tutarınız bu kuponu uygulamak için yeterli değil.");
      }
    } else {
      toast.error(res?.error?.data?.message || "Geçersiz veya süresi dolmuş kupon kodu.");
    }
  };

  const handleRemoveCoupon = () => {
    setAppliedCoupon(null);
    localStorage.removeItem("coupon");
    localStorage.removeItem("coupon_set_date");
    toast.info("Kupon kaldırıldı.");
  };

  /**
   * Calculate total price for a cart item including variants
   * @param {Object} item - Cart item object
   * @returns {number} Total price for the item
   */
  const calculateItemTotalPrice = (item) => {
    if (item.totalPrice != null && !Number.isNaN(Number(item.totalPrice))) {
      return Number(item.totalPrice);
    }
    const basePrice = Number(item.product.offer_price || item.product.price || 0);
    const variantPrice = (item.variants || []).reduce(
      (sum, variant) => sum + Number(variant?.variant_item?.price || 0),
      0
    );
    return (basePrice + variantPrice) * parseInt(item.qty || 1, 10);
  };

  /**
   * Update cart items with calculated total prices
   * @param {Array} items - Array of cart items
   * @returns {Array} Updated cart items with total prices
   */
  const updateCartItemsWithPrices = (items) => {
    return items.map((item) => ({
      ...item,
      totalPrice: calculateItemTotalPrice(item),
    }));
  };

  /**
   * Update cart item quantity and recalculate price
   * @param {number} productId - Product ID to update
   * @param {number} quantityChange - Change in quantity (+1 or -1)
   */
  const updateItemQuantity = (productId, quantityChange) => {
    if (!cartItems || cartItems.length === 0) return;

    const existingItem = cartItems.find((item) => item.product.id === productId);
    if (!existingItem) return;

    const newQty = existingItem.qty + quantityChange;
    if (newQty < 1) {
      dispatch(deleteItemAction(existingItem.product_id ?? productId));
      return;
    }

    const updatedCart = cartItems.map((cartItem) => {
      if (cartItem.product.id === productId) {
        const basePrice = Number(
          cartItem.product.offer_price || cartItem.product.price || 0
        );
        const variantPrice = (cartItem.variants || []).reduce(
          (sum, variant) => sum + Number(variant?.variant_item?.price || 0),
          0
        );

        return {
          ...cartItem,
          qty: newQty,
          totalPrice: (basePrice + variantPrice) * newQty,
        };
      }
      return cartItem;
    });

    dispatch(updateAllItems(updatedCart));
    setCartItems(updatedCart);
  };

  /**
   * Delete item from cart
   * @param {number} productId - Product ID to delete
   */
  const handleDeleteItem = (productId) => {
    dispatch(deleteItemAction(productId));
  };

  /**
   * Increase item quantity
   * @param {number} productId - Product ID to increase quantity
   */
  const handleIncreaseQuantity = (productId) => {
    updateItemQuantity(productId, 1);
  };

  /**
   * Decrease item quantity
   * @param {number} productId - Product ID to decrease quantity
   */
  const handleDecreaseQuantity = (productId) => {
    updateItemQuantity(productId, -1);
  };

  /**
   * Clear all items from cart
   */
  const handleClearCart = () => {
    dispatch(clearCartAction());
  };

  /**
   * Navigate to checkout or show login popup
   */
  const handleCheckout = () => {
    if (!auth()) {
      toast.info("Sipariş vermek için giriş yapmalısınız.");
      router.push("/login?redirect=/checkout");
      return;
    }
    router.push("/checkout");
  };

  // Update cart items when cart state changes
  useEffect(() => {
    if (cart?.cartProducts?.length > 0) {
      const itemsWithPrices = updateCartItemsWithPrices(cart.cartProducts);
      setCartItems(itemsWithPrices);
    } else {
      setCartItems([]);
    }
  }, [cart]);

  // Breadcrumb configuration
  const breadcrumbItems = [
    { name: ServeLangItem()?.home, path: "/" },
    { name: ServeLangItem()?.cart, path: "/cart" },
  ];

  // Render empty cart state
  if (!cartItems || cartItems.length === 0) {
    return (
      <div className="cart-page-wrapper w-full pt-[30px] pb-[60px]">
        <div className="container-x mx-auto">
          <BreadcrumbCom paths={breadcrumbItems} />
          <EmptyCardError />
        </div>
      </div>
    );
  }

  // Render cart with items
  return (
    <div className="cart-page-wrapper w-full bg-white pb-[60px]">
      {/* Page header — iyileştirildi (#11) */}
      <div className="w-full bg-gradient-to-r from-[#f8f5f0] to-[#fff8ee] py-8">
        <div className="container-x mx-auto">
          <h1 className="text-2xl font-bold text-qblack">{ServeLangItem()?.cart || "Sepetim"}</h1>
          <p className="text-sm text-qgray mt-1">Sepetinizde {cartItems.length} ürün bulunuyor</p>
        </div>
      </div>

      {/* Cart content */}
      <div className="w-full mt-[23px]">
        <div className="container-x mx-auto">
          {/* Free Shipping Progress Bar */}
          <div className="mb-4">
            <FreeShippingBar totalPrice={totalPrice} />
          </div>

          {/* Products table */}
          <ProductsTable
            incrementQty={handleIncreaseQuantity}
            decrementQty={handleDecreaseQuantity}
            deleteItem={handleDeleteItem}
            cartItems={cartItems}
            className="mb-[30px]"
          />

          {/* Kupon Alanı */}
          <div className="w-full mb-6 p-4 border border-qgray-border rounded-lg bg-[#fafafa]">
            <h3 className="text-base font-semibold text-qblack mb-3">İndirim Kuponu</h3>
            {appliedCoupon ? (
              <div className="flex items-center gap-3 flex-wrap">
                <div className="flex items-center gap-2 bg-green-50 border border-green-300 text-green-800 px-4 py-2 rounded-lg text-sm font-semibold">
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                  </svg>
                  <span>Kupon uygulandı: <strong>{appliedCoupon.code}</strong></span>
                </div>
                <button
                  type="button"
                  onClick={handleRemoveCoupon}
                  className="text-sm text-red-500 hover:text-red-700 font-medium"
                >
                  Kaldır
                </button>
              </div>
            ) : (
              <div className="flex gap-2">
                <input
                  type="text"
                  value={couponInput}
                  onChange={(e) => setCouponInput(e.target.value)}
                  onKeyDown={(e) => e.key === "Enter" && handleApplyCoupon()}
                  placeholder="Kupon kodunuzu girin"
                  className="flex-1 h-[46px] border border-qgray-border px-4 text-sm focus:outline-none focus:border-qblack rounded"
                />
                <button
                  type="button"
                  disabled={isApplyCouponLoading}
                  onClick={handleApplyCoupon}
                  className="h-[46px] px-5 bg-qblack text-white text-sm font-semibold rounded hover:bg-qyellow hover:text-qblack transition-colors disabled:opacity-60"
                >
                  {isApplyCouponLoading ? "..." : "Uygula"}
                </button>
              </div>
            )}
          </div>

          {/* Action buttons */}
          <div className="w-full flex flex-col sm:flex-row gap-3 sm:justify-between">
            <div className="flex flex-wrap gap-3 items-center">
              {/* Clear cart button */}
              <button onClick={handleClearCart} type="button">
                <div className="text-sm font-semibold text-qred">
                  {ServeLangItem()?.Clear_Cart}
                </div>
              </button>

              {/* Update cart button */}
              <Link href="/cart">
                <div className="px-5 h-[44px] bg-[#F6F6F6] flex justify-center items-center cursor-pointer rounded">
                  <span className="text-sm font-semibold">
                    {ServeLangItem()?.Update_Cart}
                  </span>
                </div>
              </Link>
            </div>

            {/* Checkout button */}
            <button onClick={handleCheckout} className="w-full sm:w-auto">
              <div className="w-full sm:w-[280px] h-[50px] black-btn flex justify-center items-center cursor-pointer rounded">
                <span className="text-sm font-semibold">
                  {ServeLangItem()?.Proceed_to_Checkout}
                </span>
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

export default CartPage;
