import apiRoutes from "@/appConfig/apiRoutes";
import { apiSlice } from "@/redux/api/apiSlice";

export const contactApis = apiSlice.injectEndpoints({
  endpoints: (builder) => ({
    sendContactMessageApi: builder.mutation({
      query: ({ data }) => {
        return {
          url: `${apiRoutes.contact}`,
          method: "POST",
          body: data,
        };
      },
      async onQueryStarted(info, { queryFulfilled }) {
        try {
          const { data, meta } = await queryFulfilled;
          info.success(data, meta.response.status);
        } catch (error) {
          // error handled by caller
          info.error(error?.error);
        }
      },
    }),
    sendProductInquiryApi: builder.mutation({
      query: ({ data }) => {
        return {
          url: `${apiRoutes.productInquiry}`,
          method: "POST",
          body: data,
        };
      },
      async onQueryStarted(info, { queryFulfilled }) {
        try {
          const { data, meta } = await queryFulfilled;
          info.success(data, meta.response.status);
        } catch (error) {
          info.error(error?.error);
        }
      },
    }),
  }),
});

export const { useSendContactMessageApiMutation, useSendProductInquiryApiMutation } = contactApis;
