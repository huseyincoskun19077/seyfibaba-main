import apiRoutes from "@/appConfig/apiRoutes";
import { apiSlice } from "@/redux/api/apiSlice";
import { toast } from "react-toastify";

export const authApis = apiSlice.injectEndpoints({
  endpoints: (builder) => ({
    userSignupApi: builder.mutation({
      query: ({ name, email, password, password_confirmation, phone, otp_verified_token, agree, legal_consents }) => {
        return {
          url: apiRoutes.signup,
          method: "POST",
          body: { name, email, password, password_confirmation, phone, otp_verified_token, agree, legal_consents },
        };
      },
    }),
    userLoginApi: builder.mutation({
      query: (data) => {
        // data: email, password
        return {
          url: apiRoutes.login,
          method: "POST",
          body: data,
        };
      },
      async onQueryStarted(info, { queryFulfilled }) {
        try {
          await queryFulfilled;
        } catch (error) {
          // error handled by caller
        }
      },
    }),
    logoutApi: builder.query({
      query: (data) => {
        return {
          url: `${apiRoutes.logout}?token=${data.token}`,
          method: "GET",
        };
      },
      async onQueryStarted(info, { queryFulfilled }) {
        try {
          const { data, meta } = await queryFulfilled;
          info.success(data, meta.response.status);
        } catch (error) {
          // error handled by caller
        }
      },
    }),
    resendRegisterCodeApi: builder.mutation({
      query: (data) => {
        return {
          url: apiRoutes.resendCode,
          method: "POST",
          body: data,
        };
      },
      async onQueryStarted(info, { queryFulfilled }) {
        try {
          const { meta } = await queryFulfilled;
          // statusCode
          const statusCode = meta.response.status;
          info.success(statusCode);
        } catch (error) {
          // error handled by caller
          toast.error(error?.error?.data?.message);
        }
      },
    }),
    userVerifyApi: builder.query({
      query: (data) => {
        return {
          url: `${apiRoutes.verification}/${data.otp}?email=${data.email}`,
          method: "GET",
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
    sendOtpApi: builder.mutation({
      query: (data) => ({
        url: apiRoutes.sendOtp,
        method: "POST",
        body: data,
      }),
      async onQueryStarted(info, { queryFulfilled }) {
        try {
          const { data, meta } = await queryFulfilled;
          if (info && typeof info.success === "function") {
            info.success(data, meta.response.status);
          }
        } catch (error) {
          if (info && typeof info.error === "function") {
            info.error(error?.error);
          }
        }
      },
    }),
    verifyOtpApi: builder.mutation({
      query: (data) => ({
        url: apiRoutes.verifyOtp,
        method: "POST",
        body: data,
      }),
      async onQueryStarted(info, { queryFulfilled }) {
        try {
          const { data, meta } = await queryFulfilled;
          if (info && typeof info.success === "function") {
            info.success(data, meta.response.status);
          }
        } catch (error) {
          if (info && typeof info.error === "function") {
            info.error(error?.error);
          }
        }
      },
    }),
    resendOtpApi: builder.mutation({
      query: (data) => ({
        url: apiRoutes.resendOtp,
        method: "POST",
        body: data,
      }),
      async onQueryStarted(info, { queryFulfilled }) {
        try {
          const { data, meta } = await queryFulfilled;
          if (info && typeof info.success === "function") {
            info.success(data, meta.response.status);
          }
        } catch (error) {
          if (info && typeof info.error === "function") {
            info.error(error?.error);
          }
        }
      },
    }),
    userForgotApi: builder.mutation({
      query: (data) => {
        return {
          url: apiRoutes.forgotPassword,
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
    userResetApi: builder.mutation({
      query: (data) => {
        return {
          url: `${apiRoutes.resetPassword}/${data.otp}`,
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
    updatePasswordApi: builder.mutation({
      query: (data) => {
        return {
          url: `${apiRoutes.updatePassword}?token=${data.token}`,
          method: "POST",
          body: data.data,
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
    dashboardApi: builder.query({
      query: (data) => {
        return {
          url: `${apiRoutes.dashboard}?token=${data.token}`,
          method: "GET",
        };
      },
      keepUnusedDataFor: 0,
    }),
    orderListApi: builder.query({
      query: (data) => {
        return {
          url: `${apiRoutes.orders}?token=${data.token}`,
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
    orderDetailApi: builder.query({
      query: ({ token, orderId }) => {
        return {
          url: `${apiRoutes.orderDetails}/${orderId}?token=${encodeURIComponent(token || "")}`,
          method: "GET",
        };
      },
    }),
    returnRequestsApi: builder.query({
      query: (data) => {
        const params = new URLSearchParams({
          token: data.token,
        });

        if (data.status !== undefined && data.status !== null && data.status !== "all") {
          params.set("status", data.status);
        }

        if (data.reason) {
          params.set("reason", data.reason);
        }

        if (data.search) {
          params.set("search", data.search);
        }

        if (data.dateFrom) {
          params.set("date_from", data.dateFrom);
        }

        if (data.dateTo) {
          params.set("date_to", data.dateTo);
        }

        if (data.perPage) {
          params.set("per_page", data.perPage);
        }

        return {
          url: `${apiRoutes.returnRequests}?${params.toString()}`,
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
    reviewListApi: builder.query({
      query: (data) => {
        return {
          url: `${apiRoutes.getReview}?token=${data.token}`,
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
    profileInfoApi: builder.query({
      query: (data) => {
        return {
          url: `${apiRoutes.profileInfo}?token=${data.token}`,
        };
      },
      serializeQueryArgs: ({ endpointName }) => {
        return endpointName;
      },
      forceRefetch({ currentArg, previousArg }) {
        return currentArg !== previousArg;
      },
    }),
    deleteUserApi: builder.mutation({
      query: (data) => {
        return {
          url: `${apiRoutes.deleteUser}?token=${data.token}`,
          method: "DELETE",
        };
      },
      async onQueryStarted(info, { queryFulfilled }) {
        try {
          const { data, meta } = await queryFulfilled;
          info.success(data, meta.response.status);
        } catch (error) {
          // error handled by caller
        }
      },
    }),
    updateProfileApi: builder.mutation({
      query: (data) => {
        const formData = new FormData();
        for (const key in data.data) {
          if (data.data[key] !== undefined && data.data[key] !== null) {
            formData.append(key, data.data[key]);
          }
        }
        // ignore viewImage from payload
        formData.append("viewImage", undefined);
        return {
          url: `${apiRoutes.updateProfile}?token=${data.token}`,
          method: "POST",
          body: formData,
          formData: true,
        };
      },
      async onQueryStarted(info, { queryFulfilled, dispatch }) {
        try {
          const { data, meta } = await queryFulfilled;
          if (info && typeof info.success === "function") {
            info.success(data, meta.response.status);
          }
          dispatch(
            apiSlice.util.updateQueryData(
              "profileInfoApi",
              undefined,
              (draft) => {
                const newData = info.data;
                const oldData = JSON.parse(JSON.stringify(draft));
                const updateData = {
                  ...oldData,
                  personInfo: {
                    ...oldData?.personInfo,
                    name: newData?.name ?? oldData?.personInfo?.name,
                    phone: newData?.phone ?? oldData?.personInfo?.phone,
                    address: newData?.address ?? oldData?.personInfo?.address,
                    tc_identity: newData?.tc_identity ?? oldData?.personInfo?.tc_identity,
                    tax_number: newData?.tax_number ?? oldData?.personInfo?.tax_number,
                    city_id: newData?.city,
                    country_id: newData?.country,
                    state_id: newData?.state,
                    viewImage: newData?.viewImage,
                  },
                };
                return (draft = updateData);
              }
            )
          );
          // Dashboard cache'ini de zorla yenile — adres/isim değişince dashboard da güncellensin
          dispatch(
            apiSlice.endpoints.dashboardApi.initiate(
              { token: info.token },
              { forceRefetch: true }
            )
          );
        } catch (error) {
          // error handled by caller
          if (info && typeof info.error === "function") {
            info.error(error?.error);
          }
        }
      },
    }),
    sellerRequestApi: builder.mutation({
      query: (data) => {
        const formData = new FormData();
        for (const key in data.data) {
          if (data.data[key] !== undefined && data.data[key] !== null) {
            formData.append(key, data.data[key]);
          }
        }
        return {
          url: `${apiRoutes.sellerRequest}?token=${data.token}`,
          method: "POST",
          body: formData,
          formData: true,
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
    sellerKycDocumentsApi: builder.query({
      query: (data) => {
        return {
          url: `${apiRoutes.sellerKycDocuments}?token=${data.token}`,
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
    sellerKycStatusApi: builder.query({
      query: (data) => {
        return {
          url: `${apiRoutes.sellerKycStatus}?token=${data.token}`,
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
    uploadSellerKycDocumentApi: builder.mutation({
      query: (data) => {
        const formData = new FormData();
        for (const key in data.data) {
          if (data.data[key] !== undefined && data.data[key] !== null) {
            formData.append(key, data.data[key]);
          }
        }

        return {
          url: `${apiRoutes.sellerKycUpload}?token=${data.token}`,
          method: "POST",
          body: formData,
          formData: true,
        };
      },
      async onQueryStarted(info, { queryFulfilled }) {
        try {
          const { data, meta } = await queryFulfilled;
          if (info && typeof info.success === "function") {
            info.success(data, meta.response.status);
          }
        } catch (error) {
          if (info && typeof info.error === "function") {
            info.error(error?.error);
          }
        }
      },
    }),
    deleteSellerKycDocumentApi: builder.mutation({
      query: (data) => {
        return {
          url: `${apiRoutes.sellerKycDocuments}/${data.id}?token=${data.token}`,
          method: "DELETE",
        };
      },
      async onQueryStarted(info, { queryFulfilled }) {
        try {
          const { data, meta } = await queryFulfilled;
          if (info && typeof info.success === "function") {
            info.success(data, meta.response.status);
          }
        } catch (error) {
          if (info && typeof info.error === "function") {
            info.error(error?.error);
          }
        }
      },
    }),
    sellerBulkImportsApi: builder.query({
      query: (data) => {
        return {
          url: `${apiRoutes.sellerBulkImports}?token=${data.token}`,
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
    uploadSellerBulkImportApi: builder.mutation({
      query: (data) => {
        const formData = new FormData();
        formData.append("import_file", data.file);

        return {
          url: `${apiRoutes.sellerBulkImportUpload}?token=${data.token}`,
          method: "POST",
          body: formData,
          formData: true,
        };
      },
      async onQueryStarted(info, { queryFulfilled }) {
        try {
          const { data, meta } = await queryFulfilled;
          if (info && typeof info.success === "function") {
            info.success(data, meta.response.status);
          }
        } catch (error) {
          if (info && typeof info.error === "function") {
            info.error(error?.error);
          }
        }
      },
    }),
    sellerLowStockProductsApi: builder.query({
      query: (data) => {
        const params = new URLSearchParams({
          token: data.token,
        });

        if (data.perPage) {
          params.set("per_page", data.perPage);
        }

        return {
          url: `${apiRoutes.sellerLowStockProducts}?${params.toString()}`,
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
    buyerNotificationsApi: builder.query({
      query: (data) => {
        const params = new URLSearchParams({
          token: data.token,
        });
        if (data.perPage) {
          params.set("per_page", data.perPage);
        }
        return {
          url: `${apiRoutes.userNotifications}?${params.toString()}`,
          method: "GET",
        };
      },
    }),
    markBuyerNotificationReadApi: builder.mutation({
      query: (data) => ({
        url: `${apiRoutes.userNotifications}/${data.id}/read?token=${data.token}`,
        method: "PUT",
      }),
      async onQueryStarted(info, { queryFulfilled }) {
        try {
          const { data, meta } = await queryFulfilled;
          if (info && typeof info.success === "function") {
            info.success(data, meta.response.status);
          }
        } catch (error) {
          if (info && typeof info.error === "function") {
            info.error(error?.error);
          }
        }
      },
    }),
    markAllBuyerNotificationsReadApi: builder.mutation({
      query: (data) => ({
        url: `${apiRoutes.userNotifications}/read-all?token=${data.token}`,
        method: "PUT",
      }),
      async onQueryStarted(info, { queryFulfilled }) {
        try {
          const { data, meta } = await queryFulfilled;
          if (info && typeof info.success === "function") {
            info.success(data, meta.response.status);
          }
        } catch (error) {
          if (info && typeof info.error === "function") {
            info.error(error?.error);
          }
        }
      },
    }),
    sellerNotificationsApi: builder.query({
      query: (data) => {
        const params = new URLSearchParams({
          token: data.token,
        });

        if (data.perPage) {
          params.set("per_page", data.perPage);
        }

        if (data.unreadOnly) {
          params.set("unread_only", "1");
        }

        return {
          url: `${apiRoutes.sellerNotifications}?${params.toString()}`,
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
    markSellerNotificationReadApi: builder.mutation({
      query: (data) => {
        return {
          url: `${apiRoutes.sellerNotifications}/${data.id}/read?token=${data.token}`,
          method: "PUT",
        };
      },
      async onQueryStarted(info, { queryFulfilled }) {
        try {
          const { data, meta } = await queryFulfilled;
          if (info && typeof info.success === "function") {
            info.success(data, meta.response.status);
          }
        } catch (error) {
          if (info && typeof info.error === "function") {
            info.error(error?.error);
          }
        }
      },
    }),
    markAllSellerNotificationsReadApi: builder.mutation({
      query: (data) => {
        return {
          url: `${apiRoutes.sellerNotifications}/read-all?token=${data.token}`,
          method: "PUT",
        };
      },
      async onQueryStarted(info, { queryFulfilled }) {
        try {
          const { data, meta } = await queryFulfilled;
          if (info && typeof info.success === "function") {
            info.success(data, meta.response.status);
          }
        } catch (error) {
          if (info && typeof info.error === "function") {
            info.error(error?.error);
          }
        }
      },
    }),
    sellerUpdateOrderStatusApi: builder.mutation({
      query: (data) => {
        return {
          url: `${apiRoutes.sellerUpdateOrderStatus}/${data.orderId}?token=${data.token}`,
          method: "PUT",
          body: { order_status: data.order_status },
        };
      },
      async onQueryStarted(info, { queryFulfilled }) {
        try {
          const { data, meta } = await queryFulfilled;
          if (info && typeof info.success === "function") {
            info.success(data, meta.response.status);
          }
        } catch (error) {
          if (info && typeof info.error === "function") {
            info.error(error?.error);
          }
        }
      },
    }),
  }),
});

export const {
  useUserLoginApiMutation,
  useResendRegisterCodeApiMutation,
  useUserSignupApiMutation,
  useLazyUserVerifyApiQuery,
  useUserForgotApiMutation,
  useUserResetApiMutation,
  useDashboardApiQuery,
  useOrderListApiQuery,
  useOrderDetailApiQuery,
  useReviewListApiQuery,
  useProfileInfoApiQuery,
  useLazyLogoutApiQuery,
  useDeleteUserApiMutation,
  useUpdateProfileApiMutation,
  useUpdatePasswordApiMutation,
  useSellerRequestApiMutation,
  useSendOtpApiMutation,
  useVerifyOtpApiMutation,
  useResendOtpApiMutation,
  useReturnRequestsApiQuery,
  useSellerKycDocumentsApiQuery,
  useSellerKycStatusApiQuery,
  useUploadSellerKycDocumentApiMutation,
  useDeleteSellerKycDocumentApiMutation,
  useSellerBulkImportsApiQuery,
  useUploadSellerBulkImportApiMutation,
  useSellerLowStockProductsApiQuery,
  useBuyerNotificationsApiQuery,
  useMarkBuyerNotificationReadApiMutation,
  useMarkAllBuyerNotificationsReadApiMutation,
  useSellerNotificationsApiQuery,
  useMarkSellerNotificationReadApiMutation,
  useMarkAllSellerNotificationsReadApiMutation,
  useSellerUpdateOrderStatusApiMutation,
} = authApis;
