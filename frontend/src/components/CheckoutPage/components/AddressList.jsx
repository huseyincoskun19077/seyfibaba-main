import React from "react";
import AddressDltCheck from "@/components/Helpers/icons/AddressDltCheck";

const AddressList = ({
  // Address data
  addresses,
  activeAddress,
  selectedBilling,
  selectedShipping,
  webSettings,

  // Address handlers
  setBilling,
  shippingHandler,
  deleteAddress,
}) => {
  /**
   * Handle billing address selection
   * @param {number} addressId - Selected address ID
   */
  const handleBillingSelect = (addressId) => {
    setBilling(addressId);
  };

  /**
   * Handle shipping address selection
   * @param {number} addressId - Selected address ID
   * @param {number} cityId - City ID for shipping calculation
   */
  const handleShippingSelect = (addressId, cityId) => {
    shippingHandler(addressId, cityId);
  };

  /**
   * Handle address deletion
   * @param {Event} event - Click event
   * @param {number} addressId - Address ID to delete
   */
  const handleDeleteAddress = (event, addressId) => {
    event.stopPropagation(); // Prevent address selection when clicking delete
    deleteAddress(addressId);
  };

  /**
   * Render address card
   * @param {Object} address - Address object
   * @param {number} index - Address index
   * @returns {JSX.Element} Address card component
   */
  const renderAddressCard = (address, index) => {
    const isSelected =
      activeAddress === "billing"
        ? address.id === selectedBilling
        : address.id === selectedShipping;

    const handleClick = () => {
      if (activeAddress === "billing") {
        handleBillingSelect(address.id);
      } else {
        handleShippingSelect(address.id, parseInt(address.city_id));
      }
    };

    return (
      <div
        onClick={handleClick}
        key={index}
        className={`w-full p-5 border cursor-pointer relative ${
          isSelected
            ? "border-qyellow bg-qyellowlow/10"
            : "border-transparent bg-primarygray"
        }`}
      >
        {/* Address Header */}
        <div className="flex justify-between items-center">
          <p className="title text-[22px] font-semibold">
            Adres #{index + 1}
          </p>
          <button
            onClick={(e) => handleDeleteAddress(e, address.id)}
            type="button"
            className="border border-qgray w-[34px] h-[34px] rounded-full flex justify-center items-center"
          >
            <AddressDltCheck />
          </button>
        </div>

        {/* Address Details */}
        <div className="mt-5">
          <table>
            <tbody>
              {/* Name */}
              <tr className="flex mb-3">
                <td className="text-base text-qgraytwo w-[70px] block line-clamp-1 capitalize">
                  Ad:
                </td>
                <td className="text-base text-qblack line-clamp-1 font-medium">
                  {address.name}
                </td>
              </tr>

              {/* Email */}
              <tr className="flex mb-3">
                <td className="text-base text-qgraytwo w-[70px] block line-clamp-1 capitalize">
                  E-posta:
                </td>
                <td className="text-base text-qblack line-clamp-1 font-medium">
                  {address.email}
                </td>
              </tr>

              {/* Phone */}
              <tr className="flex mb-3">
                <td className="text-base text-qgraytwo w-[70px] block line-clamp-1 capitalize">
                  Telefon:
                </td>
                <td className="text-base text-qblack line-clamp-1 font-medium">
                  {address.phone}
                </td>
              </tr>

              {/* Country */}
              <tr className="flex mb-3">
                <td className="text-base text-qgraytwo w-[70px] block line-clamp-1 capitalize">
                  Ülke:
                </td>
                <td className="text-base text-qblack line-clamp-1 font-medium">
                  {address.country?.name}
                </td>
              </tr>

              {/* State */}
              <tr className="flex mb-3">
                <td className="text-base text-qgraytwo w-[70px] block line-clamp-1 capitalize">
                  İl:
                </td>
                <td className="text-base text-qblack line-clamp-1 font-medium">
                  {address.country_state?.name}
                </td>
              </tr>

              {/* City */}
              <tr className="flex mb-3">
                <td className="text-base text-qgraytwo w-[70px] block line-clamp-1 capitalize">
                  İlçe:
                </td>
                <td className="text-base text-qblack line-clamp-1 font-medium">
                  {address.city?.name}
                </td>
              </tr>

              {/* Location coordinates - Only show if map is enabled */}
              {Number(webSettings?.map_status) === 1 && (
                <>
                  <tr className="flex mb-3">
                    <td className="text-base text-qgraytwo w-[100px] block line-clamp-1 capitalize">
                      Boylam:
                    </td>
                    <td className="text-base text-qblack line-clamp-1 font-medium">
                      {address.longitude}
                    </td>
                  </tr>
                  <tr className="flex mb-3">
                    <td className="text-base text-qgraytwo w-[100px] block line-clamp-1 capitalize">
                      Enlem:
                    </td>
                    <td className="text-base text-qblack line-clamp-1 font-medium">
                      {address.latitude}
                    </td>
                  </tr>
                </>
              )}
            </tbody>
          </table>
        </div>

        {/* Selected indicator */}
        {isSelected && (
          <span className="text-qblack bg-qyellow px-2 text-sm absolute right-2 -top-2 font-medium">
            Seçili
          </span>
        )}
      </div>
    );
  };

  if (!addresses || addresses.length === 0) {
    return (
      <div className="w-full p-4 bg-yellow-50 border border-yellow-200 rounded text-sm text-gray-600">
        <p className="font-semibold mb-1">Kayıtlı adresiniz yok.</p>
        <p>Yukarıdaki <strong>"Yeni Ekle"</strong> butonuyla bir teslimat adresi ekleyin.</p>
      </div>
    );
  }

  return (
    <div className="grid sm:grid-cols-2 grid-cols-1 gap-3">
      {addresses.map((address, i) => renderAddressCard(address, i))}
    </div>
  );
};

export default AddressList;
