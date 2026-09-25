// React and Next.js imports
import React, { useContext, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
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
import redirectToSellerPanel from "@/utils/sellerSsoRedirect";
import SocialAuthButtons from "@/components/Auth/SocialAuthButtons";

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
      <div className="mb-6 text-center">
        <p className="text-sm text-[#04334a]/65">
          {isSellerLogin
            ? "Telefon numaranızın son 10 hanesi veya e-posta ile giriş yapın."
            : "E-posta veya telefon ile hesabınıza giriş yapın."}
        </p>
      </div>

      <div className="input-area">
        <div className="mb-6 flex items-center justify-center">
          <div className="flex items-center rounded-full bg-[#F4F6F7] p-1">
            <button
              type="button"
              onClick={() => {
                setLoginType("email");
                setFormData((prev) => ({ ...prev, email: prev.email || "" }));
              }}
              className={`rounded-full px-4 py-2 text-sm font-600 transition-all duration-300 ${
                loginType === "email"
                  ? "bg-[#04334a] text-white shadow-sm"
                  : "text-[#04334a]/55 hover:text-[#04334a]"
              }`}
            >
              <svg className="mr-1.5 inline-block h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
              E-posta
            </button>
            <button
              type="button"
              onClick={() => {
                setLoginType("phone");
                setFormData((prev) => ({
                  ...prev,
                  phone: prev.phone?.startsWith("+90") ? prev.phone : "+90",
                }));
              }}
              className={`rounded-full px-4 py-2 text-sm font-600 transition-all duration-300 ${
                loginType === "phone"
                  ? "bg-[#04334a] text-white shadow-sm"
                  : "text-[#04334a]/55 hover:text-[#04334a]"
              }`}
            >
              <svg className="mr-1.5 inline-block h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
              </svg>
              Telefon
            </button>
          </div>
        </div>

        <div className="input-item mb-5">
          {loginType === "email" ? (
            <InputCom
              placeholder="ornek@email.com"
              label="E-posta Adresi*"
              name="email"
              type="text"
              inputClasses="h-[50px] bg-[#F4F6F7] text-[#04334a]"
              labelClasses="font-700 text-[#04334a] text-[13px]"
              inputHandler={handleInputChange}
              value={formData.email}
            />
          ) : (
            <div className="input-com h-full w-full">
              <label className="input-label mb-2 block text-[13px] font-700 capitalize text-[#04334a]">
                Telefon Numarası*
              </label>
              <div className="flex h-[50px] items-center overflow-hidden rounded-md bg-[#F4F6F7] focus-within:ring-2 focus-within:ring-[#FCBF49]/60">
                <div className="flex h-full items-center gap-2 border-r border-[#04334a]/10 px-3">
                  <Image
                    width={18}
                    height={12}
                    src="/assets/images/countries/TR.svg"
                    alt="TR"
                  />
                  <span className="text-sm font-medium text-[#04334a]">+90</span>
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
                  className="h-full flex-1 bg-transparent px-4 text-sm text-[#04334a] placeholder:text-qgray focus:outline-none"
                />
              </div>
            </div>
          )}
        </div>

        <div className="input-item mb-5">
          <InputCom
            placeholder="* * * * * *"
            label={ServeLangItem()?.Password + "*"}
            name="password"
            type="password"
            inputClasses="h-[50px] bg-[#F4F6F7] text-[#04334a]"
            labelClasses="font-700 text-[#04334a] text-[13px]"
            inputHandler={handleInputChange}
            value={formData.password}
            onKeyDown={(e) => e.key === "Enter" && doLogin()}
          />
        </div>

        <div className="forgot-password-area mb-7 flex items-center justify-between">
          <div className="remember-checkbox flex cursor-pointer select-none items-center space-x-2.5 rtl:space-x-reverse">
            <button
              onClick={rememberMe}
              type="button"
              className="flex h-5 w-5 cursor-pointer items-center justify-center rounded border border-[#04334a]/20 text-[#04334a]"
            >
              {checked && <CheckedSvg />}
            </button>
            <span onClick={rememberMe} className="text-sm text-[#04334a]">
              {ServeLangItem()?.Remember_Me}
            </span>
          </div>

          <Link href="/forgot-password">
            <span className="cursor-pointer text-sm font-600 text-[#04334a] underline underline-offset-2 hover:text-qyellow">
              {ServeLangItem()?.Forgot_password}?
            </span>
          </Link>
        </div>

        <div className="signin-area mb-3.5">
          <div className="flex justify-center">
            <button
              onClick={doLogin}
              type="button"
              disabled={isLoginLoading}
              className="mb-6 flex h-[50px] w-full items-center justify-center rounded-lg bg-[#04334a] text-sm font-800 text-white transition hover:bg-[#032736] disabled:opacity-70"
            >
              <span>{isSellerLogin ? "Panele Giriş Yap" : ServeLangItem()?.Login}</span>
              {isLoginLoading && (
                <span className="w-5" style={{ transform: "scale(0.3)" }}>
                  <LoaderStyleOne />
                </span>
              )}
            </button>
          </div>
        </div>

        {!isSellerLogin ? <SocialAuthButtons /> : null}

        {isSellerLogin ? (
          <div className="signup-area flex flex-col items-center gap-2 pt-1 text-center">
            <p className="text-sm font-normal text-[#04334a]/55">
              Henüz satıcı değil misiniz?
            </p>
            <Link
              href="/satici-kayit"
              className="inline-flex h-11 w-full items-center justify-center rounded-lg bg-qyellow text-sm font-800 text-[#04334a] hover:brightness-95"
            >
              Satıcı başvurusu yap
            </Link>
            <Link
              href="/login"
              className="mt-1 text-sm font-600 text-[#04334a]/70 underline underline-offset-2 hover:text-[#04334a]"
            >
              Müşteri girişi
            </Link>
          </div>
        ) : (
          <div className="signup-area flex flex-col items-center text-center">
            <p className="text-sm font-normal text-[#04334a]/60">
              {ServeLangItem()?.Dontt_have_an_account}?{" "}
              {redirect ? (
                <Link href="/signup">
                  <span className="cursor-pointer font-700 capitalize text-[#04334a] hover:text-qyellow">
                    {ServeLangItem()?.sign_up_free}
                  </span>
                </Link>
              ) : (
                <button onClick={loginActionPopup} type="button">
                  <span className="cursor-pointer font-700 capitalize text-[#04334a] hover:text-qyellow">
                    {ServeLangItem()?.sign_up_free}
                  </span>
                </button>
              )}
            </p>
          </div>
        )}
      </div>
    </div>
  );
}

export default LoginWidget;
