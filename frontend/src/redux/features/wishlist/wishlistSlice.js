import { createSlice } from "@reduxjs/toolkit";

// string
const WISHLIST = "wishlist";

const initialState = {
  wishlistData: null,
};

export const wishlistAction = createSlice({
  name: WISHLIST,
  initialState,
  reducers: {
    setWishlistData: (state, { payload }) => {
      state.wishlistData = payload;
    },
  },
});

// Export actions and reducer
export const { setWishlistData } = wishlistAction.actions;
export default wishlistAction.reducer;
