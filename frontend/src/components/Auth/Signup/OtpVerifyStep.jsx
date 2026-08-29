"use client";

import React, { useState, useEffect } from "react";
import ServeLangItem from "../../Helpers/ServeLangItem";
import InputCom from "../../Helpers/InputCom";
import LoaderStyleOne from "../../Helpers/Loaders/LoaderStyleOne";
import { toast } from "react-toastify";
import { useVerifyOtpApiMutation, useResendOtpApiMutation } from "@/redux/features/auth/apiSlice";

/**
 * OtpVerifyStep Component
 * Handles the 6-digit OTP verification for phone numbers during signup.
 */
export default function OtpVerifyStep({
  phone,
  purpose = "register",
  password = "",
  passwordConfirmation = "",
  onVerified,
  onCancel,
  isSubmitting = false,
}) {
  const [otpCode, setOtpCode] = useState("");
  const [timer, setTimer] = useState(60);
  const [canResend, setCanResend] = useState(false);

  // API Mutations
  const [verifyOtp, { isLoading: isVerifying }] = useVerifyOtpApiMutation();
  const [resendOtp, { isLoading: isResending }] = useResendOtpApiMutation();

  // Timer logic for resend cooldown
  useEffect(() => {
    let interval = null;
    if (timer > 0) {
      interval = setInterval(() => {
        setTimer((prev) => prev - 1);
      }, 1000);
    } else {
      setCanResend(true);
      if (interval) clearInterval(interval);
    }
    return () => {
      if (interval) clearInterval(interval);
    };
  }, [timer]);

  /**
   * Handles OTP verification
   */
  const handleVerify = async () => {
    if (otpCode.length !== 6) {
      toast.error(ServeLangItem()?.Invalid_OTP_code || "Lütfen 6 haneli kodu girin");
      return;
    }

    try {
      const result = await verifyOtp({
        phone,
        otp_code: otpCode,
        purpose
      }).unwrap();

      if (result.success && result.verified) {
        toast.success(result.message);
        onVerified(result.token);
      } else {
        toast.error(result.message);
      }
    } catch (error) {
      toast.error(error?.data?.message || "Doğrulama hatası");
    }
  };

  /**
   * Handles OTP resend
   */
  const handleResend = async () => {
    if (!canResend) return;

    try {
      const payload = { phone, purpose };
      if (purpose === "register") {
        payload.password = password;
        payload.password_confirmation = passwordConfirmation;
      }

      const result = await resendOtp(payload).unwrap();

      if (result.success) {
        toast.success(result.message);
        setTimer(60);
        setCanResend(false);
      } else {
        toast.error(result.message);
      }
    } catch (error) {
      toast.error(error?.data?.message || "Lütfen bekleyin");
    }
  };

  return (
    <div className="otp-verify-step animate-in fade-in duration-500">
      <div className="mb-6 text-center">
        <h2 className="text-xl font-bold text-qblack mb-2">
          {ServeLangItem()?.Phone_Verification || "Telefon Doğrulama"}
        </h2>
        <p className="text-sm text-qgray">
          {phone} numarasına bir doğrulama kodu gönderdik.
        </p>
      </div>

      <div className="mb-6">
        <InputCom
          label={ServeLangItem()?.OTP + "*"}
          placeholder="* * * * * *"
          name="otp"
          type="text"
          inputClasses="h-[50px] text-center tracking-[1rem] text-xl font-bold"
          value={otpCode}
          inputHandler={(e) => {
            const val = e.target.value.replace(/[^0-9]/g, "").substring(0, 6);
            setOtpCode(val);
          }}
        />
      </div>

      <div className="flex flex-col space-y-4">
        <button
          onClick={handleVerify}
          disabled={otpCode.length !== 6 || isVerifying || isSubmitting}
          className="w-full h-[50px] black-btn flex justify-center items-center font-semibold disabled:bg-opacity-50"
        >
          <span>{ServeLangItem()?.Verify || "Doğrula"}</span>
          {(isVerifying || isSubmitting) && (
            <span className="ml-2 w-5 scale-50">
              <LoaderStyleOne />
            </span>
          )}
        </button>

        <div className="flex justify-between items-center text-sm px-1">
          <button
            onClick={onCancel}
            type="button"
            className="text-qgray hover:text-qblack transition-colors"
          >
            {ServeLangItem()?.Back || "Geri Dön"}
          </button>

          {canResend ? (
            <button
              onClick={handleResend}
              disabled={isResending}
              type="button"
              className="text-purple font-semibold hover:underline"
            >
              {isResending ? "Gönderiliyor..." : (ServeLangItem()?.Resend_Code || "Tekrar Gönder")}
            </button>
          ) : (
            <span className="text-qgray italic">
              Yeniden gönder: 00:{timer < 10 ? `0${timer}` : timer}
            </span>
          )}
        </div>
      </div>
    </div>
  );
}
