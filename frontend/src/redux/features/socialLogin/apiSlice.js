import apiRoutes from "@/appConfig/apiRoutes";
import { apiSlice } from "@/redux/api/apiSlice";

export const socialLoginApi = apiSlice.injectEndpoints({
  endpoints: (builder) => ({
    googleGetLoginUrlApi: builder.query({
      query: () => {
        return {
          url: apiRoutes.googleGetLoginUrl,
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
    googleCallbackApi: builder.query({
      query: (query) => {
        return {
          url: `${apiRoutes.googleCallback}?${query}`,
          method: "GET",
        };
      },
    }),
    facebookGetLoginUrlApi: builder.query({
      query: () => {
        return {
          url: apiRoutes.facebookGetLoginUrl,
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
    facebookCallbackApi: builder.query({
      query: (query) => {
        return {
          url: `${apiRoutes.facebookCallback}?${query}`,
          method: "GET",
        };
      },
    }),
  }),
});

export const {
  useGoogleGetLoginUrlApiQuery,
  useLazyGoogleGetLoginUrlApiQuery,
  useFacebookGetLoginUrlApiQuery,
  useLazyGoogleCallbackApiQuery,
  useLazyFacebookCallbackApiQuery,
} = socialLoginApi;
