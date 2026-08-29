import React from "react";
import Slider from ".";
import Star from "../Helpers/icons/Star";
import appConfig from "@/appConfig";
import Image from "next/image";

function AboutUsSlider({ selector, settings, aboutData }) {
  return (
    <Slider {...settings} className="w-full h-full about-slider  ">
      {aboutData?.testimonials?.length > 0 &&
        aboutData?.testimonials?.map((item, i) => (
          <div
            key={i}
            className="item h-auto bg-primarygray sm:px-10 sm:py-9 p-2"
          >
            <div className="">
              <div className="rating flex space-x-1 rtl:space-x-reverse items-center mb-4">
                {Array.from(Array(parseInt(item.rating)), (_, index) => (
                  <span key={`testimonial-star-filled-${i}-${index}`}>
                    <Star w="20" h="20" />
                  </span>
                ))}
                {parseInt(item.rating) < 5 && (
                  <>
                    {Array.from(Array(5 - parseInt(item.rating)), (_, index) => (
                      <span
                        key={`testimonial-star-empty-${i}-${index}`}
                        className="text-gray-500"
                      >
                        <Star defaultValue={false} w="20" h="20" />
                      </span>
                    ))}
                  </>
                )}
                <div>
                  <span className="text-[13px] text-qblack">
                    ({parseInt(item.rating)})
                  </span>
                </div>
              </div>
              <div className="text-[15px] text-qgraytwo leading-[30px]  line-clamp-6 mb-4">
                {item.comment}
              </div>
              <div className="flex items-center space-x-2.5 rtl:space-x-reverse mt-3">
                <div className="w-[50px] h-[50px] rounded-full overflow-hidden relative">
                  <Image
                    layout="fill"
                    src={`${appConfig.BASE_URL + item.image}`}
                    alt="user"
                  />
                </div>
                <div>
                  <p className="text-[18px] text-qblack font-medium">
                    {item.name}
                  </p>
                  <p className="text-qgraytwo text-[13px]">
                    {item.designation}
                  </p>
                </div>
              </div>
            </div>
          </div>
        ))}
      {aboutData?.testimonials?.length > 0 &&
        aboutData?.testimonials?.map((item, i) => (
          <div
            key={i}
            className="item h-auto bg-primarygray sm:px-10 sm:py-9 p-2"
          >
            <div className="">
              <div className="rating flex space-x-1 rtl:space-x-reverse items-center mb-4">
                {Array.from(Array(parseInt(item.rating)), () => (
                  <span key={Math.random()}>
                    <Star w="20" h="20" />
                  </span>
                ))}
                {parseInt(item.rating) < 5 && (
                  <>
                    {Array.from(Array(5 - parseInt(item.rating)), () => (
                      <span
                        key={parseInt(item.rating) + Math.random()}
                        className="text-gray-500"
                      >
                        <Star defaultValue={false} w="20" h="20" />
                      </span>
                    ))}
                  </>
                )}
                <div>
                  <span className="text-[13px] text-qblack">
                    ({parseInt(item.rating)})
                  </span>
                </div>
              </div>
              <div className="text-[15px] text-qgraytwo leading-[30px]  line-clamp-6 mb-4">
                {item.comment}
              </div>
              <div className="flex items-center space-x-2.5 rtl:space-x-reverse mt-3">
                <div className="w-[50px] h-[50px] rounded-full overflow-hidden relative">
                  <Image
                    layout="fill"
                    src={`${appConfig.BASE_URL + item.image}`}
                    alt="user"
                  />
                </div>
                <div>
                  <p className="text-[18px] text-qblack font-medium">
                    {item.name}
                  </p>
                  <p className="text-qgraytwo text-[13px]">
                    {item.designation}
                  </p>
                </div>
              </div>
            </div>
          </div>
        ))}
    </Slider>
  );
}

export default AboutUsSlider;
