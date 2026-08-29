"use client";
import { useState } from "react";

import LoaderStyleOne from "../Helpers/Loaders/LoaderStyleOne";
import StarRating from "../Helpers/StarRating";
import InputCom from "../Helpers/InputCom";
import auth from "../../utils/auth";
import ServeLangItem from "../Helpers/ServeLangItem";
import CloseFillIco from "../Helpers/icons/CloseFillIco";
import { useReviewActionApiMutation } from "@/redux/features/product/apiSlice";
import { toast } from "react-toastify";

function ReviewModal({ productId, orderId, orderProductId, setReviewModal }) {
  // Star rating value (selected by user)
  const [rating, setRating] = useState(0);
  // Star rating value on hover (for UI feedback)
  const [hover, setHover] = useState(0);
  // Form state for message field only (name is auto-filled from logged-in user)
  const [form, setForm] = useState({ message: "" });
  const [reviewImage, setReviewImage] = useState(null);
  const [imagePreview, setImagePreview] = useState(null);
  
  // Check if user is logged in
  const isLoggedIn = !!auth();

  /**
   * user product review functionality
   * @Initialization useReviewActionApiMutation @const reviewActionApi
   * @func reviewActionSuccessHandler @param data @param statusCode
   * @func reviewActionErrorHandler @param error
   * @func reviewAction
   */
  const [reviewActionApi, { isLoading: isReviewActionLoading }] =
    useReviewActionApiMutation();

  const reviewActionSuccessHandler = (data, statusCode) => {
    if (statusCode === 200 || statusCode === 201) {
      toast.success(data?.message);
      setForm({ message: "" });
      setRating(0);
      setHover(0);
      setReviewModal(false);
    } else {
      toast.error(data?.message);
    }
  };

  const reviewActionErrorHandler = (error) => {
    if (error && error?.status === 422) {
      toast.error("Invalid data");
    } else {
      toast.error(error?.data?.message);
    }
  };

  const reviewAction = async () => {
    const formData = new FormData();
    formData.append("rating", rating);
    formData.append("product_id", productId);
    formData.append("order_id", orderId);
    if (orderProductId) {
      formData.append("order_product_id", orderProductId);
    }
    formData.append("review", form.message);
    if (reviewImage) {
      formData.append("image", reviewImage);
    }
    const userToken = auth()?.access_token;
    await reviewActionApi({
      token: userToken,
      data: formData,
      success: reviewActionSuccessHandler,
      error: reviewActionErrorHandler,
    });
  };

  // Render modal UI
  return (
    <div className="w-full h-full flex fixed left-0 top-0 justify-center z-50 items-center">
      {/* Modal overlay (click to close) */}
      <div
        onClick={() => setReviewModal(false)}
        className="w-full h-full fixed left-0 right-0 bg-black  bg-opacity-25"
      ></div>
      {/* Modal content */}
      <div
        data-aos="fade-up"
        className="sm:w-1/2 w-full bg-white relative py-[40px] px-[38px]"
        style={{ zIndex: "999" }}
      >
        {/* Modal header with title and close icon */}
        <div className="title-bar flex items-center justify-between mb-3">
          <h2 className="text-2xl font-medium text-qblack mb-5">
            {ServeLangItem()?.Write_Your_Reviews}
          </h2>
          <span
            className="cursor-pointer"
            onClick={() => setReviewModal(false)}
          >
            <CloseFillIco />
          </span>
        </div>

        {/* Review form section */}
        <div className="write-review w-full">
          {/* Star rating selector */}
          <div className="flex space-x-1 items-center mb-[30px]">
            <StarRating
              hoverRating={hover}
              hoverHandler={setHover}
              rating={rating}
              ratingHandler={setRating}
            />
            <span className="text-qblack text-[15px] font-normal mt-1">
              ({rating}.0)
            </span>
          </div>

          {/* Review form fields */}
          <div className="w-full review-form ">
            {/* Name input - sadece giriş yapmamış kullanıcılara göster */}
            {!isLoggedIn && (
              <div className=" w-full mb-[30px]">
                <InputCom
                  label="name"
                  placeholder=""
                  type="text"
                  name="name"
                  inputClasses="h-[50px]"
                  value={form.name || ""}
                  inputHandler={(e) =>
                    setForm((prev) => ({ ...prev, name: e.target.value }))
                  }
                />
              </div>
            )}
            {/* Message textarea */}
            <div className="w-full mb-[30px]">
              <h6 className="input-label text-qgray capitalize text-[13px] font-normal block mb-2 ">
                {ServeLangItem()?.Message}*
              </h6>
              <textarea
                value={form.message}
                onChange={(e) =>
                  setForm((prev) => ({ ...prev, message: e.target.value }))
                }
                name=""
                id=""
                cols="30"
                rows="3"
                className="w-full focus:ring-0 border border-qgray-border focus:outline-none p-6 o"
              ></textarea>
            </div>

            {/* Fotoğraf yükleme (#63) */}
            <div className="w-full mb-[30px]">
              <h6 className="input-label text-qgray capitalize text-[13px] font-normal block mb-2">
                Fotoğraf (opsiyonel)
              </h6>
              <input
                type="file"
                accept="image/*"
                onChange={(e) => {
                  const file = e.target.files[0];
                  if (file) {
                    setReviewImage(file);
                    const reader = new FileReader();
                    reader.onload = (ev) => setImagePreview(ev.target.result);
                    reader.readAsDataURL(file);
                  }
                }}
                className="w-full border border-qgray-border p-2 text-sm"
              />
              {imagePreview && (
                <div className="mt-2 relative inline-block">
                  <img src={imagePreview} alt="Önizleme" className="h-20 rounded" />
                  <button
                    type="button"
                    onClick={() => { setReviewImage(null); setImagePreview(null); }}
                    className="absolute -top-2 -right-2 bg-qred text-white rounded-full w-5 h-5 text-xs flex items-center justify-center"
                  >×</button>
                </div>
              )}
            </div>

            {/* Submit button */}
            <div className="flex justify-end">
              <button
                onClick={reviewAction}
                type="button"
                className="black-btn w-[300px] h-[50px]  flex justify-center disabled:cursor-not-allowed"
                disabled={isReviewActionLoading}
              >
                <span className="flex space-x-1 items-center h-full">
                  <span className="text-sm font-semibold">
                    {ServeLangItem()?.Submit_Review}
                  </span>
                  {/* Loader shown while submitting */}
                  {isReviewActionLoading && (
                    <span className="w-5 " style={{ transform: "scale(0.3)" }}>
                      <LoaderStyleOne />
                    </span>
                  )}
                </span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export default ReviewModal;
