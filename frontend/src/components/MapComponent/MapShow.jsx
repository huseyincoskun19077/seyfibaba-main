"use client";

import {
  DirectionsRenderer,
  GoogleMap,
  MarkerF,
  useLoadScript,
} from "@react-google-maps/api";
import { useEffect, useMemo, useState } from "react";

const containerStyle = {
  width: "100%",
  height: "400px",
};

const libraries = ["places"];

function normalizeCoordinates(point) {
  if (!point) {
    return null;
  }

  const lat = Number(point.lat);
  const lng = Number(point.lng);

  if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
    return null;
  }

  return { lat, lng };
}

export default function MapShow({ mapKey, origin, destination }) {
  const normalizedOrigin = useMemo(() => normalizeCoordinates(origin), [origin]);
  const normalizedDestination = useMemo(
    () => normalizeCoordinates(destination),
    [destination]
  );
  const isMapEnabled =
    typeof mapKey === "string" &&
    mapKey.trim() !== "" &&
    normalizedOrigin &&
    normalizedDestination;

  if (!isMapEnabled) {
    return (
      <div className="rounded-md border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
        Map preview is unavailable for this order.
      </div>
    );
  }

  return (
    <LoadedMapShow
      mapKey={mapKey}
      origin={normalizedOrigin}
      destination={normalizedDestination}
    />
  );
}

function LoadedMapShow({ mapKey, origin, destination }) {
  const { isLoaded, loadError } = useLoadScript({
    googleMapsApiKey: mapKey,
    libraries,
  });

  const [directionsResponse, setDirectionsResponse] = useState(null);
  const [map, setMap] = useState(null);

  useEffect(() => {
    const calculateRoute = async () => {
      if (!isLoaded || !window.google?.maps?.DirectionsService) {
        return;
      }

      try {
        const directionsService = new window.google.maps.DirectionsService();
        const results = await directionsService.route({
          origin,
          destination,
          travelMode: window.google.maps.TravelMode.DRIVING,
        });

        setDirectionsResponse(results);

        if (map) {
          const bounds = new window.google.maps.LatLngBounds();
          bounds.extend(origin);
          bounds.extend(destination);
          map.fitBounds(bounds);
        }
      } catch (error) {
        setDirectionsResponse(null);
      }
    };

    calculateRoute();
  }, [destination, isLoaded, map, origin]);

  if (loadError) {
    return (
      <div className="rounded-md border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
        Teslimat takibi icin Google Maps yuklenemedi.
      </div>
    );
  }

  if (!isLoaded) {
    return <div className="text-sm text-qgray">Harita yukleniyor...</div>;
  }

  return (
    <GoogleMap
      mapContainerStyle={containerStyle}
      center={origin}
      zoom={7}
      onLoad={(mapInstance) => setMap(mapInstance)}
    >
      <MarkerF
        position={origin}
        icon={{
          url: "/assets/images/deliveryman_location_point.svg",
          scaledSize: new window.google.maps.Size(40, 40),
        }}
      />
      <MarkerF
        position={destination}
        icon={{
          url: "/assets/images/user_location_point.png",
          scaledSize: new window.google.maps.Size(40, 40),
        }}
      />

      {directionsResponse && (
        <DirectionsRenderer
          directions={directionsResponse}
          options={{ suppressMarkers: true }}
        />
      )}
    </GoogleMap>
  );
}
