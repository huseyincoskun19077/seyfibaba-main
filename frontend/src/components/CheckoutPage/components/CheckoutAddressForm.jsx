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
  sortTurkeyStateOptions,
} from "@/data/turkey-cities";
import {
  AddressInvoiceFields,
  defaultInvoiceState,
  validateInvoiceForm,
} from "./InvoiceCheckoutSection";

const MapComponent = dynamic(() => import("@/components/MapComponent/Index"), {
  ssr: false,
});

const CheckoutAddressForm = ({ onAddressSaved, onCancel }) => {
  const webSettings = settings();
  const _auth = auth();
  const _user = _auth?.user || _auth;

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
  const [invoice, setInvoice] = useState(() =>
    defaultInvoiceState({
      tc_identity: _user?.tc_identity,
      tax_number: _user?.tax_number,
      tax_office: _user?.tax_office,
      company_name: _user?.company_name,
      is_e_invoice: _user?.is_e_invoice,
      postal_code: _user?.zip_code,
      invoice_type: _user?.invoice_type,
    })
  );

  const [countryDropdown, setCountryDropdown] = useState([]);
  const [stateDropdown, setStateDropdown] = useState(null);
  const [cityDropdown, setCityDropdown] = useState(null);
  const [errors, setErrors] = useState(null);
  const [location, setLocation] = useState(null);

  const [getCountryListApi] = useLazyGetCountryListApiQuery();
  const [getStateListApi] = useLazyGetStateListApiQuery();
  const [getCityListApi] = useLazyGetCityListApiQuery();
  const [addNewAddressQuery, { isLoading: isAddNewAddressLoading }] =
    useAddNewAddressMutation();

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
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [getCountryListApi]);

  const handleInputChange = (field, value) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  const handleCheckboxChange = (field) => {
    if (field === "home") {
      setFormData((prev) => ({ ...prev, home: !prev.home, office: false }));
    } else if (field === "office") {
      setFormData((prev) => ({ ...prev, office: !prev.office, home: false }));
    }
  };

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
    setInvoice(defaultInvoiceState());
    setStateDropdown(null);
    setCityDropdown(null);
    setErrors(null);
    setLocation(null);
  };

  const createAddressData = () => ({
    name: formData.fName,
    email: formData.email,
    phone: formData.phone,
    address: formData.address,
    type: formData.home ? "home" : formData.office ? "office" : null,
    country: formData.country,
    state: formData.state,
    city: formData.city,
    invoice_type: invoice.invoice_type,
    tc_identity: invoice.tc_identity,
    tax_number: invoice.tax_number,
    tax_office: invoice.tax_office,
    company_name: invoice.company_name,
    is_e_invoice: invoice.is_e_invoice ? 1 : 0,
    postal_code: invoice.postal_code,
    zip_code: invoice.postal_code,
    latitude:
      Number(webSettings?.map_status) === 1 && location
        ? location.lat
        : undefined,
    longitude:
      Number(webSettings?.map_status) === 1 && location
        ? location.lng
        : undefined,
  });

  const getState = async (value) => {
    if (!value?.id) return;
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
  };

  const getcity = async (value) => {
    if (!value?.id) return;
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
  };

  const selectCity = (value) => {
    if (value?.id) {
      setFormData((prev) => ({ ...prev, city: value.id }));
    }
  };

  const saveAddressSuccessHandler = (data, statusCode) => {
    if (statusCode === 200 || statusCode === 201) {
      resetData();
      toast.success(data?.notification || "Adres başarıyla eklendi", {
        autoClose: 1000,
      });
      if (onAddressSaved) onAddressSaved();
    }
  };

  const saveAddressErrorHandler = (error) => {
    if (error.data) setErrors(error.data.errors);
    const msg =
      error?.data?.message ||
      (error?.data?.errors && Object.values(error.data.errors).flat()?.[0]) ||
      "Adres kaydedilemedi";
    toast.error(msg, { autoClose: 2000 });
  };

  const saveAddress = async () => {
    const invoiceError = validateInvoiceForm(invoice);
    if (invoiceError) {
      toast.error(invoiceError);
      return;
    }

    const requestSaveAddress = async () => {
      await addNewAddressQuery({
        data: createAddressData(),
        token: auth()?.access_token,
        success: saveAddressSuccessHandler,
        error: saveAddressErrorHandler,
      });
    };

    if (Number(webSettings?.map_status) === 1) {
      if (!location) {
        toast.error("Lütfen konum seçin");
        return;
      }
    }
    await requestSaveAddress();
  };

  const hasError = (fieldName) => !!(errors && Object.hasOwn(errors, fieldName));
  const getErrorMessage = (fieldName) =>
    errors && Object.hasOwn(errors, fieldName) ? errors[fieldName][0] : "";

  return (
    <div className="w-full">
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

      <div className="form-area">
        <form>
          <AddressInvoiceFields invoice={invoice} onChange={setInvoice} />

          <div className="mb-6">
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
              />
            </div>
            <div className="sm:w-1/2 w-full">
              <InputCom
                label="Telefon Numarası*"
                placeholder="5xx xxx xx xx"
                inputClasses="w-full h-[50px]"
                value={formData.phone}
                inputHandler={(e) => handleInputChange("phone", e.target.value)}
                error={hasError("phone")}
                name="tel"
                type="tel"
              />
            </div>
          </div>

          <div className="mb-6">
            <h2 className="input-label capitalize block mb-2 text-qgray text-[13px] font-normal">
              Ülke*
            </h2>
            <div
              className={`w-full h-[50px] border flex justify-between items-center mb-2 ${
                hasError("country") ? "border-qred" : "border-qgray-border"
              }`}
            >
              <Selectbox
                action={getState}
                className="w-full px-5"
                defaultValue={
                  countryDropdown?.length > 0 &&
                  (countryDropdown.find(
                    (item) => parseInt(item.id) === parseInt(formData.country)
                  )?.name ||
                    "Türkiye")
                }
                datas={countryDropdown}
              >
                {({ item }) => (
                  <div className="flex justify-between items-center w-full">
                    <span className="text-[13px] text-qblack">{item}</span>
                    <ArrowDownIcoCheck />
                  </div>
                )}
              </Selectbox>
            </div>
          </div>

          <div className="flex rtl:space-x-reverse space-x-5 items-center mb-6">
            <div className="w-1/2">
              <h2 className="input-label capitalize block mb-2 text-qgray text-[13px] font-normal">
                İl*
              </h2>
              <div
                className={`w-full h-[50px] border flex justify-between items-center mb-2 ${
                  hasError("state") ? "border-qred" : "border-qgray-border"
                }`}
              >
                <SearchableSelectbox
                  action={getcity}
                  className="w-full px-5"
                  placeholder="İl ara..."
                  defaultValue={
                    stateDropdown?.length > 0 &&
                    (stateDropdown.find(
                      (item) => Number(item.id) === Number(formData.state)
                    )?.name ||
                      "Seçiniz")
                  }
                  datas={stateDropdown}
                >
                  {({ item }) => (
                    <div className="flex justify-between items-center w-full">
                      <span className="text-[13px] text-qblack">{item}</span>
                      <ArrowDownIcoCheck />
                    </div>
                  )}
                </SearchableSelectbox>
              </div>
            </div>
            <div className="w-1/2">
              <h2 className="input-label capitalize block mb-2 text-qgray text-[13px] font-normal">
                İlçe*
              </h2>
              <div
                className={`w-full h-[50px] border flex justify-between items-center mb-2 ${
                  hasError("city") ? "border-qred" : "border-qgray-border"
                }`}
              >
                <SearchableSelectbox
                  action={selectCity}
                  className="w-full px-5"
                  placeholder="İlçe ara..."
                  defaultValue={
                    cityDropdown?.length > 0 &&
                    (cityDropdown.find(
                      (item) => Number(item.id) === Number(formData.city)
                    )?.name ||
                      "Seçiniz")
                  }
                  datas={cityDropdown}
                >
                  {({ item }) => (
                    <div className="flex justify-between items-center w-full">
                      <span className="text-[13px] text-qblack">{item}</span>
                      <ArrowDownIcoCheck />
                    </div>
                  )}
                </SearchableSelectbox>
              </div>
            </div>
          </div>

          <div className="mb-6">
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

          <div className="flex rtl:space-x-reverse space-x-5 items-center">
            <div className="flex rtl:space-x-reverse space-x-2 items-center mb-10">
              <input
                checked={formData.home}
                onChange={() => handleCheckboxChange("home")}
                type="checkbox"
                name="home"
                id="home"
              />
              <label
                htmlFor="home"
                className="text-qblack text-[15px] select-none capitalize"
              >
                Ev
              </label>
            </div>
            <div className="flex rtl:space-x-reverse space-x-2 items-center mb-10">
              <input
                checked={formData.office}
                onChange={() => handleCheckboxChange("office")}
                type="checkbox"
                name="office"
                id="office"
              />
              <label
                htmlFor="office"
                className="text-qblack text-[15px] select-none"
              >
                Ofis
              </label>
            </div>
          </div>

          <button
            onClick={saveAddress}
            type="button"
            className="w-full h-[50px] disabled:cursor-not-allowed"
            disabled={isAddNewAddressLoading}
          >
            <div className="yellow-btn rounded">
              <span className="text-sm text-qblack">Adresi Kaydet</span>
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
