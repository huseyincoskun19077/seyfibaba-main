"use client";
import { useEffect, useState } from "react";
import ArrowDownIcoCheck from "@/components/Helpers/icons/ArrowDownIcoCheck";
import InputCom from "@/components/Helpers/InputCom";
import Selectbox from "@/components/Helpers/Selectbox";
import SearchableSelectbox from "@/components/Helpers/SearchableSelectbox";
import ServeLangItem from "@/components/Helpers/ServeLangItem";
import MapComponent from "@/components/MapComponent/Index";
import {
  useLazyGetStateListApiQuery,
  useLazyGetCityListApiQuery,
  useLazyGetCountryListGuestApiQuery,
} from "@/redux/features/locations/apiSlice";
import auth from "@/utils/auth";
import settings from "@/utils/settings";
import {
  dedupeTurkeyDistrictOptions,
  findTurkeyCountry,
  sortTurkeyDistrictOptions,
  sortTurkeyStateOptions,
} from "@/data/turkey-cities";
import { AddressInvoiceFields } from "./InvoiceCheckoutSection";

function GuestCheckoutAddressForm({
  fName,
  setFName,
  lName,
  setLName,
  email,
  setEmail,
  phone,
  setPhone,
  address,
  setAddress,
  home,
  setHome,
  office,
  setOffice,
  country,
  setCountry,
  setCountryName,
  state,
  setState,
  city,
  setCity,
  guestLocation,
  setGuestLocation,
  errors,
  shippingHandler,
  invoice,
  setInvoice,
}) {
  const webSettings = settings();

  // Dropdown states
  const [countryDropdown, setCountryDropdown] = useState([]);
  const [stateDropdown, setStateDropdown] = useState(null);
  const [cityDropdown, setCityDropdown] = useState(null);

  // Initialization location hooks REDUX RTK QUERY
  const [getCountryListGuestApi] = useLazyGetCountryListGuestApiQuery();
  const [getStateListApi, { isLoading: isGetStateLoading }] =
    useLazyGetStateListApiQuery();
  const [getCityListApi, { isLoading: isGetCityLoading }] =
    useLazyGetCityListApiQuery();

  /**
   * Fetch country list on component mount
   */
  useEffect(() => {
    const fetchCountries = async () => {
      try {
        const response = await getCountryListGuestApi();
        if (response.data) {
          const countries = response.data.countries || [];
          setCountryDropdown(countries);

          if (!country) {
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
  }, [country, getCountryListGuestApi]);

  /**
   * Handles getState functionality
   * @param {Object} value - Selected country object with id
   */
  const getState = async (value) => {
    if (value) {
      if (value?.id) {
        setCountry(value.id);          // keep ID for UI dropdown comparison
        setCountryName(value.name);    // store name for address payload
        setState(null);
        setCity(null);
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
        setState(value.name);  // store name (string) — backend expects string, not ID
        const response = await getCityListApi({
          stateId: Number(value.id),
          token: auth()?.access_token,
        });
        if (response.isSuccess) {
          setCity(null);
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
        setCity(value.name);           // store name (string) — backend expects string, not ID
        shippingHandler(false, value.id); // shipping rules still use numeric ID
      } else {
        console.error(
          `Argument is not valid. Argument should be object with id Ex: "{ id: 1 }"`
        );
      }
    }
  };
  return (
    <div className="w-full">
      <div className="form-area">
        {invoice && setInvoice ? (
          <AddressInvoiceFields invoice={invoice} onChange={setInvoice} />
        ) : null}
        <div className="mb-6">
          <div className="sm:flex sm:space-x-5 items-center">
            <div className="sm:w-1/2 w-full  mb-5 sm:mb-0">
              <InputCom
                label="Ad*"
                placeholder="Ad"
                inputClasses="w-full h-[50px]"
                value={fName}
                inputHandler={(e) => setFName(e.target.value)}
                error={!!(errors && Object.hasOwn(errors, "fName"))}
                name="given-name"
                type="text"
                autoComplete="given-name"
              />
              {errors && Object.hasOwn(errors, "fName") ? (
                <span className="text-sm mt-1 text-qred">
                  {errors.fName[0]}
                </span>
              ) : (
                ""
              )}
            </div>
            <div className="sm:w-1/2 w-full">
              <InputCom
                label="Soyad*"
                placeholder="Soyad"
                inputClasses="w-full h-[50px]"
                value={lName}
                inputHandler={(e) => setLName(e.target.value)}
                error={!!(errors && Object.hasOwn(errors, "lName"))}
                name="family-name"
                type="text"
                autoComplete="family-name"
              />
              {errors && Object.hasOwn(errors, "lName") ? (
                <span className="text-sm mt-1 text-qred">
                  {errors.lName[0]}
                </span>
              ) : (
                ""
              )}
            </div>
          </div>
        </div>
        <div className="flex space-x-5 items-center mb-6">
          <div className="sm:w-1/2 w-full">
            <InputCom
              label="E-posta Adresi*"
              placeholder="E-posta"
              inputClasses="w-full h-[50px]"
              value={email}
              inputHandler={(e) => setEmail(e.target.value)}
              error={!!(errors && Object.hasOwn(errors, "email"))}
              name="email"
              type="email"
              autoComplete="email"
              inputMode="email"
            />
            {errors && Object.hasOwn(errors, "email") ? (
              <span className="text-sm mt-1 text-qred">{errors.email[0]}</span>
            ) : (
              ""
            )}
          </div>
          <div className="sm:w-1/2 w-full">
            <InputCom
              label="Telefon Numarası*"
              placeholder="012 3  *******"
              inputClasses="w-full h-[50px]"
              value={phone}
              inputHandler={(e) => setPhone(e.target.value)}
              error={!!(errors && Object.hasOwn(errors, "phone"))}
              name="tel"
              type="tel"
              autoComplete="tel"
              inputMode="tel"
            />
            {errors && Object.hasOwn(errors, "phone") ? (
              <span className="text-sm mt-1 text-qred">{errors.phone[0]}</span>
            ) : (
              ""
            )}
          </div>
        </div>
        <div className="mb-6">
          <h2 className="input-label capitalize block  mb-2 text-qgray text-[13px] font-normal">
            Ülke*
          </h2>
          <div
            className={`w-full h-[50px] border flex justify-between items-center mb-2 ${
              !!(errors && Object.hasOwn(errors, "country"))
                ? "border-qred"
                : "border-[#EDEDED]"
            }`}
          >
            <Selectbox
              action={getState}
              className="w-full px-5"
              defaultValue={
                countryDropdown &&
                countryDropdown.length > 0 &&
                (function () {
                  const item = countryDropdown.find(
                    (countryItem) => parseInt(countryItem.id) === parseInt(country)
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
          {errors && Object.hasOwn(errors, "country") ? (
            <span className="text-sm mt-1 text-qred">{errors.country[0]}</span>
          ) : (
            ""
          )}
        </div>
        <div className="flex space-x-5 items-center mb-6">
          <div className="w-1/2">
            <h2 className="input-label capitalize block  mb-2 text-qgray text-[13px] font-normal">
              İl*
            </h2>
            <div
              className={`w-full h-[50px] border flex justify-between items-center mb-2 ${
                !!(errors && Object.hasOwn(errors, "state"))
                  ? "border-qred"
                  : "border-[#EDEDED]"
              }`}
            >
              <SearchableSelectbox
                action={getcity}
                className="w-full px-5"
                placeholder="İl ara..."
                defaultValue="Seçiniz"
                datas={stateDropdown && stateDropdown}
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
              </SearchableSelectbox>
            </div>
            {errors && Object.hasOwn(errors, "state") ? (
              <span className="text-sm mt-1 text-qred">{errors.state[0]}</span>
            ) : (
              ""
            )}
          </div>
          <div className="w-1/2">
            <h2 className="input-label capitalize block  mb-2 text-qgray text-[13px] font-normal">
              İlçe*
            </h2>
            <div
              className={`w-full h-[50px] border flex justify-between items-center mb-2 ${
                !!(errors && Object.hasOwn(errors, "city"))
                  ? "border-qred"
                  : "border-[#EDEDED]"
              }`}
            >
              <SearchableSelectbox
                action={selectCity}
                className="w-full px-5"
                placeholder="İlçe ara..."
                defaultValue="Seçiniz"
                datas={cityDropdown && cityDropdown}
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
              </SearchableSelectbox>
            </div>
            {errors && Object.hasOwn(errors, "city") ? (
              <span className="text-sm mt-1 text-qred">{errors.city[0]}</span>
            ) : (
              ""
            )}
          </div>
        </div>
        <div className=" mb-6">
          <div>
            <MapComponent
              location={guestLocation}
              locationHandler={setGuestLocation}
              mapKey={webSettings?.map_key}
              mapStatus={Number(webSettings?.map_status)}
              searchEnabled={Number(webSettings?.map_status) ? true : false}
              searchInputError={
                errors && Object.hasOwn(errors, "address") && errors.address[0]
              }
              searchInputHandler={setAddress}
              searchInputValue={address}
            />
          </div>
        </div>
        <div className="flex space-x-5 items-center ">
          <div className="flex space-x-2 items-center mb-10">
            <div>
              <input
                checked={home}
                onChange={() => {
                  setHome(!home);
                  setOffice(false);
                }}
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
          <div className="flex space-x-2 items-center mb-10">
            <div>
              <input
                checked={office}
                onChange={() => {
                  setOffice(!office);
                  setHome(false);
                }}
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
      </div>
    </div>
  );
}

export default GuestCheckoutAddressForm;
