"use client";
import { usePathname } from "next/navigation";
import { useEffect, useState } from "react";
import Image from "next/image";
import { useSelector } from "react-redux";
import VerifyWidget from "./VerifyWidget";
import SignupWidget from "./SignupWidget";
import appConfig from "@/appConfig";

const IMAGE_FALLBACK = "/assets/images/server-error.png";

export default function Signup() {
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const [verify, setVerify] = useState(false);
  const [signupView, setSignupView] = useState(false);
  const [imgThumb, setImgThumb] = useState(null);
  useEffect(() => {
    if (websiteSetup) {
      setImgThumb(
        websiteSetup.payload?.image_content?.login_image || IMAGE_FALLBACK
      );
    }
  }, [websiteSetup]);

  const signupImage =
    imgThumb && imgThumb !== IMAGE_FALLBACK
      ? `${appConfig.BASE_URL + imgThumb}`
      : IMAGE_FALLBACK;

  const pathname = usePathname();
  useEffect(() => {
    if (pathname === "/verify-you") {
      setVerify(true);
    } else {
      setSignupView(true);
    }
  }, [pathname]);

  return (
    <div className="login-page-wrapper w-full py-10">
      <div className="container-x mx-auto">
        <div className="lg:flex items-center relative w-full lg:min-h-[700px]">
          {verify ? (
            <div className="lg:w-[572px] w-full lg:h-[700px] bg-white flex flex-col justify-center sm:p-10 p-5 border border-[#E0E0E0]">
              <VerifyWidget />
            </div>
          ) : signupView ? (
            <div className="lg:w-[572px] w-full lg:h-auto bg-white flex flex-col justify-center sm:p-10 p-5 border border-[#E0E0E0]">
              <SignupWidget />
            </div>
          ) : (
            ""
          )}
          <div className="flex-1 lg:flex hidden transform scale-60 xl:scale-100   xl:justify-center">
            <div
              className="absolute ltr:xl:-right-20 ltr:-right-[138px] rtl:xl:-left-20 rtl:-left-[138px]"
              style={{ top: "calc(50% - 258px)" }}
            >
              <Image width={608} height={480} src={signupImage} alt="login" />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
