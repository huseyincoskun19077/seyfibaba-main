"use client";
import React, { useState, Suspense } from "react";
import { useRouter, useSearchParams } from "next/navigation";
// Third-party imports
import { toast } from "react-toastify";
// Project imports
import ServeLangItem from "../../Helpers/ServeLangItem";
import InputCom from "../../Helpers/InputCom";
import LoaderStyleOne from "../../Helpers/Loaders/LoaderStyleOne";
import { useLazyUserVerifyApiQuery } from "@/redux/features/auth/apiSlice";

/**
 * Otp shape SVG component for the title decoration
 */
const OtpShape = () => {
  return (
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
};

/**
 * VerifyWidget Component
 * Handles OTP verification for user signup or other verification flows.
 * @param {Object} props
 * @param {boolean} [props.redirect=true] - Whether to redirect to login after verification.
 * @param {Function} [props.verifyActionPopup] - Optional callback for custom action after verification.
 */

function VerifyWidgetContent({ redirect = true, verifyActionPopup }) {
  const searchParams = useSearchParams();
  const [form, setForm] = useState({ otp: "" });
  const router = useRouter();
  /**
   * Handles input changes for the OTP field.
   * @param {Object} e - Event object from input.
   */
  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value.trim() }));
  };

  /**
   * Handles user Otp functionality
   * @Initialization Otp Api @const userVerifyApi
   * @func otpSuccessHandler @param data @param statusCode
   * @func otpErrorHandler @param error
   * @func doVerify
   */

  const [userVerifyApi, { isLoading: isVerifyLoading }] =
    useLazyUserVerifyApiQuery();

  const otpSuccessHandler = (data, statusCode) => {
    if (statusCode === 200 || statusCode === 201) {
      toast.success(data?.notification);
      if (redirect) {
        router.push("/login");
      } else {
        if (verifyActionPopup) {
          verifyActionPopup();
        }
      }
    } else {
      toast.error("Something Went Wrong from Server ");
    }
  };

  const otpErrorHandler = (error) => {
    if (error.status === 400) {
      toast.error(error?.data?.notification);
    } else {
      toast.error("Something Went Wrong! ");
    }
  };

  const doVerify = async () => {
    await userVerifyApi({
      otp: form.otp,
      email: searchParams.get("email"),
      success: otpSuccessHandler,
      error: otpErrorHandler,
    });
  };

  return (
    <div className="w-full">
      {/* Title Section */}
      <div className="title-area flex flex-col justify-center items-center relative text-center mb-7">
        <h2 className="text-[34px] font-bold leading-[74px] text-qblack">
          {ServeLangItem()?.Verify_You}
        </h2>
        <div className="shape -mt-6">
          <OtpShape />
        </div>
      </div>
      {/* Input Section */}
      <div className="input-area">
        <div className="input-item mb-5">
          <InputCom
            placeholder="* * * * * *"
            label={ServeLangItem()?.OTP}
            name="otp"
            type="text"
            inputClasses="h-[50px]"
            value={form.otp}
            inputHandler={handleInputChange}
          />
        </div>
        {/* Verify Button Section */}
        <div className="signin-area mb-3">
          <div className="flex justify-center">
            <button
              disabled={!form.otp || isVerifyLoading}
              onClick={doVerify}
              type="button"
              className="black-btn disabled:bg-opacity-50 disabled:cursor-not-allowed  w-full h-[50px] font-semibold flex justify-center bg-purple items-center"
            >
              <span className="text-sm text-white block">
                {ServeLangItem()?.Verify}
              </span>
              {/* Loader shown while verifying */}
              {isVerifyLoading && (
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
}

function VerifyWidget({ redirect = true, verifyActionPopup }) {
  return (
    <Suspense
      fallback={
        <div className="w-full flex justify-center items-center min-h-[300px]">
          <div className="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-purple-600"></div>
        </div>
      }
    >
      <VerifyWidgetContent
        redirect={redirect}
        verifyActionPopup={verifyActionPopup}
      />
    </Suspense>
  );
}

export default VerifyWidget;
