"use client";
import { useState } from "react";
import dynamic from "next/dynamic";

// Third-party library imports
import { toast } from "react-toastify";

// Local utility imports
import auth from "./../../../../utils/auth";
import settings from "./../../../../utils/settings";

// Component imports
import InputCom from "../../../Helpers/InputCom";
import LoaderStyleOne from "../../../Helpers/Loaders/LoaderStyleOne";
import Selectbox from "../../../Helpers/Selectbox";
import SearchableSelectbox from "../../../Helpers/SearchableSelectbox";
import ServeLangItem from "../../../Helpers/ServeLangItem";
import AddressDltIco from "@/components/Helpers/icons/AddressDltIco";
import AddressEditIco from "@/components/Helpers/icons/AddressEditIco";
import {
  dedupeTurkeyDistrictOptions,
  sortTurkeyStateOptions,
} from "@/data/turkey-cities";
import {
  useGetAllUserAddressQueryQuery,
  useLazyGetStateListApiQuery,
  useLazyGetCityListApiQuery,
  useAddNewAddressMutation,
  useUpdateAddressApiMutation,
  useDeleteAddressApiMutation,
} from "@/redux/features/locations/apiSlice";

// Dynamically import MapComponent to prevent loading on the server
const MapComponent = dynamic(
  () => import("../../../../components/MapComponent/Index"),
  {
    ssr: false, // Disable server-side rendering for this component
  }
);
export default function AddressesTab({ countryLists }) {
  // Get website settings for map configuration
  const webSettings = settings();

  // form visibility state
  const [newAddress, setNewAddress] = useState(false);

  // location states
  const [countryDropdown, setCountryDropdown] = useState(countryLists || []);
  const [stateDropdown, setStateDropdown] = useState(null);
  const [cityDropdown, setCityDropdown] = useState(null);

  // form data state
  const [formData, setFormData] = useState({
    fName: "",
    email: "",
    phone: "",
    address: "",
    home: true,
    office: false,
    country: null,
    state: null,
    city: null,
  });

  // UI states
  const [errors, setErrors] = useState(null);
  const [location, setLocation] = useState(null);
  const [editAdd, setEdit] = useState(null);

  /**
   * Handles input field changes for form data
   * @param {string} field - The field name to update
   * @param {any} value - The new value for the field
   */
  const handleInputChange = (field, value) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  /**
   * Handles checkbox changes for address type selection
   * Ensures only one checkbox is selected at a time
   * @param {string} field - The checkbox field ('home' or 'office')
   */
  const handleCheckboxChange = (field) => {
    if (field === "home") {
      setFormData((prev) => ({ ...prev, home: !prev.home, office: false }));
    } else if (field === "office") {
      setFormData((prev) => ({ ...prev, office: !prev.office, home: false }));
    }
  };

  /**
   * Resets all form data to initial state
   */
  const resetData = () => {
    setFormData({
      fName: "",
      email: "",
      phone: "",
      address: "",
      home: true,
      office: false,
      country: null,
      state: null,
      city: null,
    });
    setCountryDropdown(null);
    setStateDropdown(null);
    setCityDropdown(null);
    setErrors(null);
    setNewAddress(false);
  };

  /**
   * Creates address data object for API requests
   * @returns {Object} Formatted address data object
   */
  const createAddressData = () => {
    return {
      name: formData.fName,
      email: formData.email,
      phone: formData.phone,
      address: formData.address,
      type: formData.home ? "home" : formData.office ? "office" : null,
      country: formData.country,
      state: formData.state,
      city: formData.city,
      latitude:
        Number(webSettings?.map_status) === 1 && location
          ? location.lat
          : undefined,
      longitude:
        Number(webSettings?.map_status) === 1 && location
          ? location.lng
          : undefined,
    };
  };

  /**
   * Fetches all user addresses from the API
   */
  const { data: addressesData, isFetching: isFetchingAddresses } =
    useGetAllUserAddressQueryQuery({ token: auth()?.access_token });
  const { addresses } = addressesData || {};

  /**
   * Handles getState functionality
   * @Initialization getState Api @const getStateListApi
   * @func getState
   */

  const [getStateListApi, { isLoading: isGetStateLoading }] =
    useLazyGetStateListApiQuery();

  const getState = async (value) => {
    if (value?.id) {
      setFormData((prev) => ({ ...prev, country: value.id }));
      const response = await getStateListApi({
        countryId: Number(value.id),
        token: auth()?.access_token,
      });
      if (response.isSuccess) {
        setCityDropdown(null);
        setStateDropdown(response?.data?.states);
      }
    } else {
      console.error(
        `Argument is not valid. Argument should be object with id Ex: "{ id: 1 }"`
      );
    }
  };

  /**
   * Handles getCities functionality
   * @Initialization getCities Api @const getCityListApi
   * @func getCity
   */

  const [getCityListApi, { isLoading: isGetCityLoading }] =
    useLazyGetCityListApiQuery();

  const getcity = async (value) => {
    if (value) {
      if (value?.id) {
        setFormData((prev) => ({ ...prev, state: value.id }));
        const response = await getCityListApi({
          stateId: Number(value.id),
          token: auth()?.access_token,
        });
        if (response.isSuccess) {
          setCityDropdown(
            dedupeTurkeyDistrictOptions(response?.data?.cities || [], value?.name)
          );
        }
      } else {
        console.error(
          `Argument is not valid. Argument should be object with id Ex: "{ id: 1 }"`
        );
      }
    }
  };

  // Handler for city selection
  const selectCity = (value) => {
    if (value) {
      if (value?.id) {
        setFormData((prev) => ({ ...prev, city: value.id }));
      } else {
        console.error(
          `Argument is not valid. Argument should be object with id Ex: "{ id: 1 }"`
        );
      }
    }
  };

  /**
   * Add new address functionality
   * First set the form data to initial state using @func addNewAddressHandler
   * @Initialization addNewAddressQuery Api @const addNewAddressQuery
   * @func saveAddressSuccessHandler @const saveAddressSuccessHandler @params data, statusCode
   * @func saveAddressErrorHandler @const saveAddressErrorHandler @params error
   * @func saveAddress @const saveAddress
   */
  const addNewAddressHandler = () => {
    const _auth = auth();
    const _user = _auth?.user || _auth;
    setFormData({
      fName: _user?.name || "",
      email: _user?.email || "",
      phone: _user?.phone || "",
      address: "",
      home: true,
      office: false,
      country: null,
      state: null,
      city: null,
    });
    setNewAddress(true);
  };

  const [addNewAddressQuery, { isLoading: isAddNewAddressLoading }] =
    useAddNewAddressMutation();
  const saveAddressSuccessHandler = (data, statusCode) => {
    if (statusCode === 200 || statusCode === 201) {
      resetData();
      toast.success(
        data?.notification ? data?.notification : "Adres başarıyla eklendi",
        {
          autoClose: 1000,
        }
      );
    }
  };
  const saveAddressErrorHandler = (error) => {
    error.data && setErrors(error.data.errors);
    if (error.status === 403) {
      toast.error(error.data.message, {
        autoClose: 1000,
      });
    }
  };
  const saveAddress = async () => {
    // requestSaveAddress function to save address
    const requestSaveAddress = async () => {
      const userData = createAddressData();
      const userToken = auth()?.access_token;
      await addNewAddressQuery({
        data: userData,
        token: userToken,
        success: saveAddressSuccessHandler,
        error: saveAddressErrorHandler,
      });
    };

    if (Number(webSettings?.map_status) === 1) {
      if (location) {
        await requestSaveAddress();
      } else {
        toast.error("Lütfen konum seçin");
      }
    } else {
      await requestSaveAddress();
    }
  };

  /**
   * updating an existing address functionality
   * @func editAddress @params id
   * * Find address by id
   * * if address found then
   * * setEdit to true
   * * set the form data
   * * getState and getCity by country id and state id
   * * setLocation by latitude and longitude
   * @Initialization updateAddressApiMutation Api @const updateAddressApi
   * @func updateAddressSuccessHandler @const updateAddressSuccessHandler @params data
   * @func updateAddressErrorHandler @const updateAddressErrorHandler @params error
   * @func updateAddress @const updateAddress
   */

  const editAddress = async (id) => {
    const addressData = addresses.find((item) => item.id === id);
    if (addressData) {
      setEdit(id);
      setFormData({
        fName: addressData.name,
        email: addressData.email,
        phone: addressData.phone,
        address: addressData.address,
        home: addressData.type === "home" ? true : false,
        office: addressData.type === "office" ? true : false,
        country: parseInt(addressData.country_id),
        state: parseInt(addressData.state_id),
        city: parseInt(addressData.city_id),
      });
      await getState({ id: addressData.country_id });
      await getcity({ id: addressData.state_id });
      if (
        Number(webSettings?.map_status) === 1 &&
        addressData?.latitude &&
        addressData?.longitude
      ) {
        setLocation({
          lat: Number(addressData?.latitude) ?? null,
          lng: Number(addressData?.longitude) ?? null,
        });
      }
      setNewAddress(!newAddress);
    } else {
      toast.error("Adres bulunamadı");
    }
  };

  const [updateAddressApi, { isLoading: isUpdateAddressLoading }] =
    useUpdateAddressApiMutation();

  const updateAddressSuccessHandler = (data) => {
    toast.success(data?.notification, {
      autoClose: 1000,
    });
  };

  const updateAddressErrorHandler = (error) => {
    error && setErrors(error.data.errors);
    if (error.status === 403) {
      toast.error(error.data.message, {
        autoClose: 1000,
      });
    }
  };
  const updateAddress = async (id) => {
    const data = createAddressData();
    const userToken = auth()?.access_token;

    if (Number(webSettings?.map_status) === 1) {
      if (location) {
        await updateAddressApi({
          id: id,
          data: data,
          token: userToken,
          success: updateAddressSuccessHandler,
          error: updateAddressErrorHandler,
        });
      } else {
        toast.error("Lütfen konum seçin");
      }
    } else {
      await updateAddressApi({
        id: id,
        data: data,
        token: userToken,
        success: updateAddressSuccessHandler,
        error: updateAddressErrorHandler,
      });
    }
  };

  /**
   * Delete address functionality
   * @Initialization deleteAddressApiMutation Api @const deleteAddressApi
   * @func deleteAddressSuccessHandler @const deleteAddressSuccessHandler @params data
   * @func deleteAddressErrorHandler @const deleteAddressErrorHandler @params error
   * @func deleteAddress @const deleteAddress @params id
   */

  const [deleteAddressApi, { isLoading: isDeleteAddressLoading }] =
    useDeleteAddressApiMutation();

  const deleteAddressSuccessHandler = (data) => {
    toast.success(data?.notification, {
      autoClose: 1000,
    });
  };

  const deleteAddressErrorHandler = (error) => {
    toast.error(error && error.data.notification, {
      autoClose: 1000,
    });
  };

  const deleteAddress = async (id) => {
    const userToken = auth()?.access_token;
    await deleteAddressApi({
      id: id,
      token: userToken,
      success: deleteAddressSuccessHandler,
      error: deleteAddressErrorHandler,
    });
  };

  return (
    <>
      {/* Add New Address Button */}
      <div className="w-[180px] h-[50px] mt-4 mb-5">
        <button
          onClick={addNewAddressHandler}
          type="button"
          className="yellow-btn rounded"
        >
          <div className="w-full text-sm font-semibold">
            {ServeLangItem()?.Add_New_Address}
          </div>
        </button>
      </div>

      {/* Address Form Section */}
      {newAddress && (
        <div className="w-full">
          {/* Form Header */}
          <div className="flex justify-between items-center">
            <h2 className="sm:text-2xl text-xl text-qblack font-medium mb-5">
              {ServeLangItem()?.Add_New_Address}
            </h2>
            <span
              onClick={() => setNewAddress(!newAddress)}
              className="text-qyellow cursor-pointer"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                className="h-5 w-5"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fillRule="evenodd"
                  d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z"
                  clipRule="evenodd"
                />
              </svg>
            </span>
          </div>

          {/* Form Content */}
          <div className="form-area">
            <form>
              {/* Name Field */}
              <div className="mb-6">
                <div className="w-full  mb-5 sm:mb-0">
                  <InputCom
                    label={ServeLangItem()?.Name + "*"}
                    placeholder="Ad Soyad"
                    inputClasses="w-full h-[50px]"
                    value={formData.fName}
                    inputHandler={(e) =>
                      handleInputChange("fName", e.target.value)
                    }
                    error={!!(errors && Object.hasOwn(errors, "name"))}
                  />
                </div>
                {errors && Object.hasOwn(errors, "name") ? (
                  <span className="text-sm mt-1 text-qred">
                    {errors.name[0]}
                  </span>
                ) : (
                  ""
                )}
              </div>

              {/* Email and Phone Fields */}
              <div className="flex rtl:space-x-reverse space-x-5 items-center mb-6">
                <div className="sm:w-1/2 w-full">
                  <InputCom
                    label={ServeLangItem()?.Email + "*"}
                    placeholder={ServeLangItem()?.Email}
                    inputClasses="w-full h-[50px]"
                    value={formData.email}
                    inputHandler={(e) =>
                      handleInputChange("email", e.target.value)
                    }
                    error={!!(errors && Object.hasOwn(errors, "email"))}
                  />
                  {errors && Object.hasOwn(errors, "email") ? (
                    <span className="text-sm mt-1 text-qred">
                      {errors.email[0]}
                    </span>
                  ) : (
                    ""
                  )}
                </div>
                <div className="sm:w-1/2 w-full">
                  <InputCom
                    label={ServeLangItem()?.Phone_Number + "*"}
                    placeholder="012 3  *******"
                    inputClasses="w-full h-[50px]"
                    value={formData.phone}
                    inputHandler={(e) =>
                      handleInputChange("phone", e.target.value)
                    }
                    error={!!(errors && Object.hasOwn(errors, "phone"))}
                  />
                  {errors && Object.hasOwn(errors, "phone") ? (
                    <span className="text-sm mt-1 text-qred">
                      {errors.phone[0]}
                    </span>
                  ) : (
                    ""
                  )}
                </div>
              </div>

              {/* Country Selection */}
              <div className="mb-6">
                <h2 className="input-label capitalize block  mb-2 text-qgray text-[13px] font-normal">
                  {ServeLangItem()?.Country}*
                </h2>
                <div
                  className={`w-full h-[50px] border flex justify-between items-center border-qgray-border mb-2 ${
                    !!(errors && Object.hasOwn(errors, "country"))
                      ? "border-qred"
                      : "border-qgray-border"
                  }`}
                >
                  <Selectbox
                    action={getState}
                    className="w-full px-5"
                    defaultValue={
                      countryDropdown &&
                      countryDropdown.length > 0 &&
                      (function () {
                        let item =
                          countryDropdown.length > 0 &&
                          countryDropdown.find(
                            (item) =>
                              parseInt(item.id) === parseInt(formData.country)
                          );
                        return item ? item.name : "Seçiniz";
                      })()
                    }
                    datas={countryDropdown && countryDropdown}
                  >
                    {({ item }) => (
                      <>
                        <div className="flex justify-between items-center w-full">
                          <div>
                            <span className="text-[13px] text-qblack">
                              {item}
                            </span>
                          </div>
                          <span>
                            <svg
                              width="11"
                              height="7"
                              viewBox="0 0 11 7"
                              fill="none"
                              xmlns="http://www.w3.org/2000/svg"
                            >
                              <path
                                d="M5.4 6.8L0 1.4L1.4 0L5.4 4L9.4 0L10.8 1.4L5.4 6.8Z"
                                fill="#222222"
                              />
                            </svg>
                          </span>
                        </div>
                      </>
                    )}
                  </Selectbox>
                </div>
                {errors && Object.hasOwn(errors, "country") ? (
                  <span className="text-sm mt-1 text-qred">
                    {errors.country[0]}
                  </span>
                ) : (
                  ""
                )}
              </div>

              {/* State and City Selection */}
              <div className="flex rtl:space-x-reverse space-x-5 items-center mb-6">
                <div className="w-1/2">
                  <h2 className="input-label capitalize block  mb-2 text-qgray text-[13px] font-normal">
                    {ServeLangItem()?.State}*
                  </h2>
                  <div
                    className={`w-full h-[50px] border flex justify-between items-center border-qgray-border mb-2 ${
                      !!(errors && Object.hasOwn(errors, "state"))
                        ? "border-qred"
                        : "border-qgray-border"
                    }`}
                  >
                    <SearchableSelectbox
                      action={getcity}
                      className="w-full px-5"
                      placeholder="İl ara..."
                      defaultValue={
                        stateDropdown &&
                        stateDropdown.length > 0 &&
                        (function () {
                          let item = stateDropdown.find(
                            (item) =>
                              Number(item.id) === Number(formData.state)
                          );
                          return item ? item.name : "Seçiniz";
                        })()
                      }
                      datas={stateDropdown && stateDropdown}
                    >
                      {({ item }) => (
                        <>
                          <div className="flex justify-between items-center w-full">
                            <div>
                              <span className="text-[13px] text-qblack">
                                {item}
                              </span>
                            </div>
                            <span>
                              <svg
                                width="11"
                                height="7"
                                viewBox="0 0 11 7"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                              >
                                <path
                                  d="M5.4 6.8L0 1.4L1.4 0L5.4 4L9.4 0L10.8 1.4L5.4 6.8Z"
                                  fill="#222222"
                                />
                              </svg>
                            </span>
                          </div>
                        </>
                      )}
                    </SearchableSelectbox>
                  </div>
                  {errors && Object.hasOwn(errors, "state") ? (
                    <span className="text-sm mt-1 text-qred">
                      {errors.state[0]}
                    </span>
                  ) : (
                    ""
                  )}
                </div>
                <div className="w-1/2">
                  <h2 className="input-label capitalize block  mb-2 text-qgray text-[13px] font-normal">
                    {ServeLangItem()?.City}*
                  </h2>
                  <div
                    className={`w-full h-[50px] border flex justify-between items-center border-qgray-border mb-2 ${
                      !!(errors && Object.hasOwn(errors, "city"))
                        ? "border-qred"
                        : "border-qgray-border"
                    }`}
                  >
                    <SearchableSelectbox
                      action={selectCity}
                      className="w-full px-5"
                      placeholder="İlçe ara..."
                      defaultValue={
                        cityDropdown &&
                        cityDropdown.length > 0 &&
                        (function () {
                          let item = cityDropdown.find(
                            (item) =>
                              Number(item.id) === Number(formData.city)
                          );
                          return item ? item.name : "Seçiniz";
                        })()
                      }
                      datas={cityDropdown && cityDropdown}
                    >
                      {({ item }) => (
                        <>
                          <div className="flex justify-between items-center w-full">
                            <div>
                              <span className="text-[13px] text-qblack">
                                {item}
                              </span>
                            </div>
                            <span>
                              <svg
                                width="11"
                                height="7"
                                viewBox="0 0 11 7"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                              >
                                <path
                                  d="M5.4 6.8L0 1.4L1.4 0L5.4 4L9.4 0L10.8 1.4L5.4 6.8Z"
                                  fill="#222222"
                                />
                              </svg>
                            </span>
                          </div>
                        </>
                      )}
                    </SearchableSelectbox>
                  </div>
                  {errors && Object.hasOwn(errors, "city") ? (
                    <span className="text-sm mt-1 text-qred">
                      {errors.city[0]}
                    </span>
                  ) : (
                    ""
                  )}
                </div>
              </div>

              {/* Map Component for Location Selection */}
              <div className=" mb-6">
                <div>
                  <MapComponent
                    location={location}
                    locationHandler={setLocation}
                    mapKey={webSettings?.map_key}
                    mapStatus={Number(webSettings?.map_status)}
                    searchEnabled
                    searchInputError={
                      errors &&
                      Object.hasOwn(errors, "address") &&
                      errors.address[0]
                    }
                    searchInputHandler={(value) =>
                      handleInputChange("address", value)
                    }
                    searchInputValue={formData.address}
                  />
                </div>
              </div>

              {/* Address Type Selection */}
              <div className="flex rtl:space-x-reverse space-x-5 items-center ">
                <div className="flex rtl:space-x-reverse space-x-2 items-center mb-10">
                  <div>
                    <input
                      checked={formData.home}
                      onChange={() => handleCheckboxChange("home")}
                      type="checkbox"
                      name="home"
                      id="home"
                    />
                  </div>
                  <label
                    htmlFor="home"
                    className="text-qblack text-[15px] select-none capitalize"
                  >
                    {ServeLangItem()?.home}
                  </label>
                </div>
                <div className="flex rtl:space-x-reverse space-x-2 items-center mb-10">
                  <div>
                    <input
                      checked={formData.office}
                      onChange={() => handleCheckboxChange("office")}
                      type="checkbox"
                      name="office"
                      id="office"
                    />
                  </div>
                  <label
                    htmlFor="office"
                    className="text-qblack text-[15px] select-none"
                  >
                    {ServeLangItem()?.Office}
                  </label>
                </div>
              </div>

              {/* Submit Buttons */}
              {editAdd ? (
                <button
                  onClick={() => updateAddress(editAdd)}
                  type="button"
                  className="w-full h-[50px] disabled:cursor-not-allowed"
                  disabled={isUpdateAddressLoading}
                >
                  <div className="yellow-btn  rounded">
                    <span className="text-sm text-qblack">
                      {ServeLangItem()?.Update_address}
                    </span>
                    {isUpdateAddressLoading && (
                      <span
                        className="w-5 "
                        style={{ transform: "scale(0.3)" }}
                      >
                        <LoaderStyleOne />
                      </span>
                    )}
                  </div>
                </button>
              ) : (
                <button
                  onClick={saveAddress}
                  type="button"
                  className="w-full h-[50px] disabled:cursor-not-allowed"
                  disabled={isAddNewAddressLoading}
                >
                  <div className="yellow-btn  rounded">
                    <span className="text-sm text-qblack">
                      {ServeLangItem()?.Save_Address}
                    </span>
                    {isAddNewAddressLoading && (
                      <span
                        className="w-5 "
                        style={{ transform: "scale(0.3)" }}
                      >
                        <LoaderStyleOne />
                      </span>
                    )}
                  </div>
                </button>
              )}
            </form>
          </div>
        </div>
      )}

      {/* Addresses List Section */}
      {!newAddress && (
        <div className="w-full">
          {!isFetchingAddresses && addressesData ? (
            <>
              {addresses && addresses.length === 0 && (
                <div className="w-full p-6 bg-yellow-50 border border-yellow-200 rounded text-sm text-gray-600 mb-4">
                  <p className="font-semibold mb-1">Henüz teslimat adresiniz yok.</p>
                  <p>Sipariş verebilmek için yukarıdaki <strong>"Yeni Adres Ekle"</strong> butonuyla bir teslimat adresi ekleyin.</p>
                  <p className="mt-1 text-xs text-gray-500">Not: Kişisel bilgilerinizdeki adres farklı bir alandır. Buraya ödeme/teslimat adresi girilir.</p>
                </div>
              )}
              <div className="grid sm:grid-cols-2 grid-cols-1 gap-[30px] w-full">
              {addresses &&
                addresses.length > 0 &&
                [...addresses].reverse().map((item, index) => (
                  <div
                    key={index}
                    className="w-full bg-primarygray rounded p-5 border"
                  >
                    {/* Address Header */}
                    <div className="flex justify-between items-center">
                      <p className="title text-[22px] font-semibold">
                        {ServeLangItem()?.Address} #{addresses.length - index}
                      </p>
                      <div className="flex rtl:space-x-reverse space-x-2.5 items-center">
                        {/* Edit Button */}
                        <button
                          onClick={() => editAddress(item.id)}
                          type="button"
                          className="border border-qgray text-qyellow w-[34px] h-[34px] rounded-full flex justify-center items-center"
                        >
                          <AddressEditIco />
                        </button>
                        {/* Delete Button */}
                        <button
                          onClick={() => deleteAddress(item.id)}
                          type="button"
                          className="border border-qgray w-[34px] h-[34px] rounded-full flex justify-center items-center"
                        >
                          <AddressDltIco />
                        </button>
                      </div>
                    </div>

                    {/* Address Details */}
                    <div className="mt-5">
                      <table>
                        <tbody>
                          <tr className="flex mb-3">
                            <td className="text-base text-qgraytwo w-[70px] block line-clamp-1 capitalize">
                              <p>{ServeLangItem()?.Name}:</p>
                            </td>
                            <td className="text-base text-qblack line-clamp-1 font-medium">
                              {item.name}
                            </td>
                          </tr>
                          <tr className="flex mb-3">
                            <td className="text-base text-qgraytwo w-[70px] block line-clamp-1 capitalize">
                              <p>{ServeLangItem()?.Email}:</p>
                            </td>
                            <td className="text-base text-qblack line-clamp-1 font-medium">
                              {item.email}
                            </td>
                          </tr>
                          <tr className="flex mb-3">
                            <td className="text-base text-qgraytwo w-[70px] block line-clamp-1 capitalize">
                              <p>{ServeLangItem()?.phone}:</p>
                            </td>
                            <td className="text-base text-qblack line-clamp-1 font-medium">
                              {item.phone}
                            </td>
                          </tr>
                          <tr className="flex mb-3">
                            <td className="text-base text-qgraytwo w-[70px] block line-clamp-1 capitalize">
                              <p>{ServeLangItem()?.Country}:</p>
                            </td>
                            <td className="text-base text-qblack line-clamp-1 font-medium">
                              {item.country?.name || "-"}
                            </td>
                          </tr>
                          <tr className="flex mb-3">
                            <td className="text-base text-qgraytwo w-[70px] block line-clamp-1 capitalize">
                              <p>{ServeLangItem()?.State}:</p>
                            </td>
                            <td className="text-base text-qblack line-clamp-1 font-medium">
                              {item.country_state?.name || "-"}
                            </td>
                          </tr>
                          <tr className="flex mb-3">
                            <td className="text-base text-qgraytwo w-[70px] block line-clamp-1 capitalize">
                              <p>{ServeLangItem()?.City}:</p>
                            </td>
                            <td className="text-base text-qblack line-clamp-1 font-medium">
                              {item.city?.name || "-"}
                            </td>
                          </tr>
                          {item.address && (
                            <tr className="flex mb-3">
                              <td className="text-base text-qgraytwo w-[70px] block line-clamp-1 capitalize">
                                <p>Adres:</p>
                              </td>
                              <td className="text-base text-qblack font-medium">
                                {item.address}
                              </td>
                            </tr>
                          )}
                        </tbody>
                      </table>
                    </div>
                  </div>
                ))}
              </div>
            </>
          ) : (
            <div className="flex justify-center items-center h-full mt-10">
              <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-gray-900"></div>
            </div>
          )}
        </div>
      )}
    </>
  );
}
