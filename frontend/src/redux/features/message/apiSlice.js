import apiRoutes from "@/appConfig/apiRoutes";
import { apiSlice } from "@/redux/api/apiSlice";

export const messageApi = apiSlice.injectEndpoints({
  endpoints: (builder) => ({
    messageWithSeller: builder.query({
      query: ({ token }) => {
        return {
          url: `${apiRoutes.messageWithSeller}?token=${token}`,
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
    loadActiveSellerMessage: builder.query({
      query: ({ token, sellerId }) => {
        return {
          url: `${apiRoutes.loadActiveSellerMessage}/${sellerId}?token=${token}`,
          method: "GET",
        };
      },
    }),
    sendMessageToSeller: builder.mutation({
      query: (data) => {
        return {
          url: `${apiRoutes.sendMessageToSeller}?token=${data.token}`,
          method: "POST",
          body: data.data,
        };
      },
      async onQueryStarted(info, { queryFulfilled }) {
        try {
          const { data, meta } = await queryFulfilled;
          info.success(data, meta.response.status);
        } catch (error) {
          // message send failed silently
        }
      },
    }),
  }),
});

export const {
  useLazyMessageWithSellerQuery,
  useLazyLoadActiveSellerMessageQuery,
  useSendMessageToSellerMutation,
} = messageApi;
