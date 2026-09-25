"use client";

import React, { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import Image from "next/image";
import { toast } from "react-toastify";

// Local imports
import ServeLangItem from "../../Helpers/ServeLangItem";
import InputCom from "../../Helpers/InputCom";
import LoaderStyleOne from "../../Helpers/Loaders/LoaderStyleOne";

// Utilities and data
import settings from "../../../utils/settings";
import {
  useUserSignupApiMutation,
  useSendOtpApiMutation,
  useUserLoginApiMutation,
} from "@/redux/features/auth/apiSlice";
import OtpVerifyStep from "./OtpVerifyStep";
import { AUTH_STORAGE_SYNC_EVENT } from "@/redux/api/apiSlice";
import { setAccessTokenCookie } from "@/utils/auth";
import {
  getConfirmPasswordError,
  getPasswordChecks,
  isPasswordValid,
} from "@/utils/passwordValidation";
import LegalConsentCheckboxes from "@/components/Legal/LegalConsentCheckboxes";
import LegalConsentNotice from "@/components/Legal/LegalConsentNotice";
import {
  SIGNUP_AUTO_ACCEPT_NOTICE,
  SIGNUP_OPTIONAL_CONSENTS,
  SIGNUP_REQUIRED_CONSENTS,
} from "@/config/legalDocuments";
import { recordLegalConsents } from "@/api/recordLegalConsents";
import SocialAuthButtons from "@/components/Auth/SocialAuthButtons";

/**
 * SignupWidget Component
 * Handles user registration with form validation and social login options
 *
 * @param {boolean} redirect - Whether to redirect after successful signup
 * @param {function} signupActionPopup - Function to handle popup signup action
 * @param {function} changeContent - Function to change content after signup
 */
function SignupWidget({ redirect = true, signupActionPopup, changeContent }) {
  const router = useRouter();
  const SIGNUP_CONSENT_STORAGE_KEY = "signup_consents_v1";

  // Form data state - consolidated all input fields
  const [formData, setFormData] = useState({
    fname: "",
    lname: "",
    email: "",
    phone: "+90",
    password: "",
    confirmPassword: "",
  });

  // UI state
  const [consentValues, setConsentValues] = useState({});
  const [errors, setErrors] = useState(null);
  const [currentStep, setCurrentStep] = useState("form"); // steps: 'form', 'otp', 'success'
  const [otpToken, setOtpToken] = useState("");
  const [devOtpCode, setDevOtpCode] = useState("");

  /**
   * Handles input field changes
   * @param {string} field - Field name to update
   * @param {string} value - New value for the field
   */
  const handleInputChange = (field, value) => {
    setFormData((prev) => ({
      ...prev,
      [field]: value,
    }));
  };

  /**
   * Returns password strength level: 0=empty, 1=weak, 2=medium, 3=strong
   */
  const getPasswordStrength = (pw) => {
    if (!pw) return 0;
    if (!isPasswordValid(pw)) return 1;
    const hasSpecial = /[^a-zA-Z0-9]/.test(pw);
    return hasSpecial ? 3 : 2;
  };

  const passwordChecks = getPasswordChecks(formData.password);
  const passwordStrength = getPasswordStrength(formData.password);
  const confirmPasswordError = getConfirmPasswordError(
    formData.password,
    formData.confirmPassword
  );
  const isFormPasswordValid =
    isPasswordValid(formData.password) && !confirmPasswordError;
  const strengthLabels = ["", "Zayıf", "Orta", "Güçlü"];
  const strengthColors = ["", "bg-red-500", "bg-yellow-400", "bg-green-500"];
  const strengthTextColors = ["", "text-red-500", "text-yellow-500", "text-green-600"];

  const handleConsentChange = (key, value) => {
    setConsentValues((prev) => ({ ...prev, [key]: value }));
  };

  const buildLegalConsentsPayload = () => {
    const entries = [];

    // Zorunlu metinler üyelikte otomatik kabul edilir
    SIGNUP_REQUIRED_CONSENTS.forEach((item) => {
      if (item.slug) {
        entries.push({ slug: item.slug, status: true });
      }
    });

    SIGNUP_OPTIONAL_CONSENTS.forEach((item) => {
      const key = item.key || item.slug;
      if (consentValues[key]) {
        entries.push({
          slug: item.slug,
          status: true,
          title: item.linkLabel || "Ticari elektronik ileti onayı",
        });
      }
    });

    return entries;
  };

  const { phone_number_required } = settings();

  // Set +90 as default phone prefix on mount
  useEffect(() => {
    if (!formData.phone || formData.phone === "") {
      handleInputChange("phone", "+90");
    }
  }, []);

  useEffect(() => {
    if (typeof window === "undefined") return;
    try {
      const raw = localStorage.getItem(SIGNUP_CONSENT_STORAGE_KEY);
      if (!raw) return;
      const parsed = JSON.parse(raw);
      if (parsed?.consents && typeof parsed.consents === "object") {
        setConsentValues(parsed.consents);
      }
    } catch {
      // ignore invalid local data
    }
  }, []);

  useEffect(() => {
    if (typeof window === "undefined") return;
    try {
      localStorage.setItem(
        SIGNUP_CONSENT_STORAGE_KEY,
        JSON.stringify({ consents: consentValues })
      );
    } catch {
      // storage may be unavailable
    }
  }, [consentValues]);

  /**
   * Handles user Signup functionality
   * @Initialization Signup Api @const userSignupApi
   * @func signupSuccessHandler @param data @param statusCode
   * @func signupErrorHandler @param error
   * @func doSignup
   */
  const [userSignupApi, { isLoading: isSignupLoading }] =
    useUserSignupApiMutation();
  const [sendOtpApi, { isLoading: isOtpSending }] = useSendOtpApiMutation();
  const [userLoginApi] = useUserLoginApiMutation();

  const persistAutoLogin = (authData) => {
    if (!authData?.access_token) return false;
    localStorage.removeItem("auth");
    localStorage.setItem("auth", JSON.stringify(authData));
    setAccessTokenCookie(authData.access_token);
    if (typeof window !== "undefined") {
      window.dispatchEvent(new Event(AUTH_STORAGE_SYNC_EVENT));
    }
    return true;
  };

  const signupSuccessHandler = (data, statusCode, verifiedToken = "") => {
    if (statusCode === 200) {
      const emailForAutoLogin = String(formData.email || "").trim();
      const phoneForAutoLogin = String(formData.phone || "").trim();
      const passwordForAutoLogin = String(formData.password || "");
      const usedOtp = Boolean(verifiedToken || otpToken);

      setFormData({
        fname: "",
        lname: "",
        email: "",
        phone: "+90",
        password: "",
        confirmPassword: "",
      });

      if (usedOtp) {
        userLoginApi({
          email:
            Number(phone_number_required) === 1 && phoneForAutoLogin
              ? phoneForAutoLogin
              : emailForAutoLogin,
          password: passwordForAutoLogin,
        })
          .unwrap()
          .then((authData) => {
            if (persistAutoLogin(authData)) {
              toast.success("Kayıt başarılı! Oturum açıldı.");
              router.push("/profile#dashboard");
              return;
            }
            toast.success("Kayıt başarılı! Giriş yapabilirsiniz.");
            router.push("/login");
          })
          .catch(() => {
            toast.success("Kayıt başarılı! Giriş yapabilirsiniz.");
            if (redirect) {
              router.push("/login");
            } else if (changeContent) {
              changeContent();
            } else {
              router.push("/login");
            }
          });
      } else {
        toast.success(data.notification);
        if (redirect) {
          router.push(`/verify-you?email=${emailForAutoLogin}`);
        } else {
          if (changeContent) {
            changeContent();
          } else {
            router.push("/login");
          }
        }
      }
    } else {
      toast.error(data.message);
    }
  };

  const signupErrorHandler = (error) => {
    const message =
      error?.data?.message ||
      error?.data?.notification ||
      (error?.data?.errors && Object.values(error.data.errors).flat()[0]) ||
      "Kayıt tamamlanamadı. Lütfen tekrar deneyin.";
    toast.error(message);
    setErrors(error && error.data && error.data.errors);
  };
  const validateSignupForm = () => {
    if (!formData.fname || !formData.lname || !formData.email || !formData.password) {
      toast.error(
        ServeLangItem()?.Please_fill_all_required_fields ||
          "Lütfen tüm zorunlu alanları doldurun"
      );
      return false;
    }

    if (!isPasswordValid(formData.password)) {
      toast.error("Şifre en az 8 karakter ve en az bir harf ile bir rakam içermelidir.");
      return false;
    }

    if (confirmPasswordError) {
      toast.error(
        ServeLangItem()?.Confirm_password_does_not_match || confirmPasswordError
      );
      return false;
    }

    if (Number(phone_number_required) === 1 && !formData.phone) {
      toast.error(
        ServeLangItem()?.Phone_number_is_required || "Telefon numarası zorunludur"
      );
      return false;
    }

    return true;
  };

  /**
   * Triggers the OTP sending process
   */
  const handleSendOtpAndNext = async () => {
    if (!validateSignupForm()) {
      return;
    }

    try {
      const result = await sendOtpApi({
        phone: formData.phone,
        email: formData.email,
        purpose: "register",
        password: formData.password,
        password_confirmation: formData.confirmPassword,
      }).unwrap();

      if (result.success) {
        toast.success(result.message, { autoClose: result.otp_code ? 15000 : 5000 });
        if (result.otp_code) {
          setDevOtpCode(result.otp_code);
        }
        setCurrentStep("otp");
      } else {
        toast.error(result.message);
      }
    } catch (error) {
      toast.error(error?.data?.message || "OTP gönderilirken hata oluştu");
    }
  };

  /**
   * Final registration after OTP verified
   * @param {string} token - The OTP verified token returned from OtpVerifyStep
   */
  const onOtpVerified = async (token) => {
    setOtpToken(token);
    await doSignup(token);
  };

  const doSignup = async (token) => {
    if (!validateSignupForm()) {
      return;
    }

    const verifiedToken = token || otpToken;
    const payload = {
      name: formData.fname + " " + formData.lname,
      email: formData.email,
      password: formData.password,
      password_confirmation: formData.confirmPassword,
      phone: formData.phone ? formData.phone : "",
      otp_verified_token: verifiedToken,
      agree: 1,
      legal_consents: buildLegalConsentsPayload(),
    };

    try {
      const result = await userSignupApi(payload).unwrap();
      try {
        await recordLegalConsents({
          consents: buildLegalConsentsPayload(),
          context: "signup",
        });
      } catch {
        // backend also records on register when authenticated context unavailable pre-login
      }
      signupSuccessHandler(result, 200, verifiedToken);
    } catch (error) {
      signupErrorHandler(error);
    }
  };

  if (currentStep === "otp") {
    return (
      <div className="w-full">
        <div className="mb-6 text-center">
          <p className="text-sm text-[#04334a]/65">
            {ServeLangItem()?.Verify_OTP || "Telefonunuza gelen doğrulama kodunu girin."}
          </p>
        </div>
        <div className="rounded-xl border border-[#04334a]/10 bg-[#F4F6F7] p-5">
          {devOtpCode && (
            <div className="mb-4 rounded-lg border border-[#FCBF49]/50 bg-[#FFF8E8] p-3 text-center">
              <span className="text-sm font-700 text-[#04334a]">
                DEV MODE — OTP Kodu: {devOtpCode}
              </span>
            </div>
          )}
          <OtpVerifyStep
            phone={formData.phone}
            password={formData.password}
            passwordConfirmation={formData.confirmPassword}
            onVerified={onOtpVerified}
            onCancel={() => setCurrentStep("form")}
            isSubmitting={isSignupLoading}
          />
        </div>
      </div>
    );
  }

  return (
    <div className="w-full">
      <div className="mb-6 text-center">
        <p className="text-sm text-[#04334a]/65">
          Bilgilerinizi doldurun; telefon doğrulaması ile hesabınızı açın.
        </p>
      </div>

      <div className="input-area">
        <div className="mb-5 flex w-full flex-col space-y-5 sm:flex-row sm:space-x-5 sm:space-y-0 rtl:space-x-reverse">
          <div className="h-full w-full sm:w-1/2">
            <InputCom
              placeholder="Ad"
              label={ServeLangItem()?.First_Name + "*"}
              name="fname"
              type="text"
              inputClasses="h-[50px] bg-[#F4F6F7] text-[#04334a]"
              labelClasses="font-700 text-[#04334a] text-[13px]"
              value={formData.fname}
              inputHandler={(e) => handleInputChange("fname", e.target.value)}
              error={!!(errors && Object.hasOwn(errors, "name"))}
            />
            {errors && Object.hasOwn(errors, "name") ? (
              <span className="mt-1 text-sm text-qred">{errors.name[0]}</span>
            ) : null}
          </div>

          <div className="h-full w-full sm:w-1/2">
            <InputCom
              placeholder="Soyad"
              label={ServeLangItem()?.Last_Name + "*"}
              name="lname"
              type="text"
              inputClasses="h-[50px] bg-[#F4F6F7] text-[#04334a]"
              labelClasses="font-700 text-[#04334a] text-[13px]"
              value={formData.lname}
              error={!!(errors && Object.hasOwn(errors, "name"))}
              inputHandler={(e) => handleInputChange("lname", e.target.value)}
            />
            {errors && Object.hasOwn(errors, "name") ? (
              <span className="mt-1 text-sm text-qred">{errors.name[0]}</span>
            ) : null}
          </div>
        </div>

        <div className="input-item mb-5">
          <InputCom
            placeholder={ServeLangItem()?.Email}
            label={ServeLangItem()?.Email_Address + "*"}
            name="email"
            type="email"
            inputClasses="h-[50px] bg-[#F4F6F7] text-[#04334a]"
            labelClasses="font-700 text-[#04334a] text-[13px]"
            value={formData.email}
            error={!!(errors && Object.hasOwn(errors, "email"))}
            inputHandler={(e) => handleInputChange("email", e.target.value)}
          />
          {errors && Object.hasOwn(errors, "email") ? (
            <span className="mt-1 text-sm text-qred">{errors.email[0]}</span>
          ) : null}
        </div>

        {Number(phone_number_required) === 1 && (
          <div className="input-item relative mb-5">
            <label className="input-label mb-2 block text-[13px] font-700 capitalize text-[#04334a]">
              {ServeLangItem()?.Phone_Number || "Telefon"}*
            </label>
            <div
              className={`flex h-[50px] items-center overflow-hidden rounded-md bg-[#F4F6F7] focus-within:ring-2 focus-within:ring-[#FCBF49]/60 ${
                errors && Object.hasOwn(errors, "phone")
                  ? "ring-2 ring-qred"
                  : ""
              }`}
            >
              <div className="flex h-full items-center gap-2 border-r border-[#04334a]/10 px-3">
                <Image
                  width="18"
                  height="12"
                  src="/assets/images/countries/TR.svg"
                  alt="Türkiye"
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
                  handleInputChange(
                    "phone",
                    `+90${String(e.target.value || "").replace(/\D/g, "").slice(0, 10)}`
                  )
                }
                className="h-full flex-1 bg-transparent px-4 text-sm text-[#04334a] placeholder:text-qgray focus:outline-none"
              />
            </div>
            {errors && Object.hasOwn(errors, "phone") ? (
              <span className="mt-1 text-sm text-qred">{errors.phone[0]}</span>
            ) : null}
          </div>
        )}

        <div className="mb-5 flex w-full flex-col space-y-5 sm:flex-row sm:space-x-5 sm:space-y-0 rtl:space-x-reverse">
          <div className="h-full w-full sm:w-1/2">
            <InputCom
              placeholder="* * * * * *"
              label={ServeLangItem()?.Password + "*"}
              name="password"
              type="password"
              inputClasses="h-[50px] bg-[#F4F6F7] text-[#04334a]"
              labelClasses="font-700 text-[#04334a] text-[13px]"
              value={formData.password}
              inputHandler={(e) =>
                handleInputChange("password", e.target.value)
              }
              error={!!(errors && Object.hasOwn(errors, "password"))}
            />
            {errors && Object.hasOwn(errors, "password") ? (
              <span className="mt-1 text-sm text-qred">{errors.password[0]}</span>
            ) : null}
            {(formData.password || formData.confirmPassword) && (
              <ul className="mt-2 space-y-1 text-xs">
                <li className={passwordChecks.minLength ? "text-green-600" : "text-qred"}>
                  {passwordChecks.minLength ? "✓" : "•"} En az 8 karakter
                </li>
                <li className={passwordChecks.hasLetter ? "text-green-600" : "text-qred"}>
                  {passwordChecks.hasLetter ? "✓" : "•"} En az bir harf
                </li>
                <li className={passwordChecks.hasNumber ? "text-green-600" : "text-qred"}>
                  {passwordChecks.hasNumber ? "✓" : "•"} En az bir rakam
                </li>
              </ul>
            )}
            {formData.password && (
              <div className="mt-2">
                <div className="mb-1 flex gap-1">
                  {[1, 2, 3].map((level) => (
                    <div
                      key={level}
                      className={`h-1.5 flex-1 rounded-full transition-all duration-300 ${
                        passwordStrength >= level
                          ? strengthColors[passwordStrength]
                          : "bg-[#04334a]/10"
                      }`}
                    />
                  ))}
                </div>
                <span className={`text-xs font-medium ${strengthTextColors[passwordStrength]}`}>
                  {strengthLabels[passwordStrength]}
                  {passwordStrength === 1 && " — En az 8 karakter ve harf+rakam gerekli"}
                </span>
              </div>
            )}
          </div>

          <div className="h-full w-full sm:w-1/2">
            <InputCom
              placeholder="* * * * * *"
              label={ServeLangItem()?.Confirm_Password + "*"}
              name="confirm_password"
              type="password"
              inputClasses="h-[50px] bg-[#F4F6F7] text-[#04334a]"
              labelClasses="font-700 text-[#04334a] text-[13px]"
              value={formData.confirmPassword}
              inputHandler={(e) =>
                handleInputChange("confirmPassword", e.target.value)
              }
              error={
                !!(errors && Object.hasOwn(errors, "password")) ||
                !!confirmPasswordError
              }
            />
            {confirmPasswordError ? (
              <span className="mt-1 text-sm text-qred">{confirmPasswordError}</span>
            ) : errors && Object.hasOwn(errors, "password") ? (
              <span className="mt-1 text-sm text-qred">{errors.password[0]}</span>
            ) : null}
          </div>
        </div>

        <div className="forgot-password-area mb-7 space-y-4">
          <LegalConsentNotice notice={SIGNUP_AUTO_ACCEPT_NOTICE} />
          <LegalConsentCheckboxes
            items={SIGNUP_OPTIONAL_CONSENTS}
            values={consentValues}
            onChange={handleConsentChange}
            required={false}
            title=""
            compact
          />
        </div>

        <div className="signin-area mb-5">
          <div className="flex justify-center">
            {Number(phone_number_required) === 1 ? (
              <button
                onClick={handleSendOtpAndNext}
                type="button"
                disabled={isOtpSending || !isFormPasswordValid}
                className="flex h-[50px] w-full items-center justify-center rounded-lg bg-[#04334a] text-sm font-800 text-white transition hover:bg-[#032736] disabled:cursor-not-allowed disabled:opacity-50"
              >
                <span>{ServeLangItem()?.Get_OTP || "Doğrulama Kodu Al"}</span>
                {isOtpSending && (
                  <span className="ml-2 w-5 scale-50">
                    <LoaderStyleOne />
                  </span>
                )}
              </button>
            ) : (
              <button
                onClick={() => doSignup()}
                type="button"
                disabled={isSignupLoading || !isFormPasswordValid}
                className="flex h-[50px] w-full items-center justify-center rounded-lg bg-[#04334a] text-sm font-800 text-white transition hover:bg-[#032736] disabled:cursor-not-allowed disabled:opacity-50"
              >
                <span>{ServeLangItem()?.Create_Account}</span>
                {isSignupLoading && (
                  <span className="ml-2 w-5 scale-50">
                    <LoaderStyleOne />
                  </span>
                )}
              </button>
            )}
          </div>
        </div>

        <SocialAuthButtons />

        <div className="signup-area flex justify-center">
          <p className="text-sm font-normal text-[#04334a]/60">
            {ServeLangItem()?.Already_have_an_Account}?{" "}
            {redirect ? (
              <Link href="/login">
                <span className="ml-1 cursor-pointer font-700 text-[#04334a] hover:text-qyellow">
                  {ServeLangItem()?.Log_In}
                </span>
              </Link>
            ) : (
              <button onClick={signupActionPopup} type="button">
                <span className="ml-1 cursor-pointer font-700 text-[#04334a] hover:text-qyellow">
                  {ServeLangItem()?.Log_In}
                </span>
              </button>
            )}
          </p>
        </div>
      </div>
    </div>
  );
}

export default SignupWidget;
