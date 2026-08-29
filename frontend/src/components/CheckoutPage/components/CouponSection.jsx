import React from "react";
import InputCom from "@/components/Helpers/InputCom";

const CouponSection = ({
  // Coupon data
  couponData,
  updateCouponData,
  isApplyCouponLoading,

  // Coupon handlers
  submitCoupon,
}) => {
  /**
   * Handle coupon input change
   * @param {Event} event - Input change event
   */
  const handleCouponInputChange = (event) => {
    updateCouponData("inputCoupon", event.target.value);
  };

  return (
    <div className="mb-10">
      <h2 className="sm:text-2xl text-xl text-qblack font-medium mt-5 mb-5">
        Kupon Uygula
      </h2>
      <div className="discount-code w-full mb-5 sm:mb-0 h-[50px] flex">
        <div className="flex-1 h-full">
          <InputCom
            value={couponData.inputCoupon}
            inputHandler={handleCouponInputChange}
            type="text"
            placeholder="İndirim kodu"
          />
        </div>
        <button
          disabled={isApplyCouponLoading}
          onClick={submitCoupon}
          type="button"
          className="w-[90px] h-[50px] black-btn disabled:cursor-not-allowed"
        >
          <span className="text-sm font-semibold">Uygula</span>
        </button>
      </div>
    </div>
  );
};

export default CouponSection;
