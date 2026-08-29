// ===================== External Imports =====================
import React, { useEffect, useState } from "react";
import { useDispatch } from "react-redux";
import { toast } from "react-toastify";
import { useRouter } from "next/navigation";

// ===================== Internal Imports =====================
import ServeLangItem from "../../../Helpers/ServeLangItem";
import auth from "../../../../utils/auth";

// ===================== Icon Components =====================
import DashNewOrder from "@/components/Helpers/icons/DashNewOrder";
import DashDeliveryCom from "@/components/Helpers/icons/DashDeliveryCom";
import DashTotalOr from "@/components/Helpers/icons/DashTotalOr";
import LargeDeleteIco from "@/components/Helpers/icons/LargeDeleteIco";
import { useDeleteUserApiMutation } from "@/redux/features/auth/apiSlice";

// ===================== Dashboard Component =====================
export default function Dashboard({ dashBoardData, userLocation }) {
  // --------------------- Hooks & State ---------------------
  const dispatch = useDispatch();
  const location = useRouter();

  // Consolidated state for all form and dialog variables
  const [formData, setFormData] = useState({
    confirmation: false,
    confirmValue: false,
    confirmUser: "",
  });

  // --------------------- Handlers ---------------------
  // Toggle confirmation dialog
  const confirmHandler = () => {
    setFormData((prev) => ({ ...prev, confirmation: !prev.confirmation }));
  };

  /**
   * Handles user delete functionality
   * @Initialization Delete User Api @const deleteUserApi
   * @func deleteUserSuccessHandler @param data @param statusCode
   * @func deleteUser
   */
  const [deleteUserApi, { isLoading: isDeleteUserLoading }] =
    useDeleteUserApiMutation();
  const deleteUserSuccessHandler = async (data, statusCode) => {
    if (statusCode === 200 || statusCode === 201) {
      localStorage.removeItem("auth");
      setFormData((prev) => ({ ...prev, confirmation: !prev.confirmation }));
      location.push("/");
      if (data) {
        toast.success(data.message && data.message);
      }
    }
  };
  const deleteUser = async () => {
    if (auth().user.email === formData.confirmUser) {
      await deleteUserApi({
        token: auth()?.access_token,
        success: deleteUserSuccessHandler,
      });
    }
    return false;
  };

  // --------------------- Effects ---------------------
  // Handle confirmation dialog animation state
  useEffect(() => {
    if (formData.confirmation) {
      setFormData((prev) => ({ ...prev, confirmValue: !prev.confirmValue }));
    } else {
      setFormData((prev) => ({ ...prev, confirmValue: false }));
    }
  }, [formData.confirmation]);

  // --------------------- Render ---------------------
  return (
    <>
      {/* Welcome Message */}
      <div className="welcome-msg w-full">
        <div>
          <p className="text-qblack text-lg">
            {ServeLangItem()?.Hello}, {dashBoardData.personInfo.name}
          </p>
          <h2 className="font-bold text-[24px] text-qblack">
            {ServeLangItem()?.Welcome_to_your_Profile}
          </h2>
        </div>
      </div>

      {/* Quick View Grid */}
      <div className="quick-view-grid w-full lg:flex justify-between lg:space-x-2 xl:space-x-0 items-center mt-3 ">
        {/* New Orders */}
        <div className="qv-item xl:w-[252px] xl:h-[208px] lg:w-1/2 w-full mb-5 xl:mb-0 bg-qblack group hover-bg-qyellow transition-all duration-300 ease-in-out p-6">
          <div className="w-[62px] h-[62px] rounded bg-white flex justify-center items-center">
            <span>
              <DashNewOrder />
            </span>
          </div>
          <p className="text-xl text-white mt-5">
            {ServeLangItem()?.New_Orders}
          </p>
          <span className="text-[40px] text-white  font-bold leading-none mt-1 block">
            {dashBoardData.pendingOrder}
          </span>
        </div>
        {/* Delivery Completed */}
        <div className="qv-item xl:w-[252px] xl:h-[208px] lg:w-1/2 w-full mb-5 xl:mb-0 bg-qblack group hover-bg-qyellow transition-all duration-300 ease-in-out p-6">
          <div className="w-[62px] h-[62px] rounded bg-white flex justify-center items-center">
            <span>
              <DashDeliveryCom />
            </span>
          </div>
          <p className="text-xl text-white  mt-5">
            {ServeLangItem()?.Delivery_Completed}
          </p>
          <span className="text-[40px] text-white  font-bold leading-none mt-1 block">
            {dashBoardData.completeOrder}
          </span>
        </div>
        {/* Total Orders */}
        <div className="qv-item xl:w-[252px] xl:h-[208px] lg:w-1/2 w-full mb-5 xl:mb-0 bg-qblack group hover-bg-qyellow transition-all duration-300 ease-in-out p-6">
          <div className="w-[62px] h-[62px] rounded bg-white flex justify-center items-center">
            <span>
              <DashTotalOr />
            </span>
          </div>
          <p className="text-xl text-white  mt-5">
            {ServeLangItem()?.Total_Orders}
          </p>
          <span className="text-[40px] text-white font-bold leading-none mt-1 block">
            {dashBoardData.totalOrder}
          </span>
        </div>
      </div>

      {/* Personal & Shop Information */}
      <div className="dashboard-info mt-8 mb-8 xl:flex justify-between items-center bg-primarygray xl:p-7 p-3">
        {/* Personal Info */}
        <div className="mb-10 xl:mb-0">
          <p className="title text-[22px] font-semibold">
            {ServeLangItem()?.Personal_Information}
          </p>
          <div className="mt-5">
            <table>
              <tbody>
                <tr className="flex mb-5">
                  <td className="text-base text-qgraytwo w-[100px] block capitalize">
                    <p>{ServeLangItem()?.Name}:</p>
                  </td>
                  <td className="text-base text-qblack font-medium">
                    {dashBoardData.personInfo.name}
                  </td>
                </tr>
                <tr className="flex mb-5">
                  <td className="text-base text-qgraytwo w-[100px] block capitalize">
                    <p>{ServeLangItem()?.Email}:</p>
                  </td>
                  <td className="text-base text-qblack font-medium">
                    {dashBoardData.personInfo.email}
                  </td>
                </tr>
                <tr className="flex mb-5">
                  <td className="text-base text-qgraytwo w-[100px] block capitalize">
                    <p>{ServeLangItem()?.phone}:</p>
                  </td>
                  <td className="text-base text-qblack font-medium">
                    {dashBoardData.personInfo.phone
                      ? dashBoardData.personInfo.phone
                      : ""}
                  </td>
                </tr>
                <tr className="flex mb-5">
                  <td className="text-base text-qgraytwo w-[100px] block capitalize">
                    <p>{ServeLangItem()?.Address}:</p>
                  </td>
                  <td className="text-base text-qblack font-medium">
                    {dashBoardData.personInfo.address ||
                      (userLocation.country &&
                        userLocation.state &&
                        userLocation.city &&
                        `${userLocation.city}, ${userLocation.state}${
                          dashBoardData.personInfo.zip_code
                            ? ` - ${dashBoardData.personInfo.zip_code}`
                            : ""
                        }, ${userLocation.country}`) ||
                      ""}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        {/* Shop Info (if seller) */}
        {dashBoardData.is_seller && (
          <>
            <div className="w-[1px] h-[164px] bg-[#E4E4E4] lg:block hidden"></div>
            <div className="lg:ml-6">
              <p className="title text-[22px] font-semibold">
                {ServeLangItem()?.Shop_Information}
              </p>
              <div className="mt-5">
                <table>
                  <tbody>
                    <tr className="flex mb-5">
                      <td className="text-base text-qgraytwo w-[100px] block capitalize">
                        <p>{ServeLangItem()?.Name}:</p>
                      </td>
                      <td className="text-base text-qblack font-medium">
                        {dashBoardData.sellerInfo.shop_name}
                      </td>
                    </tr>
                    <tr className="flex mb-5">
                      <td className="text-base text-qgraytwo w-[100px] block capitalize">
                        <p>{ServeLangItem()?.Email}:</p>
                      </td>
                      <td className="text-base text-qblack font-medium">
                        {dashBoardData.sellerInfo.email}
                      </td>
                    </tr>
                    <tr className="flex mb-5">
                      <td className="text-base text-qgraytwo w-[100px] block capitalize">
                        <p>{ServeLangItem()?.phone}:</p>
                      </td>
                      <td className="text-base text-qblack font-medium">
                        {dashBoardData.sellerInfo.phone}
                      </td>
                    </tr>
                    <tr className="flex mb-5">
                      <td className="text-base text-qgraytwo w-[100px] block capitalize">
                        <p>{ServeLangItem()?.Address}:</p>
                      </td>
                      <td className="text-base text-qblack font-medium">
                        {dashBoardData.sellerInfo.address}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </>
        )}
      </div>

      {/* Account Delete Section */}
      <div className=" px-7 py-7 border border-[rgba(0, 0, 0, 0.1)] ">
        <h2 className="text-2xl font-bold text-[#cf222e] mb-1">
          {ServeLangItem()?.Delete_Account}
        </h2>
        <p className="text-base text-qgray mb-5">
          {
            ServeLangItem()
              ?.Once_you_delete_your_account__there_is_no_going_back__Please_be_certain_
          }
        </p>
        <button
          onClick={confirmHandler}
          type="button"
          className="py-[5px] px-[16px] border border-[rgba(0, 0, 0, 0.1)] bg-[#f6f8fa] text-[#cf222e] font-semibold tracking-wide text-sm hover:text-white hover:bg-[#cf222e] hover:border-[#cf222e] transition duration-300 ease-in-out"
        >
          {ServeLangItem()?.Delete_Account}
        </button>
      </div>

      {/* Confirmation Dialog */}
      {formData.confirmation && (
        <div className="w-full h-screen fixed left-0 top-0 z-40 flex justify-center items-center">
          {/* Overlay */}
          <div
            onClick={confirmHandler}
            className="w-full h-full fixed left-0 top-0 bg-black bg-opacity-50"
          ></div>
          {/* Dialog Box */}
          <div
            className={`w-[400px] bg-white z-50 p-7 flex justify-center items-center transform transition duration-300 ease-in-out ${
              formData.confirmValue ? "scale-100" : "scale-0"
            }`}
          >
            <div>
              {/* Icon */}
              <div className="flex justify-center mb-10">
                <span className="text-qred">
                  <LargeDeleteIco />
                </span>
              </div>
              {/* Title & Description */}
              <div className="flex justify-center mb-7">
                <div>
                  <h2 className="text-2xl font-medium text-gray-700 mb-5 text-center">
                    {ServeLangItem()?.Are_You_Sure}
                  </h2>
                  <p className="text-sm text-qgraytwo text-center px-2 leading-[24px]">
                    {
                      ServeLangItem()
                        ?.Do_you_really_want_to_delete_these_account_This_process_cannot_be_undone_
                    }
                  </p>
                </div>
              </div>
              {/* Email Confirmation Input */}
              <div className="mb-5">
                <p className="text-sm text-qgraytwo leading-[24px]">
                  Enter the email{" "}
                  <span className="font-semibold text-gray-700">
                    {auth().user.email}
                  </span>{" "}
                  to continue:
                </p>
                <input
                  className="input-field placeholder:text-sm text-sm px-6 text-dark-gray w-full h-[50px] border border-qgray-border font-normal bg-white focus:ring-0 focus:outline-none "
                  type="text"
                  value={formData.confirmUser}
                  onChange={(e) =>
                    setFormData((prev) => ({
                      ...prev,
                      confirmUser: e.target.value.trim(),
                    }))
                  }
                />
              </div>
              {/* Action Buttons */}
              <div className="flex justify-center">
                <div className="flex space-x-5 items-center">
                  <button
                    onClick={confirmHandler}
                    type="button"
                    className="text-qgraytwo text-base font-semibold capitalize"
                  >
                    {ServeLangItem()?.cancel}
                  </button>
                  <button
                    disabled={auth().user.email !== formData.confirmUser}
                    onClick={deleteUser}
                    type="button"
                    className="py-[10px] disabled:bg-opacity-50 disabled:cursor-not-allowed px-[26px]  font-semibold tracking-wide text-base text-white bg-[#cf222e] transition duration-300 ease-in-out"
                  >
                    {ServeLangItem()?.Delete}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
