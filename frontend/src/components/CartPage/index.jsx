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
import { resolveCartLineUnitPrice } from "@/utils/variantPricing";
import { MoneyText } from "../Shared/PriceDisplay";

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
    const unit = resolveCartLineUnitPrice(item);
    return unit * parseInt(item.qty || 1, 10);
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
        const unit = Number(resolveCartLineUnitPrice(cartItem) || 0);
        return {
          ...cartItem,
          qty: newQty,
          totalPrice: unit * newQty,
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
    <div className="cart-page-wrapper w-full bg-[#f4f7f9] pb-[60px]">
      {/* Page header */}
      <div className="w-full bg-white border-b border-[#04334a]/08 py-8">
        <div className="container-x mx-auto">
          <h1 className="text-2xl font-800 text-[#04334a]">{ServeLangItem()?.cart || "Sepetim"}</h1>
          <p className="text-sm text-[#04334a]/55 mt-1">Sepetinizde {cartItems.length} ürün bulunuyor</p>
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

            {/* Checkout button + toplam */}
            <div className="w-full sm:w-auto sm:min-w-[280px] flex flex-col gap-2">
              <div className="flex items-center justify-between px-1">
                <span className="text-sm font-700 text-[#04334a]/70">Toplam</span>
                <MoneyText value={totalPrice} size="lg" />
              </div>
              <button onClick={handleCheckout} className="w-full" type="button">
                <div className="w-full h-[50px] black-btn flex justify-center items-center cursor-pointer rounded">
                  <span className="text-sm font-semibold">
                    {ServeLangItem()?.Proceed_to_Checkout}
                  </span>
                </div>
              </button>
            </div>
          </div>

          {/* Taksit uyarı kutusu */}
          <div
            className="mt-8 rounded-xl border border-[#c4a35a]/35 bg-[#fff9e6] pl-1 shadow-sm"
            role="note"
          >
            <div className="rounded-xl border-l-[5px] border-l-[#a67c2d] px-4 py-4 md:px-5 md:py-5">
              <div className="flex items-start gap-2.5">
                <span className="mt-0.5 shrink-0 text-[#a67c2d]" aria-hidden>
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                    <path
                      d="M12 3.5L21.5 20H2.5L12 3.5Z"
                      stroke="currentColor"
                      strokeWidth="1.8"
                      strokeLinejoin="round"
                    />
                    <path
                      d="M12 10v4.5M12 17.5h.01"
                      stroke="currentColor"
                      strokeWidth="1.8"
                      strokeLinecap="round"
                    />
                  </svg>
                </span>
                <div className="min-w-0 text-[13px] leading-relaxed text-[#6b5420]">
                  <p className="font-800 text-[#8a6a24] mb-1.5">Uyarı</p>
                  <p>
                    <span className="font-800">Taksit Seçeneği:</span> Sepetinizde
                    yasal düzenleme sebebiyle taksit sınırlaması olan bir ürün
                    varsa, ödeme adımında taksit sınırı tüm sepetinize uygulanır.
                    Dilerseniz daha yüksek taksit seçeneği olan ürünleri ayrıca
                    sipariş edebilirsiniz. Kart türüne göre bankaların taksit
                    seçenekleri değişir, ödeme adımında kartınıza uygun
                    taksitleri görebilirsiniz.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export default CartPage;
