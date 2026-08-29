import React, { Children } from "react";
import {
  Navigation,
  Pagination,
  Autoplay,
  EffectFade,
} from "swiper/modules";
import { Swiper, SwiperSlide } from "swiper/react";
import "swiper/css";
import "swiper/css/pagination";
import "swiper/css/effect-fade";

function Slider({ children, ...props }) {
  return (
    <Swiper
      {...props}
      modules={[
        Navigation,
        Pagination,
        Autoplay,
        EffectFade,
      ]}
    >
      {Children.map(children, (child) => (
        <SwiperSlide>{child}</SwiperSlide>
      ))}
    </Swiper>
  );
}
export default Slider;
