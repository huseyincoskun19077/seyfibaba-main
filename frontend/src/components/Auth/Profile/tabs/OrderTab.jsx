"use client";
import React from "react";
import DateFormat from "./../../../../utils/DateFormat";
import Link from "next/link";
import ServeLangItem from "../../../Helpers/ServeLangItem";
import CurrencyConvert from "../../../Shared/CurrencyConvert";

export default function OrderTab({ orders }) {
  // Teslim onayı artık ürün/satır bazlıdır; sipariş detayından yapılır.

  return (
    <>
      <div className="relative w-full overflow-x-auto sm:rounded-lg">
        <table className="w-full text-sm text-left text-gray-500 dark:text-gray-400">
          <tbody>
            {/* table heading */}
            <tr className="text-base text-qgray whitespace-nowrap px-2 border-b default-border-bottom ">
              <td className="py-4 block whitespace-nowrap text-center">
                {ServeLangItem()?.Order}
              </td>
              <td className="py-4 whitespace-nowrap text-center">
                {ServeLangItem()?.Date}
              </td>
              <td className="py-4 whitespace-nowrap text-center">
                {ServeLangItem()?.Amount}
              </td>
              <td className="py-4 whitespace-nowrap  text-center">
                {ServeLangItem()?.Action}
              </td>
            </tr>
            {/* table heading end */}
            {orders &&
              orders.length > 0 &&
              orders.map((item, i) => (
                <tr key={i} className="bg-white border-b hover:bg-gray-50">
                  <td className="text-center py-4">
                    <span className="text-lg text-qgray font-medium">
                      #{item.order_id}
                    </span>
                  </td>
                  <td className="text-center py-4 px-2">
                    <span className="text-base text-qgray  whitespace-nowrap">
                      {DateFormat(item.created_at)}
                    </span>
                  </td>
                  <td className="text-center py-4 px-2">
                    <span className="text-base text-qblack whitespace-nowrap px-2 ">
                      <CurrencyConvert price={item.total_amount} />
                    </span>
                  </td>
                  <td className="py-4 flex justify-center">
                    <div className="flex space-x-2 rtl:space-x-reverse items-center">
                      <Link href={`/order/${item.order_id}`}>
                        <div className="w-[116px] h-[46px] bg-qyellow text-qblack font-bold flex justify-center items-center cursor-pointer">
                          <span>{ServeLangItem()?.View_Details}</span>
                        </div>
                      </Link>
                    </div>
                  </td>
                </tr>
              ))}
          </tbody>
        </table>
      </div>
    </>
  );
}
