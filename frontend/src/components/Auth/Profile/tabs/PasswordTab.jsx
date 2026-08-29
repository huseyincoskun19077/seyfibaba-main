"use client";
import React, { useState } from "react";
import auth from "./../../../../utils/auth";
import { toast } from "react-toastify";
import { useSelector } from "react-redux";
import Image from "next/image";
import ServeLangItem from "../../../Helpers/ServeLangItem";
import { useRouter } from "next/navigation";
import HidePassIco from "@/components/Helpers/icons/HidePassIco";
import ShowPassIco from "@/components/Helpers/icons/ShowPassIco";
import { useUpdatePasswordApiMutation } from "@/redux/features/auth/apiSlice";
import appConfig from "@/appConfig";

// Reusable password input component
function PasswordInput({ label, id, value, onChange, show, onToggle }) {
  return (
    <div className="input-field mb-6">
      <label
        className="input-label text-qgray text-sm block mb-2.5"
        htmlFor={id}
      >
        {label}
      </label>
      <div className="input-wrapper border border-[#E8E8E8] w-full h-[58px] overflow-hidden relative ">
        <input
          placeholder="* * * * * *"
          className="input-field placeholder:text-base text-bese px-4 text-dark-gray w-full h-full bg-[#FAFAFA] focus:ring-0 focus:outline-none"
          type={show ? "text" : "password"}
          id={id}
          value={value}
          onChange={onChange}
        />
        <button
          type="button"
          className="absolute ltr:right-6 rtl:left-6 bottom-[17px] z-10 cursor-pointer"
          onClick={onToggle}
        >
          {show ? <ShowPassIco /> : <HidePassIco />}
        </button>
      </div>
    </div>
  );
}

export default function PasswordTab() {
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const router = useRouter();

  const [form, setForm] = useState({
    currPass: "",
    nPass: "",
    conPass: "",
  });

  const [showPass, setShowPass] = useState({
    old_password: false,
    new_password: false,
    confirm_password: false,
  });

  // password visibility handler
  const showPassword = (id) => {
    setShowPass((prev) => ({ ...prev, [id]: !prev[id] }));
  };

  /**
   * Update password functionality
   * @Initialization updatePasswordApiMutation Api @const updatePasswordApi
   * @func updatePasswordSuccessHandler @const updatePasswordSuccessHandler @params data, statusCode
   * @func updatePasswordErrorHandler @const updatePasswordErrorHandler @params error
   * @func updatePassword @const updatePassword
   */
  const [updatePasswordApi, { isLoading: isUpdatePasswordLoading }] =
    useUpdatePasswordApiMutation();

  const updatePasswordSuccessHandler = (data, statusCode) => {
    if (statusCode === 200 || statusCode === 201) {
      setForm({ currPass: "", nPass: "", conPass: "" });
      toast.success(data.notification);
    }
  };
  const updatePasswordErrorHandler = (error) => {
    if (error.status === 403) {
      toast.error(error.data.notification);
    } else if (error.status === 422) {
      toast.error(error.data.message);
    } else {
      toast.error(error?.data?.notification || error?.data?.message);
    }
  };
  const updatePassword = async () => {
    const { currPass, nPass, conPass } = form;
    const userToken = auth()?.access_token;
    if (currPass && nPass && conPass) {
      await updatePasswordApi({
        data: {
          current_password: currPass,
          password: nPass,
          password_confirmation: conPass,
        },
        token: userToken,
        success: updatePasswordSuccessHandler,
        error: updatePasswordErrorHandler,
      });
    } else {
      toast.error(ServeLangItem()?.Something_went_wrong);
    }
  };
  return (
    <div className="changePasswordTab w-full">
      <div className="w-full flex lg:flex-row space-x-5 rtl:space-x-reverse lg:items-center">
        {/* Password Form Section */}
        <div className="lg:w-[397px] w-full mb-10">
          {[
            {
              label: ServeLangItem()?.Old_Password + "*",
              id: "old_password",
              valueKey: "currPass",
            },
            {
              label: ServeLangItem()?.Password + "*",
              id: "new_password",
              valueKey: "nPass",
            },
            {
              label: ServeLangItem()?.Re_enter_Password + "*",
              id: "confirm_password",
              valueKey: "conPass",
            },
          ].map((field) => (
            <PasswordInput
              key={field.id}
              label={field.label}
              id={field.id}
              value={form[field.valueKey]}
              onChange={(e) =>
                setForm((prev) => ({
                  ...prev,
                  [field.valueKey]: e.target.value,
                }))
              }
              show={showPass[field.id]}
              onToggle={() => showPassword(field.id)}
            />
          ))}
          {/* Action Buttons */}
          <div className="w-full mt-[30px] flex justify-start">
            <div className="sm:flex sm:space-x-[30px] rtl:space-x-reverse items-center">
              <div className="w-[180px] h-[50px] lg:mb-0 mb-5">
                <button
                  onClick={updatePassword}
                  type="button"
                  className="yellow-btn disabled:cursor-not-allowed"
                  disabled={
                    isUpdatePasswordLoading ||
                    (form.currPass === "" &&
                      form.nPass === "" &&
                      form.conPass === "")
                  }
                >
                  <div className="w-full text-sm font-semibold ">
                    {ServeLangItem()?.Update_Password}
                  </div>
                </button>
              </div>
              <button onClick={() => router.back()} type="button">
                <div className="w-full text-sm font-semibold text-qblack mb-5 sm:mb-0">
                  {ServeLangItem()?.Cancel}
                </div>
              </button>
            </div>
          </div>
        </div>
        {/* Side Image Section */}
        <div className="flex-1 sm:flex hidden justify-end">
          <div className="w-[310px] h-[320px] relative">
            <Image
              layout="fill"
              objectFit="scale-down"
              src={
                appConfig.BASE_URL +
                websiteSetup?.payload?.image_content?.change_password_image
              }
              alt="Thumbnail"
            />
          </div>
        </div>
      </div>
    </div>
  );
}
