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
      <div className="mb-6 text-center">
        <p className="text-sm text-[#04334a]/65">
          {ServeLangItem()?.Verify_You || "Doğrulama kodunu girin"}
        </p>
      </div>
      <div className="input-area">
        <div className="input-item mb-5">
          <InputCom
            placeholder="* * * * * *"
            label={ServeLangItem()?.OTP}
            name="otp"
            type="text"
            inputClasses="h-[50px] bg-[#F4F6F7] text-[#04334a]"
            labelClasses="font-700 text-[#04334a] text-[13px]"
            value={form.otp}
            inputHandler={handleInputChange}
          />
        </div>
        <div className="signin-area mb-3">
          <div className="flex justify-center">
            <button
              disabled={!form.otp || isVerifyLoading}
              onClick={doVerify}
              type="button"
              className="flex h-[50px] w-full items-center justify-center rounded-lg bg-[#04334a] text-sm font-800 text-white transition hover:bg-[#032736] disabled:cursor-not-allowed disabled:opacity-50"
            >
              <span>{ServeLangItem()?.Verify}</span>
              {isVerifyLoading && (
                <span className="w-5" style={{ transform: "scale(0.3)" }}>
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
        <div className="flex min-h-[200px] w-full items-center justify-center">
          <div className="h-8 w-8 animate-spin rounded-full border-b-2 border-t-2 border-qyellow" />
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
