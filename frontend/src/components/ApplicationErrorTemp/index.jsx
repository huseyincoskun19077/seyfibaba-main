"use client";
import Image from "next/image";
import Link from "next/link";
import ServeLangItem from "../Helpers/ServeLangItem";

export default function ApplicationErrorTemp() {
  return (
    <div className="cart-page-wrapper w-full h-screen flex items-center">
      <div className="container-x mx-auto">
        <div className="empty-card-wrapper w-full">
          <div className="flex justify-center items-center w-full">
            <div>
              <div className="sm:mb-10 mb-0 transform sm:scale-100 scale-50">
                <div className="aspect-[700/417] relative">
                  <Image
                    width={700}
                    height={417}
                    src="/assets/images/server-error.svg"
                    alt="Seyfibaba uygulama hatasi gorseli"
                    className="w-full h-full object-contain"
                  />
                </div>
              </div>
              <div data-aos="fade-up" className="empty-content w-full">
                <div className="flex justify-center w-full">
                  <Link href="/">
                    <div className="flex justify-center w-full">
                      <div className="w-[180px] h-[50px] ">
                        <span type="button" className="yellow-btn">
                          {ServeLangItem()?.Back_to_Shop}
                        </span>
                      </div>
                    </div>
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
