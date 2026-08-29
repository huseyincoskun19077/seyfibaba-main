// React and Next.js imports
import React, { useContext, useEffect } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import Image from "next/image";

// Third-party library imports
import { toast } from "react-toastify";
import { useDispatch } from "react-redux";

// Component imports
import InputCom from "../../Helpers/InputCom";
import LoaderStyleOne from "../../Helpers/Loaders/LoaderStyleOne";
import ServeLangItem from "../../Helpers/ServeLangItem";
import LoginContext from "../../Contexts/LoginContext";

// Redux action imports
import { setWishlistData } from "../../../redux/features/wishlist/wishlistSlice";
import {
  useResendRegisterCodeApiMutation,
  useUserLoginApiMutation,
} from "@/redux/features/auth/apiSlice";
import { AUTH_STORAGE_SYNC_EVENT } from "@/redux/api/apiSlice";
import { safePostLoginRedirect, setAccessTokenCookie } from "@/utils/auth";
import { useLazyGetWishlistItemsApiQuery } from "@/redux/features/product/apiSlice";
import auth from "@/utils/auth";
import appConfig from "@/appConfig";
import redirectToSellerPanel from "@/utils/sellerSsoRedirect";
import SocialAuthButtons from "@/components/Auth/SocialAuthButtons";

// login line shapre
const LoginShape = () => {
  return (
    <svg
      width="172"
      height="29"
      viewBox="0 0 172 29"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
    >
      <path
        d="M1 5.08742C17.6667 19.0972 30.5 31.1305 62.5 27.2693C110.617 21.4634 150 -10.09 171 5.08727"
        stroke="#FCBF49"
      />
    </svg>
  );
};

// checked svg
const CheckedSvg = () => {
  return (
    <svg
      xmlns="http://www.w3.org/2000/svg"
      className="h-5 w-5"
      viewBox="0 0 20 20"
      fill="currentColor"
    >
      <path
        fillRule="evenodd"
        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
        clipRule="evenodd"
      />
    </svg>
  );
};

/**
 * SEND Component - Displays OTP verification message with resend option
 * @param {Function} action - Function to handle OTP resend
 * @returns {JSX.Element} OTP verification UI
 */
const SEND = ({ action, isPhoneLogin = false }) => {
  return (
    <div>
      <p className="text-xs text-qblack">
        {isPhoneLogin
          ? "Bu hesap e-posta doğrulaması bekliyor görünüyor. Telefon + SMS giriş kodu ile tekrar deneyin. Sorun sürerse çağrı merkezini arayın."
          : "Lütfen hesabınızı doğrulayın. OTP almadıysanız yeniden gönderip doğrulayın."}
      </p>
      {!isPhoneLogin && (
        <button
          type="button"
          onClick={action}
          className="text-sm text-blue-500 font-bold mt-2"
        >
          OTP Gönder
        </button>
      )}
    </div>
  );
};

/**
 * LoginWidget Component - Main login form component
 * @param {boolean} redirect - Whether to redirect after login (default: true)
 * @param {Function} loginActionPopup - Function to handle login popup action
 * @param {Function} notVerifyHandler - Function to handle unverified account
 * @param {"customer"|"seller"} variant - Login page variant
 * @returns {JSX.Element} Login form UI
 */
function LoginWidget({
  redirect = true,
  loginActionPopup,
  notVerifyHandler,
  variant = "customer",
}) {
  const isSellerLogin = variant === "seller";
  // Router and Redux hooks
  const router = useRouter();
  const dispatch = useDispatch();

  // Context hooks
  const loginPopupBoard = useContext(LoginContext);

  // Form state management
  const [formData, setFormData] = useState({
    email: "",
    phone: "+90",
    password: "",
  });

  // UI state management
  const [checked, setValue] = useState(false);
  const [loginType, setLoginType] = useState(isSellerLogin ? "phone" : "email");

  /**
   * Handles input field changes for form data
   * @param {Event} e - Input change event
   */
  const handleInputChange = (e) => {
    const { name, value } = e.target;
    const nextValue = value.replace(/\s+/g, "");
    setFormData((prev) => ({
      ...prev,
      [name]: nextValue,
    }));
  };

  /**
   * Toggles remember me checkbox state
   */
  const rememberMe = () => {
    setValue(!checked);
  };

  /**
   * get wishlist items api
   * @Initializaing useLazyGetWishlistItemsApiQuery @const getWishlistItemsApi
   * @func getWishlistItemsSuccessHandler @params data, error
   * @func getWishlistItems @params data
   */
  const [getWishlistItemsApi, { isLoading: isGetWishlistItemsLoading }] =
    useLazyGetWishlistItemsApiQuery();

  const getWishlistItemsSuccessHandler = (data, statusCode) => {
    if (statusCode === 200 || statusCode === 201) {
      dispatch(setWishlistData(data));
    }
  };

  const getWishlistItems = async () => {
    const userToken = auth()?.access_token;
    const data = {
      token: userToken,
      success: getWishlistItemsSuccessHandler,
    };
    await getWishlistItemsApi(data);
  };
  /**
   * @Initialization Resend Register Code Api @const resendRegisterCodeApi
   * @func successOtpHandler @param statusCode
   * @func sendOtpHandler
   */

  const [resendRegisterCodeApi, { isLoading: isResendLoading }] =
    useResendRegisterCodeApiMutation();

  const successOtpHandler = (statusCode) => {
    if (statusCode === 200 || statusCode === 201) {
      // Clear the OTP notification toast
      toast.dismiss();
      // Store email before clearing form data
      const emailForRedirect = formData.email;
      // Clear form data when OTP is successfully sent
      setFormData({ email: "", password: "" });
      router.push(`/verify-you?email=${emailForRedirect}`);
    } else {
      toast.error(ServeLangItem()?.OTP_sending_failed || "OTP gönderimi başarısız");
    }
  };

  const sendOtpHandler = async () => {
    if (loginType === "phone" || !formData.email) {
      toast.error("OTP için e-posta ile giriş seçin veya telefon + SMS kodu ile giriş yapın.");
      return;
    }

    await resendRegisterCodeApi({
      email: formData.email,
      success: successOtpHandler,
    });
  };

  /**
   * Handles user login functionality
   * @Initialization Login Api @const userLoginApi
   * @func loginSuccessHandler @param data
   * @func loginErrorHandler @param error
   * @func doLogin
   */
  const [userLoginApi, { isLoading: isLoginLoading }] =
    useUserLoginApiMutation();

  const loginSuccessHandler = async (data) => {
    if (data?.force_password_change && data?.redirect_url) {
      toast.success(data?.notification || "Giriş başarılı. Yeni şifrenizi oluşturun.");
      window.location.href = data.redirect_url;
      return;
    }

    if (isSellerLogin) {
      if (Number(data?.is_vendor) === 1 && data?.access_token) {
        await redirectToSellerPanel(data.access_token);
        return;
      }

      toast.error("Bu hesap bir satıcı hesabı değil. Müşteri girişi için ana giriş sayfasını kullanın.");
      return;
    }

    toast.success(data?.notification || ServeLangItem()?.Login_Successfully || "Başarıyla Giriş Yapıldı");
    setFormData({ email: "", phone: "+90", password: "" });
    localStorage.removeItem("auth");
    localStorage.setItem("auth", JSON.stringify(data));
    setAccessTokenCookie(data?.access_token);
    if (typeof window !== "undefined") {
      window.dispatchEvent(new Event(AUTH_STORAGE_SYNC_EVENT));
    }
    await getWishlistItems();
    if (redirect) {
      const next = safePostLoginRedirect(
        new URLSearchParams(window.location.search).get("next")
      );
      if (next) {
        window.location.assign(next);
        return;
      }
      router.push("/");
    } else {
      if (data) {
        loginPopupBoard.handlerPopup(false);
      }
    }
  };

  const loginErrorHandler = (error) => {
    const status = error?.status ?? error?.originalStatus;

    if (status === 429) {
      toast.error("Çok fazla giriş denemesi yaptınız. Lütfen 1 dakika bekleyip tekrar deneyin.");
      return;
    }

    const msg = String(error?.data?.notification || "").toLowerCase();
    const looksLikeVerifyAccount =
      status === 402 &&
      (msg.includes("otp") ||
        msg.includes("verify") ||
        msg.includes("doğrul") ||
        msg.includes("dogrul") ||
        msg.includes("hesabınızı") ||
        msg.includes("hesabinizi"));

    if (looksLikeVerifyAccount) {
      toast.warn(<SEND action={sendOtpHandler} isPhoneLogin={loginType === "phone"} />, {
        autoClose: false,
        icon: false,
        theme: "colored",
      });
      if (notVerifyHandler) {
        notVerifyHandler();
      }
      return;
    }
    if (status === 402 && error?.data?.notification) {
      toast.error(error.data.notification);
      return;
    }
    toast.error(ServeLangItem()?.Invalid_Credentials);
  };

  const doLogin = async () => {
    try {
      const result = await userLoginApi({
        email: loginType === "phone" ? formData.phone : formData.email,
        password: formData.password,
      }).unwrap();

      await loginSuccessHandler(result);
    } catch (error) {
      loginErrorHandler(error);
    }
  };

  return (
    <div className="w-full">
      {/* Header Section */}
      <div className="title-area flex flex-col justify-center items-center relative text-center mb-7">
        <h2 className="text-[34px] font-bold leading-[74px] text-qblack">
          {isSellerLogin ? "Satıcı Girişi" : ServeLangItem()?.Log_In}
        </h2>
        <div className="shape -mt-6">
          <LoginShape />
        </div>
      </div>

      {/* Form Section */}
      <div className="input-area">
        {/* Login Type Toggle */}
        <div className="flex items-center justify-center mb-6">
          <div className="flex items-center bg-gray-100 rounded-full p-1">
            <button
              type="button"
              onClick={() => {
                setLoginType("email");
                setFormData((prev) => ({ ...prev, email: prev.email || "" }));
              }}
              className={`px-4 py-2 rounded-full text-sm font-medium transition-all duration-300 ${
                loginType === 'email' 
                  ? 'bg-white text-blue-600 shadow-md' 
                  : 'text-gray-500 hover:text-gray-700'
              }`}
            >
              <svg className="w-4 h-4 inline-block mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
              E-posta
            </button>
            <button
              type="button"
              onClick={() => {
                setLoginType('phone');
                setFormData((prev) => ({
                  ...prev,
                  phone: prev.phone?.startsWith("+90") ? prev.phone : "+90",
                }));
              }}
              className={`px-4 py-2 rounded-full text-sm font-medium transition-all duration-300 ${
                loginType === 'phone' 
                  ? 'bg-white text-blue-600 shadow-md' 
                  : 'text-gray-500 hover:text-gray-700'
              }`}
            >
              <svg className="w-4 h-4 inline-block mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
              </svg>
              Telefon
            </button>
          </div>
        </div>

        {/* Email Input Field */}
        <div className="input-item mb-5">
          {loginType === "email" ? (
            <InputCom
              placeholder="ornek@email.com"
              label="E-posta Adresi*"
              name="email"
              type="text"
              inputClasses="h-[50px]"
              inputHandler={handleInputChange}
              value={formData.email}
            />
          ) : (
            <div className="input-com w-full h-full">
              <label className="input-label capitalize block mb-2 text-qgray text-[13px] font-normal">
                Telefon Numarası*
              </label>
              <div className="h-[50px] rounded-md bg-white border border-qgray-border flex items-center overflow-hidden">
                <div className="h-full px-3 flex items-center gap-2 border-r border-qgray-border bg-[#f7f7f7]">
                  <Image
                    width={18}
                    height={12}
                    src="/assets/images/countries/TR.svg"
                    alt="TR"
                  />
                  <span className="text-qblack text-sm font-medium">+90</span>
                </div>
                <input
                  name="phone"
                  type="tel"
                  inputMode="numeric"
                  placeholder="5XXXXXXXXX"
                  value={String(formData.phone || "").replace(/^\+90/, "")}
                  onChange={(e) =>
                    setFormData((prev) => ({
                      ...prev,
                      phone: `+90${String(e.target.value || "").replace(/\D/g, "").slice(0, 10)}`,
                    }))
                  }
                  className="flex-1 h-full bg-transparent text-qblack placeholder:text-qgray px-4 text-sm focus:outline-none"
                />
              </div>
            </div>
          )}
        </div>

        {/* Password Input Field */}
        <div className="input-item mb-5">
          <InputCom
            placeholder="* * * * * *"
            label={ServeLangItem()?.Password + "*"}
            name="password"
            type="password"
            inputClasses="h-[50px]"
            inputHandler={handleInputChange}
            value={formData.password}
            onKeyDown={(e) => e.key === "Enter" && doLogin()}
          />
        </div>

        {/* Remember Me and Forgot Password Section */}
        <div className="forgot-password-area flex justify-between items-center mb-7">
          {/* Remember Me Checkbox */}
          <div className="remember-checkbox flex items-center space-x-2.5 rtl:space-x-reverse cursor-pointer select-none">
            <button
              onClick={rememberMe}
              type="button"
              className="w-5 h-5 text-qblack flex justify-center items-center border border-light-gray cursor-pointer"
            >
              {checked && <CheckedSvg />}
            </button>
            <span onClick={rememberMe} className="text-base text-black">
              {ServeLangItem()?.Remember_Me}
            </span>
          </div>

          {/* Forgot Password Link */}
          <Link href="/forgot-password">
            <span className="text-base text-qyellow cursor-pointer">
              {ServeLangItem()?.Forgot_password}?
            </span>
          </Link>
        </div>

        {/* Login Button Section */}
        <div className="signin-area mb-3.5">
          <div className="flex justify-center">
            <button
              onClick={doLogin}
              type="button"
              disabled={isLoginLoading}
              className="black-btn mb-6 text-sm text-white w-full h-[50px] font-semibold flex justify-center bg-purple items-center"
            >
              <span>{ServeLangItem()?.Login}</span>
              {isLoginLoading && (
                <span className="w-5 " style={{ transform: "scale(0.3)" }}>
                  <LoaderStyleOne />
                </span>
              )}
            </button>
          </div>
        </div>

        {!isSellerLogin ? <SocialAuthButtons /> : null}

        {/* Sign Up Section */}
        {isSellerLogin ? (
          <div className="signup-area flex flex-col items-center text-center">
            <p className="text-base text-qgraytwo font-normal mb-2">
              Henüz satıcı değil misiniz?
            </p>
            <Link href="/satici-kayit">
              <span className="text-qblack cursor-pointer capitalize font-medium">
                Satıcı ol
              </span>
            </Link>
            <Link href="/login" className="mt-3">
              <span className="text-qyellow cursor-pointer text-sm">
                Müşteri girişi
              </span>
            </Link>
          </div>
        ) : (
          <div className="signup-area flex flex-col items-center text-center">
            <p className="text-base text-qgraytwo font-normal">
              {ServeLangItem()?.Dontt_have_an_account} ?
              {redirect ? (
                <Link href="/signup">
                  <span className="ml-2 text-qblack cursor-pointer capitalize">
                    {ServeLangItem()?.sign_up_free}
                  </span>
                </Link>
              ) : (
                <button onClick={loginActionPopup} type="button">
                  <span className="ml-2 text-qblack cursor-pointer capitalize">
                    {ServeLangItem()?.sign_up_free}
                  </span>
                </button>
              )}
            </p>
            <Link href="/satici-kayit" className="mt-3">
              <span className="text-qyellow cursor-pointer text-sm font-600">
                Satıcı ol
              </span>
            </Link>
          </div>
        )}
      </div>
    </div>
  );
}

export default LoginWidget;
