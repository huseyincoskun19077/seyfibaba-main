import apiRoutes from "@/appConfig/apiRoutes";
import { apiSlice } from "@/redux/api/apiSlice";

export const locationsApi = apiSlice.injectEndpoints({
  endpoints: (builder) => ({
    // get country list
    getCountryListApi: builder.query({
      query: (data) => {
        return {
          url: `${apiRoutes.getCountryList}?token=${data.token}`,
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
    // get country list guest
    getCountryListGuestApi: builder.query({
      query: () => {
        return {
          url: `${apiRoutes.getCountryListGuest}`,
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
    // get state list
    getStateListApi: builder.query({
      query: (data) => {
        return {
          url: `${apiRoutes.getStateList}/${data.countryId}?token=${data.token}`,
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
    // get city list
    getCityListApi: builder.query({
      query: (data) => {
        return {
          url: `${apiRoutes.getCityList}/${data.stateId}?token=${data.token}`,
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

    // get all user address query
    getAllUserAddressQuery: builder.query({
      query: (data) => {
        return {
          url: `${apiRoutes.address}?token=${data.token}`,
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
    // add new address query
    addNewAddress: builder.mutation({
      query: (data) => {
        return {
          url: `${apiRoutes.address}?token=${data.token}`,
          method: "POST",
          body: data.data,
        };
      },
      async onQueryStarted(info, { queryFulfilled, dispatch }) {
        try {
          const { data, meta } = await queryFulfilled;
          if (meta.response.status === 200 || meta.response.status === 201) {
            dispatch(
              apiSlice.util.updateQueryData(
                "getAllUserAddressQuery",
                undefined,
                (draft) => {
                  const newData = data?.address;
                  const oldData = JSON.parse(JSON.stringify(draft?.addresses));
                  const updatedData = [...oldData, newData];
                  return {
                    ...draft,
                    addresses: updatedData,
                  };
                }
              )
            );
            info.success(data, meta.response.status);
          } else {
            info.error(data);
          }
        } catch (error) {
          info.error(error?.error);
        }
      },
    }),
    // update address
    updateAddressApi: builder.mutation({
      query: (data) => {
        return {
          url: `${apiRoutes.address}/${data.id}?token=${data.token}`,
          method: "PUT",
          body: data.data,
        };
      },
      async onQueryStarted(info, { queryFulfilled, dispatch }) {
        try {
          const { data, meta } = await queryFulfilled;
          if (meta.response.status === 200 || meta.response.status === 201) {
            dispatch(
              apiSlice.util.updateQueryData(
                "getAllUserAddressQuery",
                undefined,
                (draft) => {
                  const oldData = JSON.parse(JSON.stringify(draft?.addresses));
                  const updatedData = oldData.map((item) =>
                    item.id === data?.address?.id ? data?.address : item
                  );
                  return {
                    ...draft,
                    addresses: updatedData,
                  };
                }
              )
            );
            info.success(data, meta.response.status);
          } else {
            info.error(data);
          }
        } catch (error) {
          info.error(error?.error);
        }
      },
    }),
    // delete address
    deleteAddressApi: builder.mutation({
      query: (data) => {
        return {
          url: `${apiRoutes.address}/${data.id}?token=${data.token}`,
          method: "DELETE",
        };
      },
      async onQueryStarted(info, { queryFulfilled, dispatch }) {
        try {
          const { data, meta } = await queryFulfilled;
          if (meta.response.status === 200 || meta.response.status === 201) {
            dispatch(
              apiSlice.util.updateQueryData(
                "getAllUserAddressQuery",
                undefined,
                (draft) => {
                  const oldData = JSON.parse(JSON.stringify(draft?.addresses));
                  const updatedData = oldData.filter(
                    (item) => item.id !== info.id
                  );
                  return {
                    ...draft,
                    addresses: updatedData,
                  };
                }
              )
            );
            info.success(data, meta.response.status);
          } else {
            info.error(data);
          }
        } catch (error) {
          info.error(error?.error);
        }
      },
    }),
  }),
});

export const {
  useGetCountryListApiQuery,
  useLazyGetCountryListApiQuery,
  useGetCountryListGuestApiQuery,
  useLazyGetCountryListGuestApiQuery,
  useGetStateListApiQuery,
  useLazyGetStateListApiQuery,
  useGetCityListApiQuery,
  useLazyGetCityListApiQuery,
  useGetAllUserAddressQueryQuery,
  useAddNewAddressMutation,
  useUpdateAddressApiMutation,
  useDeleteAddressApiMutation,
} = locationsApi;
