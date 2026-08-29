import { createSlice } from "@reduxjs/toolkit";

const COMPARE = "compare";

const initialState = {
  compareProducts: null,
};

export const compareProductSlice = createSlice({
  name: COMPARE,
  initialState,
  reducers: {
    setCompareProducts: (state, { payload }) => {
      state.compareProducts = payload;
    },
  },
});

export const { setCompareProducts } = compareProductSlice.actions;

export default compareProductSlice.reducer;
