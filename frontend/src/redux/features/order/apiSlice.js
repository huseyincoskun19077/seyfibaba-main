import apiRoutes from "@/appConfig/apiRoutes";
import { apiSlice } from "@/redux/api/apiSlice";

export const orderApis = apiSlice.injectEndpoints({
  endpoints: (builder) => ({
    // track order
    trackOrderApi: builder.query({
      query: (data) => ({
        url: `${apiRoutes.orderTrack}/${data.data}`,
        method: "GET",
        headers: data.token
          ? { Authorization: `Bearer ${data.token}` }
          : undefined,
      }),
      async onQueryStarted(info, { queryFulfilled }) {
        try {
          const { data, meta } = await queryFulfilled;
          info.success(data, meta.response.status);
        } catch (error) {
          // error handled by caller
          info.error(error?.error);
        }
      },
      serializeQueryArgs: ({ endpointName }) => {
        return endpointName;
      },
      forceRefetch({ currentArg, previousArg }) {
        return currentArg !== previousArg;
      },
    }),
    // live track order
    liveTrackOrderApi: builder.query({
      query: (data) => ({
        url: `${apiRoutes.liveTrackOrder}?order_id=${data.orderId}`,
        method: "GET",
        headers: data.token
          ? { Authorization: `Bearer ${data.token}` }
          : undefined,
      }),
      serializeQueryArgs: ({ endpointName }) => {
        return endpointName;
      },
      forceRefetch({ currentArg, previousArg }) {
        return currentArg !== previousArg;
      },
    }),
    // apply coupon
    applyCouponApi: builder.query({
      query: (data) => {
        return {
          url: `${apiRoutes.applyCoupon}?token=${data.token}&coupon=${data.coupon}`,
          method: "GET",
        };
      },
    }),
  }),
});

export const {
  useLazyTrackOrderApiQuery,
  useLazyLiveTrackOrderApiQuery,
  useLazyApplyCouponApiQuery,
} = orderApis;
