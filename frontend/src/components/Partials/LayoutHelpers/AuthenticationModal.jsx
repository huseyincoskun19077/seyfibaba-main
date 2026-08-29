"use client";
import LoginWidget from "@/components/Auth/Login/LoginWidget";
import SignupWidget from "@/components/Auth/Signup/SignupWidget";
import VerifyWidget from "@/components/Auth/Signup/VerifyWidget";
import LoginContext from "@/components/Contexts/LoginContext";
import React, { useContext, useState, useEffect } from "react";
import { useDispatch } from "react-redux";

function AuthenticationModal() {
  const dispatch = useDispatch();
  const getLoginContexts = useContext(LoginContext);
  const [popupView, setPopupView] = useState("login");

  //   all actions for modal maintain
  const loginActionPopup = () => {
    setPopupView("signup");
  };
  const notVerifyHandler = () => {
    setPopupView("verify");
  };
  const signupActionPopup = () => {
    setPopupView("login");
  };
  const singupActionHandle = () => {
    setPopupView("verify");
  };
  const verifyActionPopup = () => {
    setPopupView("login");
  };

  //  check if login popup false then show popup
  useEffect(() => {
    if (getLoginContexts.loginPopup === false) {
      setPopupView("login");
    }
  }, [dispatch, getLoginContexts.loginPopup]);
  return (
    <>
      {getLoginContexts.loginPopup && (
        <div
          className={
            "w-full h-screen fixed left-0 top-0 flex justify-center items-center z-40"
          }
        >
          <div className="w-full h-full fixed left-0 top-0 bg-black bg-opacity-50"></div>
          <div
            data-aos="fade-up"
            className={`lg:w-[572px] w-full lg:h-[670px] h-full bg-white flex flex-col justify-center sm:p-10 p-5 border border-[#E0E0E0] relative z-40`}
          >
            <div
              onClick={() => getLoginContexts.handlerPopup(false)}
              className="absolute right-5 top-5 cursor-pointer pointer-events-auto z-50"
            >
              <svg
                viewBox="0 0 54 54"
                fill="none"
                className="cursor-pointer md:w-[54px] md:h-[54px] w-[30px] h-[30px]"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  d="M26.9399 53.9996C12.0678 53.9827 -0.0210736 41.8265 2.75822e-05 26.912C0.0211287 12.0502 12.1965 -0.0320829 27.115 -0.000426215C41.9703 0.0312305 54.0401 12.2148 54 27.1399C53.9599 41.9447 41.7972 54.0186 26.9399 53.9996ZM18.8476 16.4083C17.6765 16.44 16.9844 16.8705 16.6151 17.7189C16.1952 18.6876 16.3893 19.574 17.1363 20.3253C19.0966 22.2901 21.0252 24.2908 23.0425 26.1965C23.7599 26.874 23.6397 27.2201 23.0045 27.83C21.078 29.6788 19.2148 31.5951 17.3241 33.4797C16.9211 33.8807 16.5581 34.3007 16.4505 34.8853C16.269 35.8835 16.6953 36.8332 17.5456 37.3102C18.4382 37.8124 19.5038 37.6626 20.3394 36.8416C22.3673 34.843 24.3866 32.836 26.3723 30.7994C26.8513 30.3077 27.1298 30.2866 27.6193 30.791C29.529 32.7579 31.4851 34.6784 33.4201 36.6179C33.8463 37.0442 34.2831 37.4431 34.9098 37.5486C35.9184 37.7196 36.849 37.2891 37.3196 36.4259C37.7964 35.5543 37.6677 34.5075 36.8912 33.714C34.9731 31.7555 33.0677 29.7801 31.0631 27.9145C30.238 27.1462 30.3688 26.7474 31.1031 26.053C32.9896 24.2655 34.8022 22.3977 36.6338 20.5511C37.7922 19.384 37.8914 17.9827 36.9081 17.0288C35.9501 16.1002 34.5975 16.2141 33.4623 17.3411C31.5188 19.2743 29.5649 21.199 27.6594 23.1659C27.1446 23.6978 26.8492 23.6957 26.3343 23.1659C24.4267 21.1969 22.4664 19.2806 20.5336 17.3369C19.9997 16.7966 19.4258 16.3661 18.8476 16.4083Z"
                  fill="#F34336"
                ></path>
              </svg>
            </div>
            {popupView === "login" ? (
              <LoginWidget
                redirect={false}
                loginActionPopup={loginActionPopup}
                notVerifyHandler={notVerifyHandler}
              />
            ) : popupView === "signup" ? (
              <SignupWidget
                redirect={false}
                signupActionPopup={signupActionPopup}
                changeContent={singupActionHandle}
              />
            ) : popupView === "verify" ? (
              <VerifyWidget
                redirect={false}
                verifyActionPopup={verifyActionPopup}
              />
            ) : (
              ""
            )}
          </div>
        </div>
      )}
    </>
  );
}

export default AuthenticationModal;
