import apiRoutes from "@/appConfig/apiRoutes";
import { apiSlice } from "@/redux/api/apiSlice";
import { toast } from "react-toastify";
import { setWishlistData } from "../wishlist/wishlistSlice";
import ServeLangItem from "@/components/Helpers/ServeLangItem";
import { setCompareProducts } from "../compareProduct/compareProductSlice";

export const productApis = apiSlice.injectEndpoints({
  endpoints: (builder) => ({
    // product review api
    reviewActionApi: builder.mutation({
      query: (data) => {
        return {
          url: `${apiRoutes.productReview}?token=${data.token}`,
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
    // product quick view api
    productQuickViewApi: builder.query({
      query: (data) => {
        return {
          url: `${apiRoutes.products}/${data.slug}`,
          method: "GET",
        };
      },
    }),
    // get all wishlist items api
    getWishlistItemsApi: builder.query({
      query: (data) => {
        return {
          url: `${apiRoutes.wishlists}?token=${data.token}`,
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
    // product add to wishlist api
    addToWishlistApi: builder.query({
      query: (data) => {
        return {
          url: `${apiRoutes.addToWishlist}/${data.id}?token=${data.token}`,
          method: "GET",
        };
      },
      async onQueryStarted(info, { queryFulfilled, dispatch }) {
        try {
          const { data, meta } = await queryFulfilled;
          if (meta.response.status === 200 || meta.response.status === 201) {
            dispatch(
              apiSlice.util.updateQueryData(
                "getWishlistItemsApi",
                { token: info?.token }, // Need to provide the same arg used in the original query
                (draft) => {
                  const newData = data?.wishlist;
                  const oldData = JSON.parse(JSON.stringify(draft));
                  const updateData = {
                    wishlists: [...oldData.wishlists, newData],
                  };
                  dispatch(setWishlistData(updateData));
                  return (draft = updateData);
                }
              )
            );
          } else {
            toast.warning(data?.message);
          }
        } catch (error) {
          // error handled by caller
        }
      },
    }),
    // product remove from wishlist api
    removeFromWishlistApi: builder.query({
      query: (data) => {
        return {
          url: `${apiRoutes.removeFromWishlist}/${data.id}?token=${data.token}`,
          method: "GET",
        };
      },
      async onQueryStarted(info, { queryFulfilled, dispatch }) {
        try {
          const { data, meta } = await queryFulfilled;
          if (meta.response.status === 200 || meta.response.status === 201) {
            // Update the cache by removing the item
            dispatch(
              apiSlice.util.updateQueryData(
                "getWishlistItemsApi",
                { token: info?.token }, // Need to provide the same arg used in the original query
                (draft) => {
                  const oldData = JSON.parse(JSON.stringify(draft));
                  // Remove the item with the specified ID from wishlists
                  const updatedWishlists = oldData.wishlists.filter(
                    (item) => item.id !== info.id
                  );
                  const updateData = {
                    wishlists: updatedWishlists,
                  };
                  dispatch(setWishlistData(updateData));
                  return (draft = updateData);
                }
              )
            );
          } else {
            toast.warning(data?.message);
          }
        } catch (error) {
          // error handled by caller
        }
      },
    }),

    // clear wishlist api
    clearWishlistApi: builder.query({
      query: (data) => {
        return {
          url: `${apiRoutes.clearWishlist}?token=${data.token}`,
          method: "GET",
        };
      },
      async onQueryStarted(info, { queryFulfilled, dispatch }) {
        try {
          const { data, meta } = await queryFulfilled;
          if (meta.response.status === 200 || meta.response.status === 201) {
            toast.success(ServeLangItem()?.Clear_wishlist);
            dispatch(
              apiSlice.util.updateQueryData(
                "getWishlistItemsApi",
                { token: info?.token }, // Need to provide the same arg used in the original query
                (draft) => {
                  const updateData = {
                    wishlists: [],
                  };
                  dispatch(setWishlistData(updateData));
                  return (draft = updateData);
                }
              )
            );
          } else {
            toast.warning(data?.message);
          }
        } catch (error) {
          // error handled by caller
        }
      },
    }),

    // compare list api
    compareListApi: builder.query({
      query: (data) => {
        return {
          url: `${apiRoutes.compare}?token=${data.token}`,
          method: "GET",
        };
      },
      async onQueryStarted(info, { queryFulfilled }) {
        try {
          const { data, meta } = await queryFulfilled;
          if (info.success) {
            info.success(data, meta.response.status);
          }
        } catch (error) {
          // error handled by caller
        }
      },
    }),
    // product add to compare api
    addToCompareApi: builder.query({
      query: (data) => {
        return {
          url: `${apiRoutes.addProductForCompare}/${data.id}?token=${data.token}`,
          method: "GET",
        };
      },
      async onQueryStarted(info, { queryFulfilled, dispatch }) {
        try {
          const { data, meta } = await queryFulfilled;
          if (meta.response.status === 200 || meta.response.status === 201) {
            toast.success(data?.notification);
            dispatch(
              apiSlice.util.updateQueryData(
                "compareListApi",
                { token: info?.token },
                (draft) => {
                  const oldData = JSON.parse(JSON.stringify(draft));
                  const updateData = {
                    products: [...oldData.products, data?.compare],
                  };
                  dispatch(setCompareProducts(updateData));
                  return (draft = updateData);
                }
              )
            );
          } else {
            toast.warning(data?.notification);
          }
        } catch (error) {
          toast.error(error?.error?.data?.notification);
          // error handled by caller
        }
      },
    }),
    // product remove from compare api
    removeFromCompareApi: builder.mutation({
      query: (data) => {
        return {
          url: `${apiRoutes.removeCompareItem}/${data.id}?token=${data.token}`,
          method: "DELETE",
        };
      },
      async onQueryStarted(info, { queryFulfilled, dispatch }) {
        try {
          const { data, meta } = await queryFulfilled;
          if (meta.response.status === 200 || meta.response.status === 201) {
            toast.success(data?.notification);
            dispatch(
              apiSlice.util.updateQueryData(
                "compareListApi",
                { token: info?.token },
                (draft) => {
                  const oldData = JSON.parse(JSON.stringify(draft));
                  const updateData = {
                    products: oldData.products.filter(
                      (item) => item.id !== info.id
                    ),
                  };
                  dispatch(setCompareProducts(updateData));
                  return (draft = updateData);
                }
              )
            );
          } else {
            toast.warning(data?.message);
          }
        } catch (error) {
          // error handled by caller
        }
      },
    }),

    // get all products api
    getAllProductsApi: builder.query({
      query: (query) => {
        return {
          url: `${apiRoutes.searchProduct}?${query}`,
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

    // get product by slug api
    getProductBySlugApi: builder.query({
      query: (slug) => {
        return {
          url: `${apiRoutes.products}/${slug}`,
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
    // get all products next page api
    nextPageProductsApi: builder.query({
      query: (url) => {
        return {
          url: `${url}`,
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
    // product report api
    productReportApi: builder.mutation({
      query: (data) => {
        return {
          url: `${apiRoutes.reportProduct}?token=${data.token}`,
          method: "POST",
          body: data.data,
        };
      },
      async onQueryStarted(info, { queryFulfilled }) {
        try {
          const { data, meta } = await queryFulfilled;
          info.success(data, meta.response.status);
        } catch (error) {
          info.error(error?.error);
          // error handled by caller
        }
      },
    }),

    // get seller detail api
    getSellerDetailApi: builder.query({
      query: (slug) => {
        return {
          url: `${apiRoutes.sellers}/${slug}`,
          method: "GET",
        };
      },
    }),
  }),
});

export const {
  useReviewActionApiMutation,
  useLazyProductQuickViewApiQuery,
  useLazyAddToWishlistApiQuery,
  useLazyRemoveFromWishlistApiQuery,
  useLazyGetWishlistItemsApiQuery,
  useLazyClearWishlistApiQuery,
  useCompareListApiQuery,
  useLazyCompareListApiQuery,
  useLazyAddToCompareApiQuery,
  useRemoveFromCompareApiMutation,
  useLazyGetAllProductsApiQuery,
  useGetAllProductsApiQuery,
  useLazyNextPageProductsApiQuery,
  useNextPageProductsApiQuery,
  useProductReportApiMutation,
  useLazyGetProductBySlugApiQuery,
  useGetSellerDetailApiQuery,
} = productApis;
