"use client";

import React from "react";
import LoginLayout from "@/components/Auth/Login/LoginLayout";
import LoginWidget from "@/components/Auth/Login/LoginWidget";

export default function SellerLogin() {
  return (
    <LoginLayout>
      <LoginWidget variant="seller" redirect={false} />
    </LoginLayout>
  );
}
