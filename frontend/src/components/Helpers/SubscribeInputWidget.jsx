import { useState } from "react";
import { toast } from "react-toastify";
import ServeLangItem from "./ServeLangItem";
import { useSubscribe } from "@/hooks/useSubscribe";

function SubscribeInputWidget() {
  const [email, setEmail] = useState("");
  const { subscribeRequest, isLoading } = useSubscribe();
  const subscribehandler = async () => {
    if (email) {
      await subscribeRequest(email)
        .unwrap()
        .then((res) => {
          toast.success(res?.message);
          setEmail("");
        })
        .catch((err) => {
          toast.error(err?.data?.message);
        });
    } else {
      toast.error("Lutfen e-posta adresinizi girin");
    }
  };
  return (
    <div
      data-aos="fade-right"
      className="w-full max-w-[543px] h-[54px] flex mt-8"
    >
      <div className="flex-1 bg-white border border-gray-300 ltr:pl-4 rtl:pr-4 flex rtl:space-x-reverse space-x-2 items-center h-full focus-within:text-qyellow focus-within:border-qyellow text-qblack">
        <span>
          <svg
            width="17"
            height="15"
            viewBox="0 0 17 15"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              d="M15 14H2C1.4 14 1 13.6 1 13V2C1 1.4 1.4 1 2 1H15C15.6 1 16 1.4 16 2V13C16 13.6 15.6 14 15 14Z"
              stroke="currentColor"
              strokeMiterlimit="10"
              strokeLinecap="round"
              strokeLinejoin="round"
            />
            <path
              d="M3 4L8.5 8.5L14 4"
              stroke="currentColor"
              strokeMiterlimit="10"
              strokeLinecap="round"
              strokeLinejoin="round"
            />
          </svg>
        </span>
        <input
          type="email"
          name="email"
          className="w-full h-full focus:outline-none text-sm placeholder:text-xs placeholder:text-qblack text-qblack font-400 tracking-wider"
          placeholder="E-POSTA ADRESI"
          onChange={(e) => setEmail(e.target.value.trim())}
          value={email}
        />
      </div>
      <button
        disabled={isLoading}
        onClick={subscribehandler}
        type="button"
        className="sm:w-[158px] w-[100px] shrink-0 h-full bg-qyellow text-sm font-600 disabled:cursor-not-allowed disabled:opacity-60"
      >
        {ServeLangItem()?.Get_the_Coupon}
      </button>
    </div>
  );
}

export default SubscribeInputWidget;
