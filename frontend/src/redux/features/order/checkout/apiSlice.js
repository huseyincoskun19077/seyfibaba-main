import apiRoutes from "@/appConfig/apiRoutes";
import { apiSlice } from "@/redux/api/apiSlice";

export const checkoutApis = apiSlice.injectEndpoints({
  endpoints: (builder) => ({
    getCheckoutDataApi: builder.query({
      query: (data) => {
        return {
          url: `${apiRoutes.getCheckoutData}?token=${data.token}`,
          method: "GET",
        };
      },
      serializeQueryArgs: ({ endpointName }) => {
        return endpointName;
      },
      forceRefetch({ currentArg, previousArg }) {
        return currentArg !== previousArg;
      },
    }),
    getGuestCheckoutDataApi: builder.query({
      query: () => {
        return {
          url: `${apiRoutes.getGuestCheckoutData}`,
          method: "GET",
        };
      },
      serializeQueryArgs: ({ endpointName }) => {
        return endpointName;
      },
      forceRefetch({ currentArg, previousArg }) {
        return currentArg !== previousArg;
      },
    }),
  }),
});

export const {
  useLazyGetCheckoutDataApiQuery,
  useLazyGetGuestCheckoutDataApiQuery,
} = checkoutApis;
