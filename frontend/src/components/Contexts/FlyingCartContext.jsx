"use client";
import { createContext, useCallback, useContext, useState } from "react";

const FlyingCartContext = createContext();

export const useFlyingCart = () => {
  const context = useContext(FlyingCartContext);
  if (!context) {
    throw new Error("useFlyingCart must be used within a FlyingCartProvider");
  }
  return context;
};

export const FlyingCartProvider = ({ children }) => {
  const [flyingCart, setFlyingCart] = useState({
    isFlying: false,
    productImage: null,
    startPosition: { x: 0, y: 0 },
    endPosition: { x: 0, y: 0 },
  });
  const [isCartDrawerOpen, setIsCartDrawerOpen] = useState(false);

  const openCartDrawer = useCallback(() => setIsCartDrawerOpen(true), []);
  const closeCartDrawer = useCallback(() => setIsCartDrawerOpen(false), []);
  const toggleCartDrawer = useCallback(
    () => setIsCartDrawerOpen((v) => !v),
    []
  );

  const triggerFlyingCart = (productImage, startPosition, endPosition) => {
    setFlyingCart({
      isFlying: true,
      productImage,
      startPosition,
      endPosition,
    });

    // Reset after animation completes
    setTimeout(() => {
      setFlyingCart({
        isFlying: false,
        productImage: null,
        startPosition: { x: 0, y: 0 },
        endPosition: { x: 0, y: 0 },
      });
    }, 1200); // Slightly longer to ensure animation completes
  };

  return (
    <FlyingCartContext.Provider
      value={{
        flyingCart,
        triggerFlyingCart,
        isCartDrawerOpen,
        openCartDrawer,
        closeCartDrawer,
        toggleCartDrawer,
      }}
    >
      {children}
    </FlyingCartContext.Provider>
  );
};

export default FlyingCartContext;
