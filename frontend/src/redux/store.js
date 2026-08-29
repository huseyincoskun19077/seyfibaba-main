"use client";
import { configureStore, combineReducers } from "@reduxjs/toolkit";
import websiteSetup from "./features/websiteSetup/websiteSetupSlice";
import wishlistData from "./features/wishlist/wishlistSlice";
import cart from "./features/cart/cartSlice";
import compareProduct from "./features/compareProduct/compareProductSlice";
import { persistReducer, persistStore } from "redux-persist";
import persistStorage from "./persistStorage";
import { apiSlice } from "./api/apiSlice";

// Manage Strings
const CART = "cart";
const KEYROOT = "root";

// Persist configuration
const persistConfig = {
  key: KEYROOT,
  storage: persistStorage,
  whitelist: [CART], // Only persist the 'cart' slice
};

// Combine reducers
const rootReducer = combineReducers({
  websiteSetup: websiteSetup,
  wishlistData: wishlistData,
  cart: cart,
  compareProducts: compareProduct,
  [apiSlice.reducerPath]: apiSlice.reducer,
});

// Create a persisted reducer
const persistedReducer = persistReducer(persistConfig, rootReducer);

const store = configureStore({
  reducer: persistedReducer,
  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware({
      serializableCheck: false,
    }).concat(apiSlice.middleware),
});

export const persistor = persistStore(store);
export default store;
