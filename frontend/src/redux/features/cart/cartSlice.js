import { createSlice } from "@reduxjs/toolkit";

// string
const CART = "cart";

// Initial state
const initialState = {
  cart: {
    cartProducts: [],
  },
  status: null,
};

// Create slice with builder callback
export const cartSlice = createSlice({
  name: CART,
  initialState,
  reducers: {
    addItem: (state, { payload }) => {
      // Reducer seviyesinde duplicate kontrolü — stale closure race condition'ı önler
      // Aynı ürün + aynı varyant kombinasyonu varsa qty artır
      const payloadVariantKey = JSON.stringify(
        (payload.variants || []).map((v) => v.variant_item_id).sort()
      );
      const existingIndex = state.cart.cartProducts.findIndex((item) => {
        if (Number(item.product_id) !== Number(payload.product_id)) return false;
        const itemVariantKey = JSON.stringify(
          (item.variants || []).map((v) => v.variant_item_id).sort()
        );
        return itemVariantKey === payloadVariantKey;
      });
      if (existingIndex >= 0) {
        // Aynı ürün + aynı varyant zaten sepette, qty artır
        state.cart.cartProducts[existingIndex].qty += payload.qty || 1;
      } else {
        state.cart.cartProducts = [...state.cart.cartProducts, payload];
      }
    },
    deleteItemAction: (state, { payload }) => {
      const newItem = state.cart.cartProducts.filter(
        (item) => Number(item.product_id) !== Number(payload)
      );
      state.cart.cartProducts = newItem;
    },
    clearCartAction: (state) => {
      state.cart.cartProducts = [];
    },
    updateAllItems: (state, { payload }) => {
      state.cart.cartProducts = payload;
    },
  },
});

export const { addItem, clearCartAction, deleteItemAction, updateAllItems } =
  cartSlice.actions;

export default cartSlice.reducer;
