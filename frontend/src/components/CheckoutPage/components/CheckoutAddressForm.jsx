"use client";
import React, { useState, useEffect } from "react";
import dynamic from "next/dynamic";
import { toast } from "react-toastify";
import auth from "@/utils/auth";
import settings from "@/utils/settings";
import InputCom from "@/components/Helpers/InputCom";
import LoaderStyleOne from "@/components/Helpers/Loaders/LoaderStyleOne";
import Selectbox from "@/components/Helpers/Selectbox";
import SearchableSelectbox from "@/components/Helpers/SearchableSelectbox";
import ServeLangItem from "@/components/Helpers/ServeLangItem";
import {
  useLazyGetStateListApiQuery,
  useLazyGetCityListApiQuery,
  useLazyGetCountryListApiQuery,
  useAddNewAddressMutation,
} from "@/redux/features/locations/apiSlice";
import ArrowDownIcoCheck from "@/components/Helpers/icons/ArrowDownIcoCheck";
import {
  dedupeTurkeyDistrictOptions,
  findTurkeyCountry,
  sortTurkeyDistrictOptions,
  sortTurkeyStateOptions,
} from "@/data/turkey-cities";

const MapComponent = dynamic(() => import("@/components/MapComponent/Index"), {
  ssr: false,
});

const CheckoutAddressForm = ({ onAddressSaved, onCancel }) => {
  // Get website settings for map configuration
  const webSettings = settings();

  // Pre-fill from logged-in user info
  const _auth = auth();
  const _user = _auth?.user || _auth;

  // Form data state - same structure as AddressTab
  const [formData, setFormData] = useState({
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

  // Dropdown states
  const [countryDropdown, setCountryDropdown] = useState([]);
  const [stateDropdown, setStateDropdown] = useState(null);
  const [cityDropdown, setCityDropdown] = useState(null);

  // UI states
  const [errors, setErrors] = useState(null);
  const [location, setLocation] = useState(null);

  // Initialization location hooks REDUX RTK QUERY
  const [getCountryListApi] = useLazyGetCountryListApiQuery();
  const [getStateListApi, { isLoading: isGetStateLoading }] =
    useLazyGetStateListApiQuery();
  const [getCityListApi, { isLoading: isGetCityLoading }] =
    useLazyGetCityListApiQuery();
  const [addNewAddressQuery, { isLoading: isAddNewAddressLoading }] =
    useAddNewAddressMutation();

  /**
   * Fetch country list on component mount
   */
  useEffect(() => {
    const fetchCountries = async () => {
      try {
        const userToken = auth()?.access_token;
        const response = await getCountryListApi({ token: userToken });
        if (response.data) {
          const countries = response.data.countries || [];
          setCountryDropdown(countries);

          if (!formData.country) {
            const turkeyCountry = findTurkeyCountry(countries);
            if (turkeyCountry?.id) {
              await getState(turkeyCountry);
            }
          }
        }
      } catch (error) {
        console.error("Error fetching countries:", error);
      }
    };

    fetchCountries();
  }, [formData.country, getCountryListApi]);

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
    setStateDropdown(null);
    setCityDropdown(null);
    setErrors(null);
    setLocation(null);
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
   * Handles getState functionality
   * @param {Object} value - Selected country object with id
   */
  const getState = async (value) => {
    if (value) {
      if (value?.id) {
        setFormData((prev) => ({
          ...prev,
          country: value.id,
          state: null,
          city: null,
        }));
        const response = await getStateListApi({
          countryId: Number(value.id),
          token: auth()?.access_token,
        });
        if (response.isSuccess) {
          setCityDropdown(null);
          setStateDropdown(sortTurkeyStateOptions(response?.data?.states || []));
        }
      } else {
        console.error(
          `Argument is not valid. Argument should be object with id Ex: "{ id: 1 }"`
        );
      }
    }
  };

  /**
   * Handles getCities functionality
   * @param {Object} value - Selected state object with id
   */
  const getcity = async (value) => {
    if (value) {
      if (value?.id) {
        setFormData((prev) => ({ ...prev, state: value.id, city: null }));
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

  /**
   * Handler for city selection
   * @param {Object} value - Selected city object with id
   */
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
   * Uses the same pattern as AddressTab component
   */
  const saveAddressSuccessHandler = (data, statusCode) => {
    if (statusCode === 200 || statusCode === 201) {
      resetData();
      toast.success(
        data?.notification ? data?.notification : "Adres başarıyla eklendi",
        {
          autoClose: 1000,
        }
      );
      // Call the callback to refresh addresses in parent component
      if (onAddressSaved) {
        onAddressSaved();
      }
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
   * Check if field has error
   * @param {string} fieldName - Field name to check
   * @returns {boolean} Whether field has error
   */
  const hasError = (fieldName) => {
    return !!(errors && Object.hasOwn(errors, fieldName));
  };

  /**
   * Get error message for field
   * @param {string} fieldName - Field name to get error for
   * @returns {string} Error message
   */
  const getErrorMessage = (fieldName) => {
    return errors && Object.hasOwn(errors, fieldName)
      ? errors[fieldName][0]
      : "";
  };

  return (
    <div className="w-full">
      {/* Form Header */}
      <div className="flex justify-between items-center">
        <h2 className="sm:text-2xl text-xl text-qblack font-medium mb-5">
          Yeni Adres Ekle
        </h2>
        <span onClick={onCancel} className="text-qyellow cursor-pointer">
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
            <div className="w-full mb-5 sm:mb-0">
              <InputCom
                label="Ad Soyad*"
                placeholder="Ad Soyad"
                inputClasses="w-full h-[50px]"
                value={formData.fName}
                inputHandler={(e) => handleInputChange("fName", e.target.value)}
                error={hasError("name")}
                name="name"
                type="text"
                autoComplete="name"
              />
            </div>
            {hasError("name") && (
              <span className="text-sm mt-1 text-qred">
                {getErrorMessage("name")}
              </span>
            )}
          </div>

          {/* Email and Phone Fields */}
          <div className="flex rtl:space-x-reverse space-x-5 items-center mb-6">
            <div className="sm:w-1/2 w-full">
              <InputCom
                label="E-posta*"
                placeholder="E-posta"
                inputClasses="w-full h-[50px]"
                value={formData.email}
                inputHandler={(e) => handleInputChange("email", e.target.value)}
                error={hasError("email")}
                name="email"
                type="email"
                autoComplete="email"
                inputMode="email"
              />
              {hasError("email") && (
                <span className="text-sm mt-1 text-qred">
                  {getErrorMessage("email")}
                </span>
              )}
            </div>
            <div className="sm:w-1/2 w-full">
              <InputCom
                label="Telefon Numarası*"
                placeholder="012 3  *******"
                inputClasses="w-full h-[50px]"
                value={formData.phone}
                inputHandler={(e) => handleInputChange("phone", e.target.value)}
                error={hasError("phone")}
                name="tel"
                type="tel"
                autoComplete="tel"
                inputMode="tel"
              />
              {hasError("phone") && (
                <span className="text-sm mt-1 text-qred">
                  {getErrorMessage("phone")}
                </span>
              )}
            </div>
          </div>

          {/* Country Selection */}
          <div className="mb-6">
            <h2 className="input-label capitalize block mb-2 text-qgray text-[13px] font-normal">
              Ülke*
            </h2>
            <div
              className={`w-full h-[50px] border flex justify-between items-center border-qgray-border mb-2 ${
                hasError("country") ? "border-qred" : "border-qgray-border"
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
                    return item ? item.name : "Türkiye";
                  })()
                }
                datas={countryDropdown && countryDropdown}
              >
                {({ item }) => (
                  <>
                    <div className="flex justify-between items-center w-full">
                      <div>
                        <span className="text-[13px] text-qblack">{item}</span>
                      </div>
                      <span>
                        <ArrowDownIcoCheck />
                      </span>
                    </div>
                  </>
                )}
              </Selectbox>
            </div>
            {hasError("country") && (
              <span className="text-sm mt-1 text-qred">
                {getErrorMessage("country")}
              </span>
            )}
          </div>

          {/* State and City Selection */}
          <div className="flex rtl:space-x-reverse space-x-5 items-center mb-6">
            <div className="w-1/2">
              <h2 className="input-label capitalize block mb-2 text-qgray text-[13px] font-normal">
                İl*
              </h2>
              <div
                className={`w-full h-[50px] border flex justify-between items-center border-qgray-border mb-2 ${
                  hasError("state") ? "border-qred" : "border-qgray-border"
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
                          <ArrowDownIcoCheck />
                        </span>
                      </div>
                    </>
                  )}
                </SearchableSelectbox>
              </div>
              {hasError("state") && (
                <span className="text-sm mt-1 text-qred">
                  {getErrorMessage("state")}
                </span>
              )}
            </div>
            <div className="w-1/2">
              <h2 className="input-label capitalize block mb-2 text-qgray text-[13px] font-normal">
                İlçe*
              </h2>
              <div
                className={`w-full h-[50px] border flex justify-between items-center border-qgray-border mb-2 ${
                  hasError("city") ? "border-qred" : "border-qgray-border"
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
                          <ArrowDownIcoCheck />
                        </span>
                      </div>
                    </>
                  )}
                </SearchableSelectbox>
              </div>
              {hasError("city") && (
                <span className="text-sm mt-1 text-qred">
                  {getErrorMessage("city")}
                </span>
              )}
            </div>
          </div>

          {/* Map Component for Location Selection */}
          <div className="mb-6">
            <div>
              <MapComponent
                location={location}
                locationHandler={setLocation}
                mapKey={webSettings?.map_key}
                mapStatus={Number(webSettings?.map_status)}
                searchEnabled
                searchInputError={
                  hasError("address") ? getErrorMessage("address") : ""
                }
                searchInputHandler={(value) =>
                  handleInputChange("address", value)
                }
                searchInputValue={formData.address}
              />
            </div>
          </div>

          {/* Address Type Selection */}
          <div className="flex rtl:space-x-reverse space-x-5 items-center">
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
                Ev
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
                Ofis
              </label>
            </div>
          </div>

          {/* Submit Button */}
          <button
            onClick={saveAddress}
            type="button"
            className="w-full h-[50px] disabled:cursor-not-allowed"
            disabled={isAddNewAddressLoading}
          >
            <div className="yellow-btn rounded">
              <span className="text-sm text-qblack">
                Adresi Kaydet
              </span>
              {isAddNewAddressLoading && (
                <span className="w-5" style={{ transform: "scale(0.3)" }}>
                  <LoaderStyleOne />
                </span>
              )}
            </div>
          </button>
        </form>
      </div>
    </div>
  );
};

export default CheckoutAddressForm;
