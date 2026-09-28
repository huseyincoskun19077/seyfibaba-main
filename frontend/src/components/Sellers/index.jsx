"use client";
import Image from "next/image";
import Link from "next/link";
import Star from "../Helpers/icons/Star";
import PageTitle from "../Helpers/PageTitle";
import ServeLangItem from "../Helpers/ServeLangItem";
import appConfig from "@/appConfig";
function Sallers({ sellersData }) {
  return (
    <>
      <div className="sallers-page-wrapper w-full mb-[60px]">
        <PageTitle
          title="Tum Saticilar"
          breadcrumb={[
            { name: ServeLangItem()?.home, path: "/" },
            { name: ServeLangItem()?.Sellers, path: "/sellers" },
          ]}
        />
      </div>

      <div className="content-wrapper w-full mb-[60px]">
        <div className="container-x mx-auto w-full">
          <div className="grid lg:grid-cols-2 grid-cols-1 lg:gap-[30px] gap-5">
            {sellersData &&
              sellersData.length > 0 &&
              sellersData.map((seller, i) => (
                <div key={i} data-aos="fade-up" className="item w-full">
                  <div
                    className="w-full sm:h-[328px] sm:p-[30px] p-5"
                    style={{
                      background: `url(/assets/images/sallers-cover-1.png) no-repeat`,
                      backgroundSize: "cover",
                    }}
                  >
                    <div className="flex sm:flex-row flex-col-reverse sm:items-center justify-between w-full h-full">
                      <div className="flex flex-col justify-between h-full">
                        <div className="">
                          <h2 className="text-[30px] font-semibold text-qblack notranslate">
                            {seller.shop_name}
                          </h2>
                          <div className="flex space-x-2 items-center mb-[30px]">
                            <div className="flex ">
                              {Array.from(
                                Array(parseInt(seller.averageRating)),
                                () => (
                                  <span
                                    key={
                                      parseInt(seller.averageRating) +
                                      Math.random()
                                    }
                                  >
                                    <Star />
                                  </span>
                                )
                              )}
                              {parseInt(seller.averageRating) < 5 && (
                                <>
                                  {Array.from(
                                    Array(5 - parseInt(seller.averageRating)),
                                    () => (
                                      <span
                                        key={
                                          parseInt(seller.averageRating) +
                                          Math.random()
                                        }
                                        className="text-gray-500"
                                      >
                                        <Star defaultValue={false} />
                                      </span>
                                    )
                                  )}
                                </>
                              )}
                            </div>

                            <span className="text-[15px] font-bold text-qblack ">
                              ({parseInt(seller.averageRating)})
                            </span>
                          </div>

                          <div className="saller-text-details">
                            <ul>
                              {seller.address ? (
                              <li className="text-black flex space-x-5 rtl:space-x-reverse items-center leading-9 text-base font-normal">
                                <span>
                                  <svg
                                    width="14"
                                    height="19"
                                    viewBox="0 0 14 19"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                  >
                                    <path
                                      d="M6.97116 2.68819e-05C2.96055 0.0118815 -0.248362 3.57049 0.0150623 7.72998C0.107867 9.19477 0.60259 10.5136 1.45069 11.6909C3.13831 14.0337 4.82379 16.3787 6.5107 18.7214C6.77412 19.0875 7.21745 19.0934 7.47659 18.734C9.17135 16.3816 10.8761 14.0359 12.5566 11.6724C15.2879 7.83075 14.0101 2.65546 9.84454 0.632026C9.03428 0.239342 7.93562 -0.00293677 6.97116 2.68819e-05ZM6.99257 9.29479C5.81395 9.29035 4.85877 8.29975 4.85734 7.08094C4.85592 5.8614 5.80752 4.86931 6.98686 4.86116C8.17762 4.85301 9.14708 5.85769 9.13994 7.09428C9.13351 8.3116 8.16977 9.29924 6.99257 9.29479Z"
                                      fill="black"
                                    />
                                  </svg>
                                </span>
                                <span>{seller.address}</span>
                              </li>
                              ) : null}
                            </ul>
                          </div>
                        </div>

                        <div>
                          <Link
                            href={`/seller/${seller.slug}`}
                          >
                            <div className="w-fit h-[40px] cursor-pointer">
                              <div className="yellow-btn flex justify-center px-7">
                                <div className="flex space-x-2 rtl:space-x-reverse items-center">
                                  <span>{ServeLangItem()?.Shop_Now}</span>
                                  <span>
                                    <svg
                                      className={`transform rtl:rotate-180 fill-current`}
                                      width="7"
                                      height="11"
                                      viewBox="0 0 7 11"
                                      fill="none"
                                      xmlns="http://www.w3.org/2000/svg"
                                    >
                                      <rect
                                        x="1.0918"
                                        y="0.636719"
                                        width="6.94219"
                                        height="1.54271"
                                        transform="rotate(45 1.0918 0.636719)"
                                      />
                                      <rect
                                        x="6.00195"
                                        y="5.54492"
                                        width="6.94219"
                                        height="1.54271"
                                        transform="rotate(135 6.00195 5.54492)"
                                      />
                                    </svg>
                                  </span>
                                </div>
                              </div>
                            </div>
                          </Link>
                        </div>
                      </div>

                      <div>
                        <div className="flex sm:justify-center justify-start">
                          <div className="w-[170px] h-[170px] p-[30px] rounded-full bg-white mb-[20px] flex justify-center items-center relative overflow-hidden">
                            <Image
                              width={170}
                              height={170}
                              className="w-full h-full object-contain"
                              src={`${appConfig.BASE_URL + seller.logo}`}
                              alt={seller.slug || "Seller Logo"}
                            />
                          </div>
                        </div>

                        <h2 className="sm:block hidden text-[30px] font-semibold  text-qblack text-center leading-none">
                          {seller.shop_name}
                        </h2>
                      </div>
                    </div>
                  </div>
                </div>
              ))}
          </div>
        </div>
      </div>
    </>
  );
}
export default Sallers;
