"use client";
import { useFlyingCart } from "../Contexts/FlyingCartContext";
import Image from "next/image";
import { useState, useEffect } from "react";
import appConfig from "@/appConfig";

const SimpleFlyingCart = () => {
  const { flyingCart } = useFlyingCart();
  const [isVisible, setIsVisible] = useState(false);
  const [position, setPosition] = useState({
    x: 0,
    y: 0,
    scale: 1,
    opacity: 1,
  });

  useEffect(() => {
    if (!flyingCart.isFlying) {
      setIsVisible(false);
      return;
    }

    setIsVisible(true);
    setPosition({ x: 0, y: 0, scale: 1, opacity: 1 });

    // Get the fixed cart button position
    const fixedCartButton = document.querySelector(".fixed-cart-wrapper");
    if (!fixedCartButton) return;

    const cartRect = fixedCartButton.getBoundingClientRect();
    const endX = cartRect.left + cartRect.width / 2;
    const endY = cartRect.top + cartRect.height / 2;

    const deltaX = endX - flyingCart.startPosition.x;
    const deltaY = endY - flyingCart.startPosition.y;

    // Simple animation using setTimeout
    const steps = 60; // 60 steps for 1 second
    const stepDuration = 1000 / steps;

    for (let i = 1; i <= steps; i++) {
      setTimeout(() => {
        const progress = i / steps;
        const easeOut = 1 - Math.pow(1 - progress, 2); // Quadratic ease-out

        const currentX = deltaX * easeOut;
        const currentY = deltaY * easeOut;
        const currentScale = 1 - 0.7 * easeOut;
        const currentOpacity = 1 - easeOut;

        setPosition({
          x: currentX,
          y: currentY,
          scale: currentScale,
          opacity: currentOpacity,
        });

        if (i === steps) {
          setTimeout(() => setIsVisible(false), 100);
        }
      }, i * stepDuration);
    }
  }, [flyingCart]);

  if (!isVisible) return null;

  return (
    <div
      className="fixed z-[9999] pointer-events-none"
      style={{
        left: `${flyingCart.startPosition.x}px`,
        top: `${flyingCart.startPosition.y}px`,
        transform: `translate(-50%, -50%) translate3d(${position.x}px, ${position.y}px, 0) scale(${position.scale})`,
        opacity: position.opacity,
        transition: "none",
        willChange: "transform, opacity",
      }}
    >
      <div className="w-12 h-12 rounded-full overflow-hidden border-2 border-white shadow-lg bg-white">
        <Image
          src={`${appConfig.BASE_URL}${flyingCart.productImage}`}
          alt="Flying product"
          width={48}
          height={48}
          className="w-full h-full object-cover"
          priority
        />
      </div>
    </div>
  );
};

export default SimpleFlyingCart;
