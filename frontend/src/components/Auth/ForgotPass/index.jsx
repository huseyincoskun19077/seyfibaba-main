"use client";

// React and Next.js imports
import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import Image from "next/image";

// Third-party library imports
import { toast } from "react-toastify";
import { useSelector } from "react-redux";

// Local imports
import InputCom from "../../Helpers/InputCom";
import LoaderStyleOne from "../../Helpers/Loaders/LoaderStyleOne";
import ServeLangItem from "../../Helpers/ServeLangItem";
import {
  useResendOtpApiMutation,
  useSendOtpApiMutation,
  useVerifyOtpApiMutation,
  useUserForgotApiMutation,
  useUserResetApiMutation,
} from "@/redux/features/auth/apiSlice";
import appConfig from "@/appConfig";

const IMAGE_FALLBACK = "/assets/images/server-error.png";

// SVG Components for decorative shapes
const LineShape = () => (
  <svg
    width="354"
    height="30"
    viewBox="0 0 354 30"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
  >
    <path
      d="M1 28.8027C17.6508 20.3626 63.9476 8.17089 113.509 17.8802C166.729 28.3062 341.329 42.704 353 1"
      stroke="#FCBF49"
      strokeWidth="2"
      strokeLinecap="round"
    />
  </svg>
);

const LineShapeTwo = () => (
  <svg
    width="354"
    height="30"
    viewBox="0 0 354 30"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
  >
    <path
      d="M1 28.8027C17.6508 20.3626 63.9476 8.17089 113.509 17.8802C166.729 28.3062 341.329 42.704 353 1"
      stroke="#FCBF49"
      strokeWidth="2"
      strokeLinecap="round"
    />
  </svg>
);

export default function ForgotPass() {
  // Router for navigation
  const router = useRouter();

  // Redux state for website setup (for login image)
  const { websiteSetup } = useSelector((state) => state.websiteSetup);

  // Component state management
  const [fields, setFields] = useState({
    phone: "",
    otp: "",
    newPass: "",
    confirmPassword: "",
  });
  const [resetPass, setResetpass] = useState(false);
  const [forgotUser, setForgotUser] = useState(true);
  const [errors, setErrors] = useState(null);
  const [devOtpCode, setDevOtpCode] = useState("");
  const [imgThumb, setImgThumb] = useState(null);
  const loginImage =
    imgThumb && imgThumb !== IMAGE_FALLBACK
      ? `${appConfig.BASE_URL + imgThumb}`
      : IMAGE_FALLBACK;

  // Effects
  // Set login image from website setup when component mounts or websiteSetup changes
  useEffect(() => {
    if (websiteSetup) {
      setImgThumb(
        websiteSetup.payload?.image_content?.login_image || IMAGE_FALLBACK
      );
    }
  }, [websiteSetup]);

  // Event Handlers
  /**
   * Generic input handler for all form fields
   * Updates the fields state with the new value
   * @param {Event} e - Input change event
   */
  const handleInput = (e) => {
    const { name, value } = e.target;
    setFields((prev) => ({ ...prev, [name]: value }));
  };

  /**
   * Handle forgot password request (send OTP to phone)
   * @Initialization OTP send API
   * @func forgotSuccessHandler @param data @param statusCode
   * @func forgotErrorHandler @param error
   * @func doForgot
   */
  const [sendOtpApi, { isLoading: isForgotLoading }] = useSendOtpApiMutation();
  const [userForgotApi, { isLoading: isEmailForgotLoading }] =
    useUserForgotApiMutation();
  const forgotSuccessHandler = (data, statusCode) => {
    if (statusCode === 200 || statusCode === 201) {
      setResetpass(true);
      setForgotUser(false);
      setErrors(null);
      if (data?.otp_code) {
        setDevOtpCode(data.otp_code);
      }
      toast.success(
        data?.notification || data?.message || "Doğrulama kodu gönderildi.",
        { autoClose: data?.otp_code ? 15000 : 5000 }
      );
    } else {
      toast.error(data?.notification || data?.message);
    }
  };
  const forgotErrorHandler = (error) => {
    toast.error(
      error?.data?.notification ||
        error?.data?.message ||
        "Doğrulama kodu gönderilemedi."
    );
  };
  const doForgot = async () => {
    if (!fields.phone || !fields.phone.trim()) {
      toast.error("Lütfen telefon numaranızı girin.");
      return;
    }
    await sendOtpApi({
      phone: fields.phone.trim(),
      purpose: "password_reset",
      success: forgotSuccessHandler,
      error: forgotErrorHandler,
    });
  };

  /**
   * Handle password reset with OTP verification
   * @Initialization Verify OTP and reset APIs
   * @func resetSuccessHandler @param data @param statusCode
   * @func resetErrorHandler @param error
   * @func doReset
   */

  const [userResetApi, { isLoading: isResetLoading }] =
    useUserResetApiMutation();
  const [verifyOtpApi, { isLoading: isVerifyLoading }] =
    useVerifyOtpApiMutation();
  const [resendOtpApi, { isLoading: isResendLoading }] =
    useResendOtpApiMutation();

  const resetSuccessHandler = (data, statusCode) => {
    if (statusCode === 200 || statusCode === 201) {
      toast.success(data?.notification);
      router.push("login");
    } else {
      toast.error(data?.notification);
    }
  };

  const resetErrorHandler = (error) => {
    if (error?.data?.notification) toast.error(error?.data.notification);
  };

  const doReset = async () => {
    setErrors(null);

    if (!fields.otp || !fields.otp.trim()) {
      toast.error("Lütfen doğrulama kodunu girin.");
      return;
    }
    if (!fields.newPass) {
      toast.error("Lütfen yeni şifrenizi girin.");
      return;
    }
    if (fields.newPass !== fields.confirmPassword) {
      toast.error("Şifreler eşleşmiyor.");
      return;
    }

    await verifyOtpApi({
      phone: fields.phone.trim(),
      otp_code: fields.otp.trim(),
      purpose: "password_reset",
      success: async (data, statusCode) => {
        if (statusCode !== 200 || !data?.token) {
          toast.error(data?.message || "Doğrulama başarısız.");
          return;
        }

        await userResetApi({
          otp: data.token,
          otp_verified_token: data.token,
          phone: fields.phone.trim(),
          password: fields.newPass,
          password_confirmation: fields.confirmPassword,
          success: resetSuccessHandler,
          error: resetErrorHandler,
        });
      },
      error: (error) => {
        setErrors(error);
        toast.error(
          error?.data?.message ||
            error?.data?.notification ||
            "Doğrulama başarısız."
        );
      },
    });
  };

  const doResend = async () => {
    await resendOtpApi({
      phone: fields.phone.trim(),
      purpose: "password_reset",
      success: (data, statusCode) => {
        if (statusCode === 200 || statusCode === 201) {
          toast.success(data?.message || "Doğrulama kodu tekrar gönderildi.");
          return;
        }

        toast.error(data?.message || "Doğrulama kodu tekrar gönderilemedi.");
      },
      error: (error) => {
        toast.error(
          error?.data?.message ||
            error?.data?.notification ||
            "Doğrulama kodu tekrar gönderilemedi."
        );
      },
    });
  };

  // Render Methods
  /**
   * Render the phone input form (Step 1)
   * @returns {JSX.Element} Phone form component
   */
  const renderPhoneForm = () => (
    <div className="w-full">
      {/* Form Title */}
      <div className="title-area flex flex-col justify-center items-center relative text-center mb-7">
        <h2 className="text-[34px] font-bold leading-[74px] text-qblack capitalize">
          {ServeLangItem()?.Forgot_password}
        </h2>
        <div className="shape -mt-6">
          <LineShapeTwo />
        </div>
      </div>

      {/* Form Inputs */}
      <div className="input-area">
        {/* Phone Input Field */}
        <div className="input-item mb-5">
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
                value={String(fields.phone || "").replace(/^\+90/, "")}
                onChange={(e) =>
                  setFields((prev) => ({
                    ...prev,
                    phone: `+90${String(e.target.value || "").replace(/\D/g, "").slice(0, 10)}`,
                  }))
                }
                className="flex-1 h-full bg-transparent text-qblack placeholder:text-qgray px-4 text-sm focus:outline-none"
              />
            </div>
          </div>
        </div>

        {/* Submit Button */}
        <div className="signin-area mb-3.5">
          <div className="flex justify-center">
            <button
              onClick={doForgot}
              type="button"
              disabled={!fields.phone || isForgotLoading || isEmailForgotLoading}
              className="black-btn disabled:bg-opacity-50 disabled:cursor-not-allowed mb-6 text-sm text-white w-full h-[50px] font-semibold flex justify-center bg-purple items-center"
            >
              <span>{ServeLangItem()?.Send}</span>
              {(isForgotLoading || isEmailForgotLoading) && (
                <span className="w-5 " style={{ transform: "scale(0.3)" }}>
                  <LoaderStyleOne />
                </span>
              )}
            </button>
          </div>
        </div>
      </div>
    </div>
  );

  /**
   * Render the password reset form (Step 2)
   * @returns {JSX.Element} Password reset form component
   */
  const renderResetForm = () => (
    <div className="w-full">
      {/* Form Title */}
      <div className="title-area flex flex-col justify-center items-center relative text-center mb-7">
        <h2 className="text-[34px] font-bold leading-[74px] text-qblack">
          {ServeLangItem()?.Reset_Password}
        </h2>
        <div className="shape -mt-6">
          <LineShape />
        </div>
      </div>

      {/* Form Inputs */}
      <div className="input-area">
        {devOtpCode && (
          <div className="mb-4 p-3 bg-yellow-100 border border-yellow-400 rounded text-center">
            <span className="text-sm text-yellow-800 font-bold">
              DEV MODE — OTP Kodu: {devOtpCode}
            </span>
          </div>
        )}
        {/* OTP Input Field */}
        <div className="input-item mb-5">
          <InputCom
            placeholder="* * * * * *"
            label={ServeLangItem()?.OTP + "*"}
            name="otp"
            type="text"
            inputClasses="h-[50px]"
            value={fields.otp}
            error={errors}
            inputHandler={handleInput}
          />
        </div>

        {/* New Password Input Field */}
        <div className="input-item mb-5">
          <InputCom
            placeholder="* * * * * *"
            label={ServeLangItem()?.New_Password + "*"}
            name="newPass"
            type="password"
            inputClasses="h-[50px]"
            value={fields.newPass}
            error={
              errors?.data?.errors &&
              Object.hasOwn(errors.data.errors, "password")
            }
            inputHandler={handleInput}
          />
          {errors?.data?.errors?.password && (
            <span className="text-sm mt-1 text-qred">
              {errors.data.errors.password[0]}
            </span>
          )}
        </div>

        <div className="mb-5 text-sm text-qgraytwo">
          {fields.phone} numarasına doğrulama kodu gönderildi.
        </div>

        {/* Confirm Password Input Field */}
        <div className="input-item mb-5">
          <InputCom
            placeholder="* * * * * *"
            label={ServeLangItem()?.Confirm_Password + "*"}
            name="confirmPassword"
            type="password"
            inputClasses="h-[50px]"
            value={fields.confirmPassword}
            error={
              errors?.data?.errors &&
              Object.hasOwn(errors.data.errors, "password")
            }
            inputHandler={handleInput}
          />
          {errors?.data?.errors?.password && (
            <span className="text-sm mt-1 text-qred">
              {errors.data.errors.password[0]}
            </span>
          )}
        </div>

        {/* Submit Button */}
        <div className="signin-area mb-3.5">
          <div className="flex justify-center">
            <button
              onClick={doReset}
              type="button"
              disabled={
                !(fields.otp && fields.confirmPassword && fields.newPass) ||
                isResetLoading ||
                isVerifyLoading
              }
              className="black-btn disabled:bg-opacity-50 disabled:cursor-not-allowed mb-6 text-sm text-white w-full h-[50px] font-semibold flex justify-center bg-purple items-center"
            >
              <span>{ServeLangItem()?.Reset}</span>
              {(isResetLoading || isVerifyLoading) && (
                <span className="w-5 " style={{ transform: "scale(0.3)" }}>
                  <LoaderStyleOne />
                </span>
              )}
            </button>
          </div>
        </div>

        <div className="flex justify-between items-center text-sm">
          <button
            type="button"
            onClick={() => {
              setForgotUser(true);
              setResetpass(false);
              setErrors(null);
              setFields((prev) => ({
                ...prev,
                otp: "",
                newPass: "",
                confirmPassword: "",
              }));
            }}
            className="text-qgraytwo underline"
          >
            Telefon numarasını değiştir
          </button>

          <button
            type="button"
            onClick={doResend}
            disabled={!fields.phone || isResendLoading}
            className="text-qblack underline disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Kodu tekrar gönder
          </button>
        </div>
      </div>
    </div>
  );

  // Main Component Render
  return (
    <div className="login-page-wrapper w-full py-10">
      <div className="container-x mx-auto">
        <div className="lg:flex items-center relative">
          {/* Main Form Card */}
          <div className="lg:w-[572px] w-full h-[783px] bg-white flex flex-col justify-center sm:p-10 p-5 border border-[#E0E0E0]">
            {forgotUser
              ? renderPhoneForm()
              : resetPass
              ? renderResetForm()
              : ""}
          </div>

          {/* Side Image (Desktop Only) */}
          <div className="flex-1 lg:flex hidden transform scale-60 xl:scale-100 xl:justify-center ">
            <div
              className="absolute ltr:xl:-right-20 ltr:-right-[138px] rtl:-left-20 rtl:-left-[138px]"
              style={{ top: "calc(50% - 258px)" }}
            >
              <Image width={608} height={480} src={loginImage} alt="login" />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
