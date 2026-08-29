import React, { useState, useEffect, useRef } from "react";
import AddressList from "./AddressList";
import CheckoutAddressForm from "./CheckoutAddressForm";

/**
 * Address Tabs Component
 * Handles switching between billing and shipping address selection
 * Uses the same pattern as AddressTab component for address creation
 */
const AddressTabs = ({
  // Address data
  addresses,
  /** RTK Query: adresler yüklenirken true — ilk render'da addresses=null iken yanlışlıkla "yeni adres" açılmasını önler */
  isAddressLoading = false,
  activeAddress,
  selectedBilling,
  selectedShipping,
  webSettings,

  // Address handlers
  setActiveAddress,
  setBilling,
  shippingHandler,
  deleteAddress,

  // Callback for address refresh
  onAddressRefresh,
}) => {
  // İlk render'da addresses henüz null; useState ilk değeri bir daha hesaplanmaz → API geldikten sonra da "yeni adres" açık kalıyordu
  const [showNewAddressForm, setShowNewAddressForm] = useState(false);
  const prevAddressCountRef = useRef(null);

  useEffect(() => {
    if (isAddressLoading) return;
    if (addresses === null || addresses === undefined) return;

    const len = addresses.length;
    const prev = prevAddressCountRef.current;

    if (len > 0) {
      // Sadece ilk yükleme veya boş listeden en az bir adrese geçişte listeyi göster; refetch ile formu kapatma
      if (prev === null || prev === 0) {
        setShowNewAddressForm(false);
      }
      prevAddressCountRef.current = len;
    } else {
      setShowNewAddressForm(true);
      prevAddressCountRef.current = 0;
    }
  }, [addresses, isAddressLoading]);

  /**
   * Handle tab switching
   * @param {string} tab - Tab to switch to (billing or shipping)
   */
  const handleTabSwitch = (tab) => {
    setActiveAddress(tab);
  };

  /**
   * Handle new address toggle
   */
  const handleNewAddressToggle = () => {
    setShowNewAddressForm(!showNewAddressForm);
  };

  /**
   * Handle address saved callback
   */
  const handleAddressSaved = () => {
    setShowNewAddressForm(false);
    if (onAddressRefresh) {
      onAddressRefresh();
    }
  };

  /**
   * Handle cancel new address
   */
  const handleCancelNewAddress = () => {
    setShowNewAddressForm(false);
  };

  if (isAddressLoading && (addresses === null || addresses === undefined)) {
    return (
      <div className="w-full py-10 text-center text-qgray text-sm">
        Adresler yükleniyor...
      </div>
    );
  }

  return (
    <>
      {/* Address Tabs Header */}
      {!showNewAddressForm && (
        <div className="addresses-widget w-full">
          <div className="sm:flex justify-between items-center w-full mb-5">
            <div className="bg-qyellowlow/10 border border-qyellow rounded p-2">
              <button
                onClick={() => handleTabSwitch("billing")}
                type="button"
                className={`px-4 py-3 text-md font-medium rounded-md ${
                  activeAddress === "billing"
                    ? "text-qblack bg-qyellow"
                    : "text-qyellow"
                }`}
              >
                Fatura Adresi
              </button>
              <button
                onClick={() => handleTabSwitch("shipping")}
                type="button"
                className={`px-4 py-3 text-md font-medium rounded-md ml-1 ${
                  activeAddress === "shipping"
                    ? "text-qblack bg-qyellow"
                    : "text-qyellow"
                }`}
              >
                Teslimat Adresi
              </button>
            </div>

            <button
              onClick={handleNewAddressToggle}
              type="button"
              className="w-[100px] h-[40px] mt-2 sm:mt-0 border border-qblack hover:bg-qblack hover:text-white transition-all duration-300 ease-in-out"
            >
              <span className="text-sm font-semibold">
                Yeni Ekle
              </span>
            </button>
          </div>

          {/* Address List */}
          <AddressList
            addresses={addresses}
            activeAddress={activeAddress}
            selectedBilling={selectedBilling}
            selectedShipping={selectedShipping}
            webSettings={webSettings}
            setBilling={setBilling}
            shippingHandler={shippingHandler}
            deleteAddress={deleteAddress}
          />
        </div>
      )}

      {/* New Address Form - Using CheckoutAddressForm component */}
      {showNewAddressForm && (
        <CheckoutAddressForm
          onAddressSaved={handleAddressSaved}
          onCancel={handleCancelNewAddress}
        />
      )}
    </>
  );
};

export default AddressTabs;
