import React, { useState, useEffect, useRef } from "react";
import AddressList from "./AddressList";
import CheckoutAddressForm from "./CheckoutAddressForm";

/**
 * Address Tabs Component
 * Teslimat / fatura aynıysa tek liste; ayrıysa iki sekme.
 */
const AddressTabs = ({
  addresses,
  isAddressLoading = false,
  activeAddress,
  selectedBilling,
  selectedShipping,
  webSettings,
  sameAsShipping = true,
  setSameAsShipping,
  setActiveAddress,
  setBilling,
  shippingHandler,
  deleteAddress,
  onAddressRefresh,
}) => {
  const [showNewAddressForm, setShowNewAddressForm] = useState(false);
  const [editingAddress, setEditingAddress] = useState(null);
  const prevAddressCountRef = useRef(null);

  useEffect(() => {
    if (isAddressLoading) return;
    if (addresses === null || addresses === undefined) return;

    const len = addresses.length;
    const prev = prevAddressCountRef.current;

    if (len > 0) {
      if (prev === null || prev === 0) {
        setShowNewAddressForm(false);
      }
      prevAddressCountRef.current = len;
    } else {
      setShowNewAddressForm(true);
      prevAddressCountRef.current = 0;
    }
  }, [addresses, isAddressLoading]);

  const handleSameAddressChange = (checked) => {
    setSameAsShipping?.(checked);
    if (checked) {
      setActiveAddress("shipping");
      if (selectedShipping) {
        setBilling(selectedShipping);
      }
    } else {
      setActiveAddress("billing");
    }
  };

  const handleTabSwitch = (tab) => {
    if (sameAsShipping && tab === "billing") return;
    setActiveAddress(tab);
  };

  const handleNewAddressToggle = () => {
    setEditingAddress(null);
    setShowNewAddressForm(!showNewAddressForm);
  };

  const handleEditAddress = (address) => {
    setEditingAddress(address);
    setShowNewAddressForm(true);
  };

  const handleAddressSaved = () => {
    setShowNewAddressForm(false);
    setEditingAddress(null);
    if (onAddressRefresh) {
      onAddressRefresh();
    }
  };

  const handleCancelNewAddress = () => {
    setShowNewAddressForm(false);
    setEditingAddress(null);
  };

  if (isAddressLoading && (addresses === null || addresses === undefined)) {
    return (
      <div className="w-full py-10 text-center text-qgray text-sm">
        Adresler yükleniyor...
      </div>
    );
  }

  const listActiveAddress = sameAsShipping ? "shipping" : activeAddress;

  return (
    <>
      {!showNewAddressForm && (
        <div className="addresses-widget w-full">
          <label className="flex items-start gap-3 mb-4 cursor-pointer select-none">
            <input
              type="checkbox"
              className="mt-1 h-4 w-4 accent-qyellow"
              checked={sameAsShipping}
              onChange={(e) => handleSameAddressChange(e.target.checked)}
            />
            <span>
              <span className="block text-sm font-medium text-qblack">
                Fatura adresi teslimat adresi ile aynı
              </span>
              <span className="block text-xs text-qgraytwo mt-0.5">
                Aynıysa bir kez seçin. Farklıysa fatura ve teslimat adresini ayrı seçin.
              </span>
            </span>
          </label>

          <div className="sm:flex justify-between items-center w-full mb-5">
            {!sameAsShipping ? (
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
            ) : (
              <h3 className="text-base font-semibold text-qblack">
                Teslimat / Fatura Adresi
              </h3>
            )}

            <button
              onClick={handleNewAddressToggle}
              type="button"
              className="w-[100px] h-[40px] mt-2 sm:mt-0 border border-qblack hover:bg-qblack hover:text-white transition-all duration-300 ease-in-out"
            >
              <span className="text-sm font-semibold">Yeni Ekle</span>
            </button>
          </div>

          <AddressList
            addresses={addresses}
            activeAddress={listActiveAddress}
            selectedBilling={selectedBilling}
            selectedShipping={selectedShipping}
            webSettings={webSettings}
            setBilling={setBilling}
            shippingHandler={shippingHandler}
            deleteAddress={deleteAddress}
            onEditAddress={handleEditAddress}
          />
        </div>
      )}

      {showNewAddressForm && (
        <CheckoutAddressForm
          key={editingAddress?.id || "new"}
          editingAddress={editingAddress}
          onAddressSaved={handleAddressSaved}
          onCancel={handleCancelNewAddress}
        />
      )}
    </>
  );
};

export default AddressTabs;
