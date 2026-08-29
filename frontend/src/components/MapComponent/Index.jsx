"use client";

import { GoogleMap, MarkerF, useLoadScript } from "@react-google-maps/api";
import { useState, useCallback, useEffect } from "react";
import InputCom from "../Helpers/InputCom";

const containerStyle = {
  width: "100%",
  height: "400px",
};

const libraries = ["places"];
const fallbackCenter = { lat: 37.7749, lng: -122.4194 };

function BasicAddressInput({
  searchInputValue,
  searchInputHandler,
  searchInputError = false,
}) {
  return (
    <div>
      <InputCom
        value={searchInputValue}
        inputHandler={(e) => searchInputHandler(e.target.value)}
        label="Adres"
        placeholder="Adresinizi yazin"
        inputClasses="w-full h-[50px]"
        error={searchInputError}
        name="street-address"
        type="text"
        autoComplete="street-address"
      />
      {searchInputError ? (
        <span className="text-sm mt-1 text-qred">{searchInputError}</span>
      ) : (
        ""
      )}
    </div>
  );
}

function InteractiveMap({
  searchEnabled,
  searchInputValue,
  searchInputHandler,
  searchInputError,
  mapKey,
  location,
  locationHandler,
}) {
  const [markerPosition, setMarkerPosition] = useState(null);
  const [mapCenter, setMapCenter] = useState(location ?? fallbackCenter);
  const [locationError, setLocationError] = useState(null);
  const [locationInfo, setLocationInfo] = useState(null);
  const [predictions, setPredictions] = useState([]);
  const [showPredictions, setShowPredictions] = useState(false);
  const [hasUserSelectedLocation, setHasUserSelectedLocation] = useState(
    Boolean(location)
  );

  const { isLoaded, loadError } = useLoadScript({
    googleMapsApiKey: mapKey,
    libraries,
  });

  const getPlaceName = useCallback(
    (lat, lng) => {
      if (!window.google?.maps?.Geocoder) {
        return;
      }

      const geocoder = new window.google.maps.Geocoder();
      geocoder.geocode({ location: { lat, lng } }, (results, status) => {
        if (status === "OK" && results?.[0]) {
          searchInputHandler(results[0].formatted_address);
        }
      });
    },
    [searchInputHandler]
  );

  const applySelectedLocation = useCallback(
    ({ lat, lng, address }) => {
      const nextLocation = { lat, lng };
      setMapCenter(nextLocation);
      setMarkerPosition(nextLocation);
      setHasUserSelectedLocation(true);
      setPredictions([]);
      setShowPredictions(false);
      locationHandler(nextLocation);

      if (address) {
        searchInputHandler(address);
      } else {
        getPlaceName(lat, lng);
      }
    },
    [getPlaceName, locationHandler, searchInputHandler]
  );

  const getUserLocation = useCallback(() => {
    if (hasUserSelectedLocation || !navigator.geolocation) {
      return;
    }

    navigator.geolocation.getCurrentPosition(
      (position) => {
        if (location?.lat && location?.lng) {
          applySelectedLocation({
            lat: Number(location.lat),
            lng: Number(location.lng),
          });
          return;
        }

        applySelectedLocation({
          lat: position.coords.latitude,
          lng: position.coords.longitude,
        });
      },
      (error) => {
        switch (error.code) {
          case error.PERMISSION_DENIED:
            setLocationInfo(
              "Location access was not granted. You can manually select your location on the map below."
            );
            break;
          case error.POSITION_UNAVAILABLE:
            setLocationError(
              "Location information is currently unavailable. You can manually select your location on the map below."
            );
            break;
          case error.TIMEOUT:
            setLocationError(
              "Location request timed out. You can manually select your location on the map below."
            );
            break;
          default:
            setLocationError(
              "Unable to get your location. You can manually select your location on the map below."
            );
            break;
        }

        setMapCenter(location ?? fallbackCenter);
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 300000,
      }
    );
  }, [applySelectedLocation, hasUserSelectedLocation, location]);

  const getPlacePredictions = useCallback((input) => {
    if (!input.trim() || !window.google?.maps?.places?.AutocompleteService) {
      setPredictions([]);
      setShowPredictions(false);
      return;
    }

    const autocompleteService =
      new window.google.maps.places.AutocompleteService();

    autocompleteService.getPlacePredictions(
      {
        input,
        types: ["geocode", "establishment"],
      },
      (results, status) => {
        if (
          status === window.google.maps.places.PlacesServiceStatus.OK &&
          results?.length
        ) {
          setPredictions(results);
          setShowPredictions(true);
          return;
        }

        setPredictions([]);
        setShowPredictions(false);
      }
    );
  }, []);

  const selectPlace = useCallback(
    (prediction) => {
      if (!prediction?.place_id || !window.google?.maps?.places?.PlacesService) {
        return;
      }

      const placesService = new window.google.maps.places.PlacesService(
        document.createElement("div")
      );

      placesService.getDetails(
        {
          placeId: prediction.place_id,
          fields: ["geometry", "formatted_address", "name"],
        },
        (place, status) => {
          if (
            status !== window.google.maps.places.PlacesServiceStatus.OK ||
            !place?.geometry?.location
          ) {
            searchInputHandler(prediction.description ?? searchInputValue);
            setPredictions([]);
            setShowPredictions(false);
            return;
          }

          applySelectedLocation({
            lat: place.geometry.location.lat(),
            lng: place.geometry.location.lng(),
            address: place.formatted_address ?? place.name,
          });
        }
      );
    },
    [applySelectedLocation, searchInputHandler, searchInputValue]
  );

  const onMapClick = useCallback(
    (event) => {
      applySelectedLocation({
        lat: event.latLng.lat(),
        lng: event.latLng.lng(),
      });
    },
    [applySelectedLocation]
  );

  const onMarkerDragEnd = useCallback(
    (event) => {
      applySelectedLocation({
        lat: event.latLng.lat(),
        lng: event.latLng.lng(),
      });
    },
    [applySelectedLocation]
  );

  useEffect(() => {
    if (location?.lat && location?.lng) {
      const nextLocation = {
        lat: Number(location.lat),
        lng: Number(location.lng),
      };
      setMarkerPosition(nextLocation);
      setMapCenter(nextLocation);
      setHasUserSelectedLocation(true);
    }
  }, [location]);

  useEffect(() => {
    if (isLoaded) {
      getUserLocation();
    }
  }, [getUserLocation, isLoaded]);

  if (loadError) {
    return (
      <div>
        <BasicAddressInput
          searchInputValue={searchInputValue}
          searchInputHandler={searchInputHandler}
          searchInputError={searchInputError}
        />
        <div className="mt-3 rounded-md border border-yellow-200 bg-yellow-50 p-3 text-sm text-yellow-800">
          Google Maps yuklenemedi. Adresi manuel olarak girmeye devam edebilirsiniz.
        </div>
      </div>
    );
  }

  if (!isLoaded) {
    return (
      <div>
        <BasicAddressInput
          searchInputValue={searchInputValue}
          searchInputHandler={searchInputHandler}
          searchInputError={searchInputError}
        />
        <div className="mt-3 text-sm text-qgray">Harita yukleniyor...</div>
      </div>
    );
  }

  return (
    <div>
      {locationError && (
        <div className="mb-4 rounded-md border border-yellow-200 bg-yellow-50 p-3">
          <span className="text-sm text-yellow-800">{locationError}</span>
        </div>
      )}

      {locationInfo && (
        <div className="mb-4 rounded-md border border-blue-200 bg-blue-50 p-3">
          <span className="text-sm text-blue-800">{locationInfo}</span>
        </div>
      )}

      {searchEnabled ? (
        <div className="relative">
          <InputCom
            value={searchInputValue}
            inputHandler={(e) => {
              const value = e.target.value;
              searchInputHandler(value);
              getPlacePredictions(value);
            }}
            onFocus={() => {
              if (predictions.length > 0) {
                setShowPredictions(true);
              }
            }}
            onBlur={() => {
              setTimeout(() => setShowPredictions(false), 200);
            }}
            label="Adres"
            placeholder="Adresinizi yazin"
            inputClasses="w-full h-[50px]"
            error={searchInputError}
            name="street-address"
            type="text"
            autoComplete="street-address"
          />
          {searchInputError ? (
            <span className="text-sm mt-1 text-qred">{searchInputError}</span>
          ) : (
            ""
          )}

          {showPredictions && predictions.length > 0 && (
            <div className="absolute z-10 mt-1 max-h-60 w-full overflow-y-auto rounded-md border border-gray-300 bg-white shadow-lg">
              {predictions.map((prediction) => (
                <div
                  key={prediction.place_id}
                  className="cursor-pointer border-b border-gray-200 px-4 py-2 last:border-b-0 hover:bg-gray-100"
                  onClick={() => selectPlace(prediction)}
                >
                  <div className="text-sm text-gray-900">
                    {prediction.structured_formatting?.main_text ??
                      prediction.description}
                  </div>
                  {prediction.structured_formatting?.secondary_text && (
                    <div className="text-xs text-gray-500">
                      {prediction.structured_formatting.secondary_text}
                    </div>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>
      ) : (
        <BasicAddressInput
          searchInputValue={searchInputValue}
          searchInputHandler={searchInputHandler}
          searchInputError={searchInputError}
        />
      )}

      {mapCenter && (
        <GoogleMap
          mapContainerStyle={containerStyle}
          center={mapCenter}
          zoom={12}
          onClick={onMapClick}
        >
          {markerPosition && (
            <MarkerF
              position={markerPosition}
              draggable
              onDragEnd={onMarkerDragEnd}
            />
          )}
        </GoogleMap>
      )}
    </div>
  );
}

export default function MapComponent(props) {
  const isMapEnabled =
    Number(props.mapStatus) === 1 && typeof props.mapKey === "string" && props.mapKey.trim() !== "";

  if (!isMapEnabled) {
    return (
      <BasicAddressInput
        searchInputValue={props.searchInputValue}
        searchInputHandler={props.searchInputHandler}
        searchInputError={props.searchInputError}
      />
    );
  }

  return <InteractiveMap {...props} />;
}
