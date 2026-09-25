"use client";
import { useState, useEffect } from "react";
import { useSelector } from "react-redux";
import ThinBag from "./icons/ThinBag";
import { useFlyingCart } from "@/components/Contexts/FlyingCartContext";

const FixedCartButton = () => {
  const { cart } = useSelector((state) => state.cart);
  const { toggleCartDrawer, isCartDrawerOpen } = useFlyingCart();
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMounted(true);
  }, []);

  const cartItemsCount = mounted ? cart?.cartProducts?.length || 0 : 0;

  return (
    <div
      className={`fixed-cart-button ${isCartDrawerOpen ? "opacity-0 pointer-events-none" : ""}`}
    >
      <button
        type="button"
        onClick={toggleCartDrawer}
        aria-label="Sepeti aç"
        className="block"
      >
        <div className="fixed-cart-wrapper">
          <div className="cart-icon">
            <ThinBag />
          </div>
          <span className="cart-count">
            {cartItemsCount > 9 ? "9+" : cartItemsCount}
          </span>
        </div>
      </button>
    </div>
  );
};

export default FixedCartButton;
