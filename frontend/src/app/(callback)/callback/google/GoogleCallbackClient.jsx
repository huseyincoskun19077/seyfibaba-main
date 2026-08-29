"use client";

import React, { Suspense, useEffect, useRef, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { toast } from "react-toastify";
import LoaderStyleOne from "@/components/Helpers/Loaders/LoaderStyleOne";
import ServeLangItem from "@/components/Helpers/ServeLangItem";
import { AUTH_STORAGE_SYNC_EVENT } from "@/redux/api/apiSlice";
import { setAccessTokenCookie } from "@/utils/auth";
import { useLazyGoogleCallbackApiQuery } from "@/redux/features/socialLogin/apiSlice";

function GoogleCallbackContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const started = useRef(false);
  const [loading, setLoading] = useState(true);
  const [googleCallbackApi] = useLazyGoogleCallbackApiQuery();

  useEffect(() => {
    if (started.current) return;
    started.current = true;

    const code = searchParams.get("code");
    if (!code) {
      setLoading(false);
      toast.error(
        ServeLangItem()?.Invalid_callback_parameters ||
          "Geçersiz geri çağrı parametreleri"
      );
      router.replace("/login");
      return;
    }

    const params = new URLSearchParams();
    searchParams.forEach((value, key) => {
      if (value != null && value !== "") params.set(key, value);
    });

    (async () => {
      const response = await googleCallbackApi(params.toString());
      if (response?.status === "fulfilled" && response.data?.access_token) {
        if (typeof window !== "undefined") {
          localStorage.removeItem("auth");
          localStorage.setItem("auth", JSON.stringify(response.data));
          setAccessTokenCookie(response.data.access_token);
          window.dispatchEvent(new Event(AUTH_STORAGE_SYNC_EVENT));
        }
        toast.success(
          ServeLangItem()?.Login_Successfully || "Başarıyla Giriş Yapıldı"
        );
        router.replace("/");
        return;
      }

      setLoading(false);
      toast.error(
        ServeLangItem()?.Login_failed_Please_try_again ||
          "Giriş başarısız. Lütfen tekrar deneyin."
      );
      router.replace("/login");
    })();
  }, [googleCallbackApi, router, searchParams]);

  if (!loading) return null;

  return (
    <div
      className="relative flex h-screen w-full items-center justify-center bg-black bg-opacity-70"
      style={{ zIndex: 999999 }}
    >
      <LoaderStyleOne />
    </div>
  );
}

export default function GoogleCallbackClient() {
  return (
    <Suspense
      fallback={
        <div
          className="relative flex h-screen w-full items-center justify-center bg-black bg-opacity-70"
          style={{ zIndex: 999999 }}
        >
          <LoaderStyleOne />
        </div>
      }
    >
      <GoogleCallbackContent />
    </Suspense>
  );
}
