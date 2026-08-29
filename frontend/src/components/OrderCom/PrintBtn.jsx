"use client";
import React from "react";
import ServeLangItem from "../Helpers/ServeLangItem";

function PrintBtn() {
  const print = () => {
    window.print();
  };
  return (
    <button
      onClick={print}
      type="button"
      className="w-[161px] h-[52px] rounded flex space-x-2.5 rtl:space-x-reverse items-center justify-center primary-bg print:hidden mt-5 sm:mt-0"
    >
      <span>
        <svg
          width="27"
          height="26"
          viewBox="0 0 27 26"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            d="M24.9 6.10885H22.0364V0.900017C22.0364 0.402996 21.6334 0 21.1364 0H5.86364C5.36662 0 4.96362 0.402943 4.96362 0.900017V6.10885H2.09999C0.942047 6.10885 0 7.05095 0 8.2089V17.2635C0 18.4214 0.942047 19.3635 2.09999 19.3635H4.96378V24.1947C4.96378 24.6917 5.36672 25.0947 5.8638 25.0947H21.1362C21.6332 25.0947 22.0362 24.6918 22.0362 24.1947V19.3635H24.9C26.058 19.3635 27 18.4214 27 17.2635V8.2089C27 7.05101 26.058 6.10885 24.9 6.10885ZM6.76361 1.80004H20.2363V6.10885H6.76361V1.80004ZM20.2362 23.2947H6.76382C6.76382 23.1188 6.76382 16.149 6.76382 15.9315H20.2362C20.2362 16.1545 20.2362 23.1256 20.2362 23.2947ZM21.1364 11.3936H18.8454C18.3484 11.3936 17.9454 10.9907 17.9454 10.4936C17.9454 9.99654 18.3483 9.5936 18.8454 9.5936H21.1364C21.6334 9.5936 22.0364 9.99654 22.0364 10.4936C22.0364 10.9907 21.6334 11.3936 21.1364 11.3936Z"
            fill="#222222"
          />
        </svg>
      </span>
      <span className="text-sm text-qblack">{ServeLangItem()?.Print_PDF}</span>
    </button>
  );
}

export default PrintBtn;
