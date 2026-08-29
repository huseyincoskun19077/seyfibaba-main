"use client";
import React, { useState } from "react";
import { toast } from "react-toastify";
import { useSendContactMessageApiMutation } from "@/redux/features/contact/apiSlice";
import InputCom from "../Helpers/InputCom";
import ServeLangItem from "../Helpers/ServeLangItem";
import LoaderStyleOne from "../Helpers/Loaders/LoaderStyleOne";

function ContactForm() {
  // State for form data
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    subject: "",
    message: "",
  });
  // State for form errors
  const [errors, setErrors] = useState(null);

  // Form fields configuration
  const formFields = [
    {
      key: "name",
      label: ServeLangItem()?.Name + "*",
      placeholder: ServeLangItem()?.Name,
    },
    {
      key: "email",
      label: ServeLangItem()?.Email_Address + "*",
      placeholder: ServeLangItem()?.Email,
    },
    {
      key: "subject",
      label: "Konu*",
      placeholder: "Konu girin",
    },
  ];

  // Unified input change handler for all fields
  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  /**
   * send contact message functionality
   * @Initialization sendContactMessageApi Api @const sendContactMessage
   * @func sendContactMessageSuccessHandler @const sendContactMessageSuccessHandler @params data, statusCode
   * @func sendContactMessageErrorHandler @const sendContactMessageErrorHandler @params error
   * @func sendHandler
   */

  const [sendContactMessageApi, { isLoading: isSendContactMessageLoading }] =
    useSendContactMessageApiMutation();

  const sendContactMessageSuccessHandler = (data, statusCode) => {
    if (statusCode === 200 || statusCode === 201) {
      setFormData({ name: "", email: "", subject: "", message: "" });
      setErrors(null);
      toast.success(data?.notification);
    }
  };

  const sendContactMessageErrorHandler = (error) => {
    setErrors(error.data?.errors);
    if (error.status === 403) {
      toast.error(error.data.notification);
    } else if (error.status === 422) {
      toast.error(error.data.message);
    } else {
      toast.error(error?.data?.notification || error?.data?.message);
    }
  };

  const sendHandler = async () => {
    await sendContactMessageApi({
      data: formData,
      success: sendContactMessageSuccessHandler,
      error: sendContactMessageErrorHandler,
    });
  };

  return (
    <div className="w-full inputs mt-5">
      {/* Form Fields (name, email, subject) */}
      {formFields.map((field) => (
        <div key={field.key} className="mb-4">
          <InputCom
            label={field.label}
            placeholder={field.placeholder}
            name={field.key}
            inputClasses="h-[50px]"
            value={formData[field.key]}
            inputHandler={handleInputChange}
            error={!!(errors && Object.hasOwn(errors, field.key))}
          />
          {errors && Object.hasOwn(errors, field.key) && (
            <span className="text-sm mt-1 text-qred">
              {errors[field.key][0]}
            </span>
          )}
        </div>
      ))}

      {/* Message Textarea */}
      <div className="mb-5">
        <h6 className="input-label text-qgray capitalize text-[13px] font-normal block mb-2">
          {ServeLangItem()?.Message}*
        </h6>
        <textarea
          name="message"
          value={formData.message}
          onChange={handleInputChange}
          placeholder="Mesajınızı buraya yazın"
          className={`w-full h-[105px] focus:ring-0 focus:outline-none p-3 border placeholder:text-sm ${
            !!(errors && Object.hasOwn(errors, "message"))
              ? "border-qred"
              : "border-qgray-border"
          }`}
        />
        {errors && Object.hasOwn(errors, "message") && (
          <span className="text-sm mt-1 text-qred">{errors.message[0]}</span>
        )}
      </div>

      {/* Submit Button */}
      <div>
        <button
          disabled={isSendContactMessageLoading}
          onClick={sendHandler}
          type="button"
          className="disabled:bg-gray-400 disabled:cursor-not-allowed bg-qblack text-white text-sm font-semibold w-full h-[50px] flex justify-center items-center"
        >
          <span>{ServeLangItem()?.Send_Now}</span>
          {isSendContactMessageLoading && (
            <span className="w-5" style={{ transform: "scale(0.3)" }}>
              <LoaderStyleOne />
            </span>
          )}
        </button>
      </div>
    </div>
  );
}

export default ContactForm;
