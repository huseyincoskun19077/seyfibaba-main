"use client";
import { useContext, useEffect, useRef, useState } from "react";
import Image from "next/image";
import Link from "next/link";
import dynamic from "next/dynamic";
import { toast } from "react-toastify";
import { useSelector } from "react-redux";
import InputCom from "../Helpers/InputCom";
import PageTitle from "../Helpers/PageTitle";
import ServeLangItem from "../Helpers/ServeLangItem";
import CheckmarkIcon from "../Helpers/icons/CheckmarkIcon";
import UploadIcon from "../Helpers/icons/UploadIcon";
import appConfig from "@/appConfig";
import auth from "./../../utils/auth";
import settings from "./../../utils/settings";
import LoginContext from "../Contexts/LoginContext";
import { useSellerRequestApiMutation } from "@/redux/features/auth/apiSlice";
import LoaderStyleOne from "../Helpers/Loaders/LoaderStyleOne";
import { useRouter } from "next/navigation";
import SellerFaqSection from "./SellerFaqSection";

// Dynamically import MapComponent to prevent loading on the server
const MapComponent = dynamic(() => import("../MapComponent/Index"), {
  ssr: false, // Disable server-side rendering for this component
});

function BecomeSaller() {
  const loginPopupBoard = useContext(LoginContext);
  // Configuration and settings
  const webSettings = settings();
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const router = useRouter();
  // Form data state
  const [formData, setFormData] = useState({
    email: "",
    phone: "",
    shopName: "",
    shopAddress: "",
  });

  // UI state
  const [checked, setCheck] = useState(false);
  const [errors, setErrors] = useState(null);

  // Image states
  const [logoImg, setLogoImg] = useState(null);
  const [coverImg, setCoverImg] = useState(null);
  const [uploadLogo, setUploadLogo] = useState(null);
  const [uploadCoverImg, setUploadCoverImg] = useState(null);
  const [defaultCover, setDefaultCover] = useState(null);
  const [defaultLogo, setLogo] = useState(null);

  // Location state
  const [location, setLocation] = useState(null);

  // Refs for file inputs
  const logoImgInput = useRef(null);
  const coverImgInput = useRef(null);

  /**
   * Effect to set default images from website setup
   */
  useEffect(() => {
    if (!defaultCover || !defaultLogo) {
      if (websiteSetup) {
        setDefaultCover(
          websiteSetup.payload?.image_content.become_seller_banner
        );
        setLogo(websiteSetup.payload?.image_content.become_seller_avatar);
      }
    }
  }, [defaultCover, defaultLogo, websiteSetup]);

  /**
   * Handle form input changes
   * @param {string} field - The field name to update
   * @param {string} value - The new value for the field
   */
  const handleInputChange = (field, value) => {
    setFormData((prev) => ({
      ...prev,
      [field]: value,
    }));
  };

  /**
   * Toggle terms and conditions checkbox
   */
  const rememberMe = () => {
    setCheck(!checked);
  };

  /**
   * Browse and select logo image
   */
  const browseLogoImg = () => {
    logoImgInput.current.click();
  };

  /**
   * Handle logo image file selection and preview
   * @param {Event} e - File input change event
   */
  const logoImgChangHandler = (e) => {
    if (e.target.value !== "") {
      const imgReader = new FileReader();
      imgReader.onload = (event) => {
        setLogoImg(event.target.result);
      };
      imgReader.readAsDataURL(e.target.files[0]);
      setUploadLogo(e.target.files[0]);
    }
  };

  /**
   * Browse and select cover image
   */
  const browseCoverImg = () => {
    coverImgInput.current.click();
  };

  /**
   * Handle cover image file selection and preview
   * @param {Event} e - File input change event
   */
  const coverImgChangHandler = (e) => {
    if (e.target.value !== "") {
      const imgReader = new FileReader();
      imgReader.onload = (event) => {
        setCoverImg(event.target.result);
      };
      imgReader.readAsDataURL(e.target.files[0]);
      setUploadCoverImg(e.target.files[0]);
    }
  };

  /**
   * seller request functionality
   * @Initializing useSellerRequestApiMutation @const sellerRequestApi
   * @func sellerRequestSuccessHandler @params data , statusCode
   * @func sellerRequestErrorHandler @params error
   * @func sellerReqHandler @params data
   * @func sellerReq
   */
  const [sellerRequestApi, { isLoading: sellerRequestLoading }] =
    useSellerRequestApiMutation();
  const sellerRequestSuccessHandler = (data, statusCode) => {
    if (statusCode === 200 || statusCode === 201) {
      toast.success(
        "Tebrikler! Satıcı başvurunuz başarıyla iletildi."
      );
      router.push("/");
    }
  };
  const sellerRequestErrorHandler = (error) => {
    if (error.status === 422) {
      setErrors(error?.data?.errors);
      if (error?.data?.errors?.logo) {
        toast.error(error?.data?.errors?.logo[0]);
      }
      if (error?.data?.errors?.banner_image) {
        toast.error(error?.data?.errors?.banner_image[0]);
      }
    } else {
      if (error?.data?.notification) {
        toast.error(error?.data?.notification);
      }
    }
  };
  const sellerReqHandler = async (data) => {
    const userToken = auth()?.access_token;
    await sellerRequestApi({
      data: data,
      token: userToken,
      success: sellerRequestSuccessHandler,
      error: sellerRequestErrorHandler,
    });
  };

  /**
   * Handle seller registration form submission
   */
  const sellerReq = async () => {
    if (!checked) {
      toast.error("Please agree to the terms and conditions");
      return;
    }
    const data = {
      banner_image: uploadCoverImg,
      shop_name: formData.shopName,
      email: formData.email,
      phone: formData.phone,
      address: formData.shopAddress,
      open_at: "10.00AM",
      closed_at: "10.00PM",
      agree_terms_condition: checked,
      logo: uploadLogo,
      latitude: location ? location.lat : null,
      longitude: location ? location.lng : null,
    };

    // Check if user is authenticated
    if (auth()) {
      // Check if map is enabled and location is required
      if (Number(webSettings?.map_status) === 1) {
        if (location) {
          sellerReqHandler(data);
        } else {
          toast.error("Please select location");
        }
      } else {
        sellerReqHandler(data);
      }
    } else {
      loginPopupBoard.handlerPopup(true);
    }
  };

  return (
    <div className="become-saller-wrapper w-full">
      {/* Page Title Section */}
      <div className="title mb-10">
        <PageTitle
          title="Satici Basvurusu"
          breadcrumb={[
            { name: ServeLangItem()?.home, path: "/" },
            { name: ServeLangItem()?.Become_seller, path: "/become-seller" },
          ]}
        />
      </div>

      {/* Main Content Section */}
      <div className="content-wrapper w-full mb-10">
        <div className="container-x mx-auto">
          <div className="w-full bg-white sm:p-[30px] p-3">
            <div className="flex xl:flex-row flex-col-reverse xl:space-x-11">
              {/* Left Column - Form Section */}
              <div className="xl:w-[824px]">
                {/* Seller Information Section */}
                <div className="title w-full mb-4">
                  <h2 className="text-[22px] font-semibold text-qblack mb-1">
                    {ServeLangItem()?.Seller_Information}
                  </h2>
                  <p className="text-[15px] text-qgraytwo">
                    {
                      ServeLangItem()
                        ?.Fill_the_form_below_or_write_us_We_will_help_you_as_soon_as_possible
                    }
                  </p>
                </div>

                {/* Seller Information Form Fields */}
                <div className="input-area">
                  {/* Email Input */}
                  <div className="mb-5">
                    <InputCom
                      placeholder={ServeLangItem()?.Email}
                      label={ServeLangItem()?.Email_Address + "*"}
                      name="email"
                      type="email"
                      inputClasses="h-[50px]"
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

                  {/* Phone Input */}
                  <div className="mb-5">
                    <InputCom
                      placeholder="0213 *********"
                      label={ServeLangItem()?.phone + "*"}
                      name="phone"
                      type="text"
                      inputClasses="h-[50px]"
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

                {/* Shop Information Section */}
                <div className="title w-full mt-10 mb-4">
                  <h2 className="text-[22px] font-semibold text-qblack mb-1">
                    {ServeLangItem()?.Shop_Information}
                  </h2>
                  <p className="text-[15px] text-qgraytwo">
                    {
                      ServeLangItem()
                        ?.Fill_the_form_below_or_write_us_We_will_help_you_as_soon_as_possible
                    }
                  </p>
                </div>

                {/* Shop Information Form Fields */}
                <div className="input-area">
                  {/* Shop Name Input */}
                  <div className="mb-5">
                    <InputCom
                      placeholder={ServeLangItem()?.Name}
                      label={ServeLangItem()?.Shop_Name + "*"}
                      name="shopname"
                      type="text"
                      inputClasses="h-[50px]"
                      value={formData.shopName}
                      inputHandler={(e) =>
                        handleInputChange("shopName", e.target.value)
                      }
                      error={!!(errors && Object.hasOwn(errors, "shop_name"))}
                    />
                    {errors && Object.hasOwn(errors, "shop_name") ? (
                      <span className="text-sm mt-1 text-qred">
                        {errors.shop_name[0]}
                      </span>
                    ) : (
                      ""
                    )}
                  </div>

                  {/* Location Map Component */}
                  <div className="mb-5">
                    <div>
                      <MapComponent
                        location={location}
                        locationHandler={setLocation}
                        mapKey={webSettings?.map_key}
                        mapStatus={Number(webSettings?.map_status)}
                        searchEnabled={
                          Number(webSettings?.map_status) ? true : false
                        }
                        searchInputError={
                          errors &&
                          Object.hasOwn(errors, "address") &&
                          errors.address[0]
                        }
                        searchInputHandler={(value) =>
                          handleInputChange("shopAddress", value)
                        }
                        searchInputValue={formData.shopAddress}
                      />
                    </div>
                  </div>

                  {/* Terms and Conditions Checkbox */}
                  <div className="remember-checkbox flex items-center space-x-2.5 mb-5">
                    <button
                      onClick={rememberMe}
                      type="button"
                      className="w-5 h-5 text-qblack flex justify-center items-center border border-light-gray"
                    >
                      {checked && <CheckmarkIcon />}
                    </button>
                    <Link href="/seller-terms-condition">
                      <span className="text-base text-black cursor-pointer">
Seyfibaba satıcı şartlarını ve gizlilik politikasını kabul ediyorum
                      </span>
                    </Link>
                  </div>

                  {/* Submit Button */}
                  <div className="signin-area mb-3">
                    <div className="flex justify-center">
                      <button
                        onClick={sellerReq}
                        disabled={sellerRequestLoading}
                        type="button"
                        className="black-btn disabled:bg-opacity-50 disabled:cursor-not-allowed  text-sm text-white w-[490px] h-[50px] font-semibold flex justify-center bg-purple items-center"
                      >
                        <span>{ServeLangItem()?.Create_Seller_Account}</span>

                        {sellerRequestLoading && (
                          <span
                            className="w-5 "
                            style={{ transform: "scale(0.3)" }}
                          >
                            <LoaderStyleOne />
                          </span>
                        )}
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              {/* Right Column - Image Upload Section */}
              <div className="flex-1 mb-10 xl:mb-0">
                {/* Logo Upload Section */}
                <div className="update-logo w-full mb-9">
                  <h2 className="text-xl tracking-wide font-bold text-qblack mb-2">
                    {ServeLangItem()?.Update_Logo}
                  </h2>
                  <p className="text-sm text-qgraytwo mb-5">
                    {ServeLangItem()?.Profile_of_at_least_Size}
                    <span className="ml-1 text-qblack">300x300</span>.{" "}
                    {ServeLangItem()?.Gifs_work_too}.
                    <span className="ml-1 text-qblack">
                      {ServeLangItem()?.Max_5mb}
                    </span>
                    .
                  </p>
                  <div className="flex xl:justify-center justify-start">
                    <div className="relative">
                      {/* Logo Preview */}
                      <div className="sm:w-[198px] sm:h-[198px] w-[199px] h-[199px] rounded-full overflow-hidden relative">
                        {defaultLogo && (
                          <Image
                            objectFit="cover"
                            layout="fill"
                            src={
                              logoImg
                                ? logoImg
                                : defaultLogo &&
                                  appConfig.BASE_URL + defaultLogo
                            }
                            alt=""
                          />
                        )}
                      </div>

                      {/* Hidden File Input */}
                      <input
                        ref={logoImgInput}
                        onChange={(e) => logoImgChangHandler(e)}
                        type="file"
                        className="hidden"
                      />

                      {/* Upload Button */}
                      <div
                        onClick={browseLogoImg}
                        className="w-[32px] h-[32px] absolute bottom-7 sm:right-0 right-[105px]  hover:bg-[#F539F8] bg-[#F539F8] rounded-full cursor-pointer"
                      >
                        <UploadIcon />
                      </div>
                    </div>
                  </div>
                </div>

                {/* Cover Image Upload Section */}
                <div className="update-cover w-full">
                  <h2 className="text-xl tracking-wide font-bold text-qblack mb-2">
                    {ServeLangItem()?.Update_Cover}
                  </h2>
                  <p className="text-sm text-qgraytwo mb-5">
                    {ServeLangItem()?.Cover_of_at_least_Size}
                    <span className="ml-1 text-qblack">1170x920</span>.
                  </p>
                  <div className="flex justify-center">
                    <div className="w-full relative">
                      {/* Cover Image Preview */}
                      <div className="w-full h-[120px] rounded-lg overflow-hidden object-cover">
                        {defaultCover && (
                          <Image
                            layout="fill"
                            src={
                              coverImg
                                ? coverImg
                                : defaultCover &&
                                  appConfig.BASE_URL + defaultCover
                            }
                            alt=""
                          />
                        )}
                      </div>

                      {/* Hidden File Input */}
                      <input
                        ref={coverImgInput}
                        onChange={(e) => coverImgChangHandler(e)}
                        type="file"
                        className="hidden"
                      />

                      {/* Upload Button */}
                      <div
                        onClick={browseCoverImg}
                        className="w-[32px] h-[32px] absolute -bottom-4 right-4 bg-[#F539F8] hover:bg-[#F539F8] rounded-full cursor-pointer"
                      >
                        <UploadIcon />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="container-x mx-auto px-4">
          <SellerFaqSection />
        </div>
      </div>
    </div>
  );
}

export default BecomeSaller;
