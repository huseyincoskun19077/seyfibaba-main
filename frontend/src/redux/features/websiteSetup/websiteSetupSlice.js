import { createSlice } from "@reduxjs/toolkit";

// string
const WEBSITE_SETUP = "websiteSetup";

const initialState = {
  websiteSetup: null,
};

export const websiteSetup = createSlice({
  name: WEBSITE_SETUP,
  initialState,
  reducers: {
    setupAction: (state, payload) => {
      state.websiteSetup = payload;
    },
  },
});

// Action creators are generated for each case reducer function
export const { setupAction } = websiteSetup.actions;
export default websiteSetup.reducer;
