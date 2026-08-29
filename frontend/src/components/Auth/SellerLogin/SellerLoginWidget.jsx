"use client";

import React, { useState } from "react";
import Link from "next/link";
import { toast } from "react-toastify";
import InputCom from "@/components/Helpers/InputCom";
import LoaderStyleOne from "@/components/Helpers/Loaders/LoaderStyleOne";
import { AUTH_STORAGE_SYNC_EVENT } from "@/redux/api/apiSlice";
import { setAccessTokenCookie } from "@/utils/auth";
import { useUserLoginApiMutation } from "@/redux/features/auth/apiSlice";
import appConfig from "@/appConfig";
import redirectToSellerPanel from "@/utils/sellerSsoRedirect";

function SellerLoginWidget() {
  const [formData, setFormData] = useState({
    login: "",
    phone: "+90",
    password: "",
  });
  const [loginType, setLoginType] = useState("phone");
  const [userLoginApi, { isLoading }] = useUserLoginApiMutation();

  const onChange = (e) => {
    setFormData((prev) => ({ ...prev, [e.target.name]: e.target.value }));
  };

  const handleSuccess = async (data) => {
    if (data?.force_password_change && data?.redirect_url) {
      toast.success(data?.notification || "Giriş başarılı. Yeni şifrenizi oluşturun.");
      window.location.href = data.redirect_url;
      return;
    }

    if (Number(data?.is_vendor) === 1 && data?.access_token) {
      localStorage.removeItem("auth");
      localStorage.setItem("auth", JSON.stringify(data));
      setAccessTokenCookie(data.access_token);
      if (typeof window !== "undefined") {
        window.dispatchEvent(new Event(AUTH_STORAGE_SYNC_EVENT));
      }
      await redirectToSellerPanel(data.access_token);
      return;
    }

    toast.error("Bu hesap bir satıcı hesabı değil. Müşteri girişi için ana giriş sayfasını kullanın.");
  };

  const handleError = (error) => {
    const status = error?.status ?? error?.originalStatus;

    if (status === 429) {
      toast.error("Çok fazla giriş denemesi yaptınız. Lütfen 1 dakika bekleyip tekrar deneyin.");
      return;
    }

    toast.error(
      error?.data?.notification ||
        "Giriş bilgileri hatalı. Telefon/e-posta ve şifrenizi kontrol edin."
    );
  };

  const doLogin = async () => {
    const identifier = loginType === "phone" ? formData.phone : formData.login;

    if (!identifier?.trim() || !formData.password?.trim()) {
      toast.error("E-posta/telefon ve şifre zorunludur.");
      return;
    }

    try {
      const result = await userLoginApi({
        email: identifier,
        password: formData.password,
      }).unwrap();

      handleSuccess(result);
    } catch (error) {
      handleError(error);
    }
  };

  return (
    <div className="w-full">
      <div className="title-area flex flex-col justify-center items-center relative text-center mb-7">
        <h1 className="text-[34px] font-bold leading-[48px] text-qblack">Satıcı Girişi</h1>
        <p className="text-sm text-gray-600 mt-2 max-w-md">
          Telefon numaranız veya e-posta adresiniz ile satıcı paneline giriş yapın.
        </p>
      </div>

      <div className="flex items-center justify-center mb-6">
        <div className="flex items-center bg-gray-100 rounded-full p-1">
          <button
            type="button"
            onClick={() => setLoginType("phone")}
            className={`px-4 py-2 rounded-full text-sm font-medium transition-all duration-300 ${
              loginType === "phone"
                ? "bg-white text-blue-600 shadow-md"
                : "text-gray-500 hover:text-gray-700"
            }`}
          >
            Telefon
          </button>
          <button
            type="button"
            onClick={() => setLoginType("email")}
            className={`px-4 py-2 rounded-full text-sm font-medium transition-all duration-300 ${
              loginType === "email"
                ? "bg-white text-blue-600 shadow-md"
                : "text-gray-500 hover:text-gray-700"
            }`}
          >
            E-posta
          </button>
        </div>
      </div>

      <div className="input-item mb-5">
        {loginType === "phone" ? (
          <InputCom
            placeholder="5XX XXX XX XX"
            label="Telefon"
            name="phone"
            type="tel"
            inputClasses="h-[50px]"
            value={formData.phone}
            inputHandler={onChange}
            onKeyDown={(e) => e.key === "Enter" && doLogin()}
          />
        ) : (
          <InputCom
            placeholder="ornek@email.com"
            label="E-posta"
            name="login"
            type="email"
            inputClasses="h-[50px]"
            value={formData.login}
            inputHandler={onChange}
            onKeyDown={(e) => e.key === "Enter" && doLogin()}
          />
        )}
      </div>

      <div className="input-item mb-7">
        <InputCom
          placeholder="Şifre"
          label="Şifre"
          name="password"
          type="password"
          inputClasses="h-[50px]"
          value={formData.password}
          inputHandler={onChange}
          onKeyDown={(e) => e.key === "Enter" && doLogin()}
        />
      </div>

      <div className="signin-area mb-4">
        <button
          onClick={doLogin}
          type="button"
          disabled={isLoading}
          className="black-btn mb-2 text-sm text-white w-full h-[50px] font-semibold flex justify-center bg-purple items-center"
        >
          <span>Giriş Yap</span>
          {isLoading && (
            <span className="w-5" style={{ transform: "scale(0.3)" }}>
              <LoaderStyleOne />
            </span>
          )}
        </button>
      </div>

      <div className="text-center text-sm text-gray-600">
        <p className="mb-2">Henüz satıcı değil misiniz?</p>
        <Link href="/satici-kayit" className="text-qyellow font-semibold">
          Satıcı olmak için başvurun
        </Link>
        <span className="mx-2">|</span>
        <Link href="/login" className="text-qyellow font-semibold">
          Müşteri girişi
        </Link>
      </div>
    </div>
  );
}

export default SellerLoginWidget;
