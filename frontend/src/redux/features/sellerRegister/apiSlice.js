import apiRoutes from "@/appConfig/apiRoutes";
import { apiSlice } from "@/redux/api/apiSlice";

export const sellerRegisterApi = apiSlice.injectEndpoints({
  endpoints: (builder) => ({
    publicSellerRegister: builder.mutation({
      query: (body) => ({
        url: apiRoutes.publicSellerRegister,
        method: "POST",
        body,
      }),
    }),
    getPublicSellerRegisterStates: builder.query({
      query: () => ({
        url: apiRoutes.publicSellerRegisterStates,
        method: "GET",
      }),
    }),
    publicSellerCallback: builder.mutation({
      query: (body) => ({
        url: apiRoutes.publicSellerCallback,
        method: "POST",
        body,
      }),
    }),
  }),
});

export const {
  usePublicSellerRegisterMutation,
  useGetPublicSellerRegisterStatesQuery,
  usePublicSellerCallbackMutation,
} = sellerRegisterApi;
