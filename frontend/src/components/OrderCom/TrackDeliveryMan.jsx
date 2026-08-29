"use client";
import React, { useEffect, useState } from "react";
import settings from "../../utils/settings";
import MapShow from "../MapComponent/MapShow";
import auth from "@/utils/auth";
import { useLazyLiveTrackOrderApiQuery } from "@/redux/features/order/apiSlice";

function TrackDeliveryMan({ location, deliverymanLocationPoint, orderId }) {
  const webSettings = settings();
  const [deliverymanLocation, setDeliverymanLocation] = useState({
    lat: Number(deliverymanLocationPoint?.lat) || 0,
    lng: Number(deliverymanLocationPoint?.lng) || 0,
  });
  /**
   * Live track functionality for delivery man
   * @Initializatoin liveTrackOrderApi
   * @func liveTrack
   * useEffect for live track after 10 seconds
   */
  const [liveTrackOrderApi, { isLoading: isLiveTrackLoading }] =
    useLazyLiveTrackOrderApiQuery();
  const liveTrack = async () => {
    const res = await liveTrackOrderApi({
      orderId: orderId,
      token: auth()?.access_token,
    });
    const data = res?.data?.data;
    if (res.status === "fulfilled") {
      setDeliverymanLocation({
        lat: Number(data?.deliveryman_latitude),
        lng: Number(data?.deliveryman_longitude),
      });
    }
  };
  useEffect(() => {
    let interval;
    interval = setInterval(() => {
      liveTrack();
    }, 10000);
    return () => clearInterval(interval);
  }, [deliverymanLocation]);

  return (
    <>
      <div className="w-full print:hidden block mt-5">
        <MapShow
          origin={deliverymanLocation}
          destination={{
            lat: Number(location?.lat) || 0,
            lng: Number(location?.lng) || 0,
          }}
          mapKey={webSettings?.map_key}
        />
      </div>
    </>
  );
}

export default TrackDeliveryMan;
