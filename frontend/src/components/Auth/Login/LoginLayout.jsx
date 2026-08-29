import React from "react";

function LoginLayout({ children, scrollable = false }) {
  const panelClassName = scrollable
    ? "lg:w-[572px] w-full bg-white flex flex-col sm:p-10 p-5 pb-6 border border-[#E0E0E0]"
    : "lg:w-[572px] w-full h-[783px] bg-white flex flex-col justify-center sm:p-10 p-5 border border-[#E0E0E0]";

  return (
    <div
      className={
        scrollable
          ? "login-page-wrapper w-full py-6 pb-28 lg:py-10 lg:pb-10"
          : "login-page-wrapper w-full py-10"
      }
    >
      <div className="container-x mx-auto">
        <div className="flex justify-center">
          <div className={panelClassName}>
            {children && children}
          </div>
        </div>
      </div>
    </div>
  );
}

export default LoginLayout;
