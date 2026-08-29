import apiRoutes from "@/appConfig/apiRoutes";
import { apiSlice } from "@/redux/api/apiSlice";
import { sanitizeWebsiteSetup } from "@/utils/sanitizeWebsiteSetup";

const DEFAULT_LANGUAGE_CODE = "tr";

export const websiteSetupApi = apiSlice.injectEndpoints({
  endpoints: (builder) => ({
    getDefaultSetup: builder.query({
      query: () => {
        return {
          url: `${apiRoutes.websiteSetup}?lang_code=${DEFAULT_LANGUAGE_CODE}`,
        };
      },
      transformResponse: (response) => sanitizeWebsiteSetup(response),
      serializeQueryArgs: ({ endpointName }) => {
        return `${endpointName}_${DEFAULT_LANGUAGE_CODE}`;
      },
      forceRefetch({ currentArg, previousArg }) {
        return currentArg !== previousArg;
      },
    }),
    subscribeRequest: builder.mutation({
      query: (email) => {
        return {
          url: apiRoutes.subscribeRequest,
          method: "POST",
          body: { email },
        };
      },
    }),
  }),
});

export const { useGetDefaultSetupQuery, useSubscribeRequestMutation } =
  websiteSetupApi;
