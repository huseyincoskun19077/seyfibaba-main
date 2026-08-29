import apiRoutes from "@/appConfig/apiRoutes";
import { apiSlice } from "@/redux/api/apiSlice";

export const secondHandApi = apiSlice.injectEndpoints({
  endpoints: (builder) => ({
    secondHandVerification: builder.query({
      query: () => ({
        url: apiRoutes.secondHandUserVerification,
        method: "GET",
      }),
      providesTags: ["SecondHandVerification"],
      /** Profil / sekmeler arasında gereksiz yeniden istekleri azaltır; gönderim sonrası tag ile yine tazelenir */
      refetchOnMountOrArgChange: 120,
    }),

    secondHandVerificationSubmit: builder.mutation({
      query: (body) => ({
        url: apiRoutes.secondHandUserVerification,
        method: "POST",
        body,
      }),
      invalidatesTags: ["SecondHandVerification", "SecondHandListings", "SecondHandInbox"],
    }),

    secondHandMyListings: builder.query({
      query: (arg = {}) => {
        const params = new URLSearchParams();
        if (arg.status) params.set("status", arg.status);
        if (arg.page) params.set("page", String(arg.page));
        if (arg.q) params.set("q", String(arg.q));
        if (arg.condition) params.set("condition", String(arg.condition));
        const qs = params.toString();
        return {
          url: `${apiRoutes.secondHandUserListingsMy}${qs ? `?${qs}` : ""}`,
          method: "GET",
        };
      },
      providesTags: ["SecondHandListings"],
    }),

    secondHandCreateDraft: builder.mutation({
      query: (body) => ({
        url: apiRoutes.secondHandUserListings,
        method: "POST",
        body,
        headers: { "Content-Type": "application/json" },
      }),
      invalidatesTags: ["SecondHandListings"],
    }),

    secondHandUpdateDraft: builder.mutation({
      query: ({ id, body }) => ({
        url: `${apiRoutes.secondHandUserListings}/${id}`,
        method: "PUT",
        body,
        headers: { "Content-Type": "application/json" },
      }),
      invalidatesTags: ["SecondHandListings"],
    }),

    secondHandPublishListing: builder.mutation({
      query: (id) => ({
        url: `${apiRoutes.secondHandUserListings}/${id}/publish`,
        method: "POST",
      }),
      invalidatesTags: ["SecondHandListings"],
    }),

    secondHandUploadListingImage: builder.mutation({
      query: ({ id, formData }) => ({
        url: `${apiRoutes.secondHandUserListings}/${id}/images`,
        method: "POST",
        body: formData,
      }),
      invalidatesTags: ["SecondHandListings"],
    }),

    secondHandDeleteListingImage: builder.mutation({
      query: ({ listingId, imageId }) => ({
        url: `${apiRoutes.secondHandUserListings}/${listingId}/images/${imageId}`,
        method: "DELETE",
      }),
      invalidatesTags: ["SecondHandListings"],
    }),

    secondHandDeactivateListing: builder.mutation({
      query: ({ id, inactive_reason }) => ({
        url: `${apiRoutes.secondHandUserListings}/${id}/deactivate`,
        method: "POST",
        body: { inactive_reason: inactive_reason || null },
        headers: { "Content-Type": "application/json" },
      }),
      invalidatesTags: ["SecondHandListings"],
    }),

    secondHandActivateListing: builder.mutation({
      query: (id) => ({
        url: `${apiRoutes.secondHandUserListings}/${id}/activate`,
        method: "POST",
      }),
      invalidatesTags: ["SecondHandListings"],
    }),

    secondHandMarkSoldListing: builder.mutation({
      query: (id) => ({
        url: `${apiRoutes.secondHandUserListings}/${id}/sold`,
        method: "POST",
      }),
      invalidatesTags: ["SecondHandListings", "SecondHandInbox"],
    }),

    secondHandInbox: builder.query({
      query: () => ({
        url: apiRoutes.secondHandUserMessagesInbox,
        method: "GET",
      }),
      providesTags: ["SecondHandInbox"],
      refetchOnMountOrArgChange: 60,
    }),

    secondHandConversationMessages: builder.query({
      query: (arg) => {
        const conversationId = typeof arg === "object" ? arg?.conversationId : arg;
        const page = typeof arg === "object" ? arg?.page : undefined;
        const qs = page ? `?page=${encodeURIComponent(String(page))}` : "";
        return {
          url: `${apiRoutes.secondHandUserMessagesConversations}${conversationId}${qs}`,
          method: "GET",
        };
      },
      providesTags: (result, error, arg) => {
        const conversationId = typeof arg === "object" ? arg?.conversationId : arg;
        return [{ type: "SecondHandConversation", id: String(conversationId) }];
      },
    }),

    secondHandSendToListing: builder.mutation({
      query: ({ listingId, body }) => ({
        url: `${apiRoutes.secondHandUserMessagesListings}${listingId}`,
        method: "POST",
        body,
      }),
      invalidatesTags: ["SecondHandInbox"],
    }),

    secondHandSendToConversation: builder.mutation({
      query: ({ conversationId, body }) => ({
        url: `${apiRoutes.secondHandUserMessagesConversations}${conversationId}`,
        method: "POST",
        body,
      }),
      invalidatesTags: (result, error, arg) => [
        "SecondHandInbox",
        { type: "SecondHandConversation", id: String(arg.conversationId) },
      ],
    }),

    secondHandMarkConversationRead: builder.mutation({
      query: (conversationId) => ({
        url: `${apiRoutes.secondHandUserMessagesConversations}${conversationId}/read`,
        method: "POST",
      }),
      invalidatesTags: (result, error, conversationId) => [
        "SecondHandInbox",
        { type: "SecondHandConversation", id: String(conversationId) },
      ],
    }),

    secondHandReportCreate: builder.mutation({
      query: (body) => ({
        url: apiRoutes.secondHandUserReports,
        method: "POST",
        body,
        headers: { "Content-Type": "application/json" },
      }),
    }),

    secondHandBlockUser: builder.mutation({
      query: (body) => ({
        url: apiRoutes.secondHandUserBlocks,
        method: "POST",
        body,
        headers: { "Content-Type": "application/json" },
      }),
      invalidatesTags: ["SecondHandInbox"],
    }),

    secondHandUnblockUser: builder.mutation({
      query: (blockedId) => ({
        url: `${apiRoutes.secondHandUserBlocks}/${blockedId}`,
        method: "DELETE",
      }),
      invalidatesTags: ["SecondHandInbox"],
    }),
  }),
});

export const {
  useSecondHandVerificationQuery,
  useSecondHandVerificationSubmitMutation,
  useSecondHandMyListingsQuery,
  useSecondHandCreateDraftMutation,
  useSecondHandUpdateDraftMutation,
  useSecondHandPublishListingMutation,
  useSecondHandUploadListingImageMutation,
  useSecondHandDeleteListingImageMutation,
  useSecondHandDeactivateListingMutation,
  useSecondHandActivateListingMutation,
  useSecondHandMarkSoldListingMutation,
  useSecondHandInboxQuery,
  useSecondHandConversationMessagesQuery,
  useSecondHandSendToListingMutation,
  useSecondHandSendToConversationMutation,
  useSecondHandMarkConversationReadMutation,
  useSecondHandReportCreateMutation,
  useSecondHandBlockUserMutation,
  useSecondHandUnblockUserMutation,
} = secondHandApi;
