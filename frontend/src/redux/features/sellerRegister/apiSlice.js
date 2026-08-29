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
  }),
});

export const {
  usePublicSellerRegisterMutation,
  useGetPublicSellerRegisterStatesQuery,
} = sellerRegisterApi;
