"use client";
import LoginLayout from "./LoginLayout";
import { useEffect } from "react";
import { useRouter } from "next/navigation";
import LoginWidget from "./LoginWidget";
import auth from "@/utils/auth";

function Login({ isLayout = true }) {
  const router = useRouter();

  // Zaten giriş yapılmışsa login formunu gösterme
  useEffect(() => {
    if (auth()) {
      router.replace("/profile#dashboard");
    }
  }, [router]);

  if (isLayout) {
    return (
      <LoginLayout>
        <LoginWidget />
      </LoginLayout>
    );
  }

  return <LoginLayout />;
}

export default Login;
