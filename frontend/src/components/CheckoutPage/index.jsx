"use client";
import { useRouter } from "next/navigation";
import { useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";
import auth from "@/utils/auth";
import PageTitle from "@/components/Helpers/PageTitle";
import ServeLangItem from "@/components/Helpers/ServeLangItem";
import { useCheckoutState } from "./hooks/useCheckoutState";
import {
  calculateTotalPrice,
  calculateDiscountAmount,
  calculateTotalWeight,
  calculateTotalQuantity,
  calculateProductPrice,
  checkProductExistsInFlashSale,
  calculateLocationShippingPrice,
  filterShippingRulesByCity,
  getWebSettings,
} from "./utils/checkoutUtils";
import AddressTabs from "./components/AddressTabs";
import CouponSection from "./components/CouponSection";
import OrderSummary from "./components/OrderSummary";
import PaymentMethods from "./components/PaymentMethods";
import {
  useGetAllUserAddressQueryQuery,
  useDeleteAddressApiMutation,
} from "@/redux/features/locations/apiSlice";
import {
  useLazyGetCheckoutDataApiQuery,
  useLazyGetGuestCheckoutDataApiQuery,
} from "@/redux/features/order/checkout/apiSlice";
import { useLazyApplyCouponApiQuery } from "@/redux/features/order/apiSlice";
import LoaderStyleTwo from "../Helpers/Loaders/LoaderStyleTwo";
import { toast } from "react-toastify";
import usePlaceOrder from "./hooks/usePlaceOrder";
import { isGuestCheckoutEnabled } from "@/utils/guestCheckout";
import useRefreshCartPrices from "@/hooks/useRefreshCartPrices";
import CheckoutAddressForm from "./components/CheckoutAddressForm";
import GuestCheckoutAddressForm from "./components/GuestCheckoutAddressForm";

export default function CheckoutPage() {
  // Redux selectors
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const { cart } = useSelector((state) => state.cart);

  useRefreshCartPrices(cart?.cartProducts, { enabled: Boolean(cart?.cartProducts?.length), notify: true });

  // Router and dispatch
  const router = useRouter();
  const dispatch = useDispatch();

  useEffect(() => {
    if (!auth() && !isGuestCheckoutEnabled()) {
      toast.info("Sipariş vermek için giriş yapmalısınız.");
      router.replace("/login?redirect=/checkout");
    }
  }, [router]);

  // Web settings
  const webSettings = getWebSettings();

  // Custom hook for all checkout state management
  const {
    // Cart and product state
    carts,
    setCarts,
    subTotal,
    setSubTotal,
    mainTotalPrice,
    setMainTotalPrice,
    totalWeight,
    setTotalWeight,
    totalQty,
    setQty,

    // Location and address state
    location,
    setLocation,
    addresses,
    setAddresses,
    activeAddress,
    setActiveAddress,
    selectedShipping,
    setShipping,
    selectedBilling,
    setBilling,

    // Guest fields state
    fName,
    setFName,
    lName,
    setLName,
    email,
    setEmail,
    phone,
    setPhone,
    address,
    setAddress,
    home,
    setHome,
    office,
    setOffice,
    country,
    setCountry,
    countryName,
    setCountryName,
    state,
    setState,
    city,
    setCity,
    guestLocation,
    setGuestLocation,
    errors,
    setErrors,

    // Shipping state
    shippingRules,
    setShipppingRules,
    shippingRulesByCityId,
    setShippingRulesByCityId,
    selectedRule,
    setSelectedRule,
    shippingCharge,
    setShippingCharge,
    locationShippingPrice,
    setLocationShippingPrice,

    // Payment state
    selectPayment,
    setPaymentMethod,

    // Coupon state
    couponData,
    setCouponData,
    updateCouponData,

    // Bank state
    bankInfo,
    setBankInfo,
    transactionInfo,
    setTransactionInfo,
    legalConsentValues,
    setLegalConsentValues,

    // Payment statuses state
    paymentStatuses,
    setPaymentStatuses,
    updatePaymentStatuses,
  } = useCheckoutState();

  // Calculate total price from subtotal
  const totalPrice = calculateTotalPrice(subTotal);

  // Redux RTK Query hooks for address management
  const { data: addressesData, refetch: refetchAddresses, isFetching: isAddressesFetching } =
    useGetAllUserAddressQueryQuery(
      { token: auth()?.access_token },
      { skip: !auth() }
    );

  /**
   * Coupon Functionality
   * @Initialization useLazyApplyCouponApiQuery @const applyCouponApi
   * @func submitCoupon @const submitCoupon
   */

  const [applyCouponApi, { isLoading: isApplyCouponLoading }] =
    useLazyApplyCouponApiQuery();

  const submitCoupon = async () => {
    if (!auth()) {
      toast.error("Kupon kullanmak için önce giriş yapmalısınız");
      return false;
    }

    const userToken = auth()?.access_token;
    const res = await applyCouponApi({
      token: userToken,
      coupon: couponData.inputCoupon,
    });

    if (res.status === "fulfilled") {
      updateCouponData("inputCoupon", "");
      if (res.data) {
        if (totalPrice >= parseInt(res.data.coupon.min_purchase_price)) {
          updateCouponData("couponCode", res.data.coupon);
          localStorage.setItem(
            "coupon",
            JSON.stringify(res.data && res.data.coupon)
          );
          let currDate = new Date().toLocaleDateString();
          localStorage.setItem("coupon_set_date", currDate);
          toast.success(ServeLangItem()?.Coupon_Applied);
        } else {
          toast.error("Toplam tutarınız bu kuponu uygulamak için yeterli değil");
        }
      }
    } else if (res.status === "rejected") {
      toast.error(res?.error?.data?.message || "Kupon uygulanırken bir hata oluştu.");
    } else {
      toast.error("Bir şeyler ters gitti");
    }
  };

  /**
   * Calculate product price with variants and offers
   * @param {Object} item - Cart item
   * @returns {number} Calculated price
   */
  const price = (item) => {
    return calculateProductPrice(item);
  };

  /**
   * Handle shipping selection and calculation
   * @param {number} addressId - Selected address ID
   * @param {number} cityId - City ID for shipping rules
   */
  const shippingHandler = (addressId, cityId) => {
    setSelectedRule(null);
    setShippingCharge(null);

    const autoSelectBestRule = (rules) => {
      if (!rules || rules.length === 0) return;
      // Find the best matching rule: prefer the cheapest one that matches current totalPrice
      const currentTotal = calculateTotalPrice(subTotal) || 0;
      const matchingRules = rules.filter((rule) => {
        if (rule.type === "base_on_price") {
          const from = parseInt(rule.condition_from);
          const to = parseInt(rule.condition_to);
          return from <= currentTotal && (to >= currentTotal || to === -1);
        }
        return true;
      });
      // Sort by fee ascending — cheapest (free) first
      const sorted = matchingRules.length > 0
        ? [...matchingRules].sort((a, b) => parseFloat(a.shipping_fee) - parseFloat(b.shipping_fee))
        : rules;
      setSelectedRule(sorted[0].id.toString());
      setShippingCharge(sorted[0].shipping_fee);
    };

    if (auth() && addressId) {
      setShipping(addressId);

      if (Number(webSettings?.map_status) === 1) {
        const findAddress = addresses?.find(
          (f) => parseInt(f.id) === addressId
        );
        const calcPrice = findAddress
          ? calculateLocationShippingPrice(findAddress)
          : null;
        setLocationShippingPrice(calcPrice);
        setShippingRulesByCityId([]);
      } else {
        setLocationShippingPrice(null);
        const filteredRules = filterShippingRulesByCity(shippingRules, cityId);
        setShippingRulesByCityId(filteredRules);
        autoSelectBestRule(filteredRules);
      }
    } else {
      setLocationShippingPrice(null);
      const filteredRules = filterShippingRulesByCity(shippingRules, cityId);
      setShippingRulesByCityId(filteredRules);
      autoSelectBestRule(filteredRules);
    }
  };

  /**
   * Handle shipping rule selection
   * @param {Event} e - Radio button change event
   * @param {number} price - Shipping price
   */
  const selectedRuleHandler = (e, price) => {
    setSelectedRule(e.target.value);
    setShippingCharge(price);
  };

  /**
   * Delete address functionality
   * @Initialization deleteAddressApiMutation Api @const deleteAddressApi
   * @func deleteAddressSuccessHandler @const deleteAddressSuccessHandler @params data
   * @func deleteAddressErrorHandler @const deleteAddressErrorHandler @params error
   * @func deleteAddress @const deleteAddress @params id
   */

  const [deleteAddressApi, { isLoading: isDeleteAddressLoading }] =
    useDeleteAddressApiMutation();

  const deleteAddressSuccessHandler = (data) => {
    toast.success(data?.notification, {
      autoClose: 1000,
    });
  };

  const deleteAddressErrorHandler = (error) => {
    toast.error((error?.data?.notification || error?.data?.message) || "Adres silinemedi. Lütfen tekrar deneyin.", {
      autoClose: 1000,
    });
  };

  const deleteAddress = async (id) => {
    const userToken = auth()?.access_token;
    await deleteAddressApi({
      id: id,
      token: userToken,
      success: deleteAddressSuccessHandler,
      error: deleteAddressErrorHandler,
    });
  };

  /**
   * Place order handler
   * Handles all payment methods and order placement
   */
  const { placeOrderHandler } = usePlaceOrder({
    carts,
    addresses,
    webSettings,
    selectedBilling,
    selectedShipping,
    selectedRule,
    couponCode: couponData.couponCode,
    selectPayment,
    transactionInfo,
    legalConsentValues,
    guestFields: {
      fName,
      lName,
      email,
      phone,
      address,
      home,
      office,
      country,
      countryName,
      state,
      city,
      location: guestLocation,
      errors,
      setErrors,
    },
    setNewAddress: () => setActiveAddress("shipping"),
    ServeLangItem,
  });

  /**
   * Handle address refresh after new address is saved
   */
  const handleAddressRefresh = () => {
    refetchAddresses();
  };

  // Update addresses when addressesData changes
  useEffect(() => {
    if (addressesData?.addresses) {
      const checkoutAddresses = addressesData.addresses;
      setAddresses(checkoutAddresses);

      if (checkoutAddresses.length > 0) {
        const selectedShippingAddress =
          checkoutAddresses.find(
            (item) => parseInt(item.id) === parseInt(selectedShipping)
          ) || checkoutAddresses[0];

        const selectedBillingAddress =
          checkoutAddresses.find(
            (item) => parseInt(item.id) === parseInt(selectedBilling)
          ) || checkoutAddresses[0];

        if (
          !selectedShipping ||
          !checkoutAddresses.some(
            (item) => parseInt(item.id) === parseInt(selectedShipping)
          )
        ) {
          setShipping(selectedShippingAddress.id);
        }

        if (
          !selectedBilling ||
          !checkoutAddresses.some(
            (item) => parseInt(item.id) === parseInt(selectedBilling)
          )
        ) {
          setBilling(selectedBillingAddress.id);
        }

        if (Number(webSettings?.map_status) === 1) {
          const calcPrice = calculateLocationShippingPrice(
            selectedShippingAddress
          );
          setLocationShippingPrice(calcPrice);
        } else if (!selectedRule && shippingRules?.length > 0) {
          // Shipping rule not yet selected (e.g. address just added) — auto-select now
          const cityId = parseInt(selectedShippingAddress.city_id);
          const filteredRules = filterShippingRulesByCity(shippingRules, cityId);
          setShippingRulesByCityId(filteredRules);
          if (filteredRules.length > 0) {
            const cartTotal = cart?.cartProducts?.reduce((sum, item) => {
              const basePrice = item.product?.offer_price || item.product?.price || 0;
              return sum + parseFloat(basePrice) * parseInt(item.qty);
            }, 0) || 0;
            const matching = filteredRules.filter((r) => {
              if (r.type !== "base_on_price") return true;
              const from = parseInt(r.condition_from);
              const to = parseInt(r.condition_to);
              return from <= cartTotal && (to >= cartTotal || to === -1);
            });
            const best = (matching.length > 0 ? matching : filteredRules)
              .sort((a, b) => parseFloat(a.shipping_fee) - parseFloat(b.shipping_fee))[0];
            setSelectedRule(best.id.toString());
            setShippingCharge(best.shipping_fee);
          }
        }
      } else {
        setShipping(null);
        setBilling(null);
        setSelectedRule(null);
        setShippingCharge(null);
        setLocationShippingPrice(null);
        setShippingRulesByCityId([]);
      }
    }
  }, [addressesData, webSettings, selectedShipping, selectedBilling, shippingRules, selectedRule]);

  // Update cart data when cart changes
  useEffect(() => {
    setCarts(cart && cart.cartProducts);

    // Calculate total weight
    const totalWeight = calculateTotalWeight(cart?.cartProducts);
    setTotalWeight(totalWeight);

    // Calculate total quantity
    const totalQuantity = calculateTotalQuantity(cart?.cartProducts);
    setQty(totalQuantity);
  }, [cart]);

  // Calculate subtotal when cart changes
  useEffect(() => {
    if (carts && carts.length > 0) {
      const calculatedSubTotal = carts.map((v) => {
        let prices = [];
        v.variants.map(
          (item) =>
            item.variant_item &&
            prices.push(parseFloat(item.variant_item.price))
        );
        const sumCal = prices.length > 0 && prices.reduce((p, c) => p + c);

        if (v.product.offer_price) {
          if (v.variants && v.variants.length > 0) {
            const v_price = sumCal + parseFloat(v.product.offer_price);
            const checkFlshPrdct = checkProductExistsInFlashSale(
              v.product_id,
              v_price,
              websiteSetup
            );
            return checkFlshPrdct * v.qty;
          } else {
            const wo_v_price = checkProductExistsInFlashSale(
              v.product_id,
              parseFloat(v.product.offer_price),
              websiteSetup
            );
            return wo_v_price * v.qty;
          }
        } else {
          if (v.variants && v.variants.length > 0) {
            const v_price = sumCal + parseFloat(v.product.price);
            const checkFlshPrdct = checkProductExistsInFlashSale(
              v.product_id,
              v_price,
              websiteSetup
            );
            return checkFlshPrdct * v.qty;
          } else {
            const wo_v_price = checkProductExistsInFlashSale(
              v.product_id,
              parseFloat(v.product.price),
              websiteSetup
            );
            return wo_v_price * v.qty;
          }
        }
      });
      setSubTotal(calculatedSubTotal);
    }
  }, [carts, websiteSetup]);

  // Calculate discount when coupon changes
  useEffect(() => {
    if (couponData.couponCode) {
      const discountAmount = calculateDiscountAmount(
        couponData.couponCode,
        totalPrice
      );
      updateCouponData("discountCoupon", discountAmount);
    }
  }, [couponData.couponCode, totalPrice]);

  // Calculate main total price when shipping changes
  useEffect(() => {
    if (shippingCharge) {
      setMainTotalPrice(totalPrice + parseInt(shippingCharge));
    } else if (locationShippingPrice) {
      setMainTotalPrice(Number(totalPrice) + Number(locationShippingPrice));
    } else {
      setMainTotalPrice(totalPrice);
    }
  }, [shippingCharge, locationShippingPrice, totalPrice]);

  // Initialize shipping rules when addresses and rules are available
  useEffect(() => {
    if (
      addresses &&
      addresses.length > 0 &&
      shippingRules &&
      shippingRules.length > 0
    ) {
      const shippingAddress =
        addresses.find((item) => parseInt(item.id) === parseInt(selectedShipping)) ||
        addresses[0];

      if (shippingAddress) {
        if (Number(webSettings?.map_status) === 1) {
          const calcPrice = calculateLocationShippingPrice(shippingAddress);
          setLocationShippingPrice(calcPrice);
          setShippingRulesByCityId([]);
        } else {
          setLocationShippingPrice(null);
          const filteredRules = filterShippingRulesByCity(
            shippingRules,
            parseInt(shippingAddress.city_id)
          );
          setShippingRulesByCityId(filteredRules);

          // Auto-select first matching rule if none selected
          if (!selectedRule && filteredRules.length > 0) {
            const firstRule = filteredRules[0];
            setSelectedRule(firstRule.id.toString());
            setShippingCharge(firstRule.shipping_fee);
          }
        }
      }
    }
  }, [shippingRules, addresses]);

  /**
   * Initialize data on component mount
   * Get all addresses, payment methods, shipping rules
   * @Initializes useLazyGetCheckoutDataApiQuery and useLazyGetGuestCheckoutDataApiQuery @const getCheckoutDataApi and  @const getGuestCheckoutDataApi
   * Helper @func SET_CHECKOUT_DATA @params response, isRealUser
   * @func fetchCheckoutData
   * @call fetchCheckoutData into useEffect
   */

  // for real user
  const [getCheckoutDataApi, { isLoading: isGetCheckoutDataLoading }] =
    useLazyGetCheckoutDataApiQuery();
  // for guest user
  const [
    getGuestCheckoutDataApi,
    { isLoading: isGetGuestCheckoutDataLoading },
  ] = useLazyGetGuestCheckoutDataApiQuery();

  // Helper function for set checkout data
  const SET_CHECKOUT_DATA = (response, isRealUser) => {
    /**
     * Set payment getways status
     * @array [sslcommerz, paypalPaymentInfo, mollie, paystackAndMollie, instamojo, myfatoorah, flutterwavePaymentInfo, razorpayPaymentInfo, stripePaymentInfo, bkash, cash_on_delivery_status, bankPaymentInfo]
     * use updatePaymentStatuses to set payment statuses
     */
    const getWays = [
      "cash_on_delivery_status",
      "bankPaymentInfo",
      "iyzico",
    ];

    // Payment gateway status mapping
    const gatewayStatusMap = {
      cash_on_delivery_status: () =>
        Number(response.data?.bankPaymentInfo?.cash_on_delivery_status) === 1,
      bankPaymentInfo: () =>
        Number(response.data?.bankPaymentInfo?.status) === 1,
      iyzico: () =>
        Number(response.data?.iyzico?.status) === 1,
    };

    // Update payment statuses for all gateways
    getWays.forEach((way) => {
      const getStatus = gatewayStatusMap[way] || (() => false);
      updatePaymentStatuses(way, getStatus());
    });

    // bank payment info
    setBankInfo(response.data.bankPaymentInfo);

    // Set shipping rules
    const shippings = response.data.shippings || [];
    setShipppingRules(shippings);

    if (isRealUser) {
      // Set addresses
      const checkoutAddresses = response.data.addresses || [];
      setAddresses(checkoutAddresses);

      // Set default shipping and billing addresses
      if (checkoutAddresses.length > 0) {
        const defaultShippingAddress = checkoutAddresses[0];

        setShipping(defaultShippingAddress.id);
        setBilling(defaultShippingAddress.id);

        // Calculate location shipping price if map is enabled
        if (Number(webSettings?.map_status) === 1) {
          setLocationShippingPrice(
            calculateLocationShippingPrice(defaultShippingAddress)
          );
        } else {
          // Filter and auto-select shipping rules for default address
          const filteredRules = filterShippingRulesByCity(
            shippings,
            parseInt(defaultShippingAddress.city_id)
          );
          setShippingRulesByCityId(filteredRules);
          // Auto-select best rule (cheapest matching)
          if (filteredRules.length > 0) {
            const cartTotal = cart?.cartProducts?.reduce((sum, item) => {
              const basePrice = item.product?.offer_price || item.product?.price || 0;
              return sum + parseFloat(basePrice) * parseInt(item.qty);
            }, 0) || 0;
            const matching = filteredRules.filter((r) => {
              if (r.type !== "base_on_price") return true;
              const from = parseInt(r.condition_from);
              const to = parseInt(r.condition_to);
              return from <= cartTotal && (to >= cartTotal || to === -1);
            });
            const best = (matching.length > 0 ? matching : filteredRules)
              .sort((a, b) => parseFloat(a.shipping_fee) - parseFloat(b.shipping_fee))[0];
            setSelectedRule(best.id.toString());
            setShippingCharge(best.shipping_fee);
          }
        }
      }
    } else {
      // Guest user: show default rules (city_id=0) immediately
      const defaultRules = shippings.filter(
        (rule) => parseInt(rule.city_id) === 0
      );
      if (defaultRules.length > 0) {
        setShippingRulesByCityId(defaultRules);
        const best = [...defaultRules].sort((a, b) => parseFloat(a.shipping_fee) - parseFloat(b.shipping_fee))[0];
        setSelectedRule(best.id.toString());
        setShippingCharge(best.shipping_fee);
      }
    }
  };

  const fetchCheckoutData = async () => {
    if (!auth()) {
      if (!isGuestCheckoutEnabled()) {
        return;
      }
      const response = await getGuestCheckoutDataApi();
      if (response.data) {
        SET_CHECKOUT_DATA(response, false);
      }
      return;
    }

    const userToken = auth()?.access_token;
    const response = await getCheckoutDataApi({ token: userToken });
    if (response.data) {
      SET_CHECKOUT_DATA(response, true);
    }
  };

  useEffect(() => {
    fetchCheckoutData();
  }, []);

  const guestCheckoutAddressProps = {
    fName,
    setFName,
    lName,
    setLName,
    email,
    setEmail,
    phone,
    setPhone,
    address,
    setAddress,
    home,
    setHome,
    office,
    setOffice,
    country,
    setCountry,
    setCountryName,
    state,
    setState,
    city,
    setCity,
    guestLocation,
    setGuestLocation,
    shippingHandler,
    errors,
  };

  // Don't render if no cart
  if (!carts) {
    return null;
  }

  if (!auth() && !isGuestCheckoutEnabled()) {
    return (
      <div className="checkout-page-wrapper w-full bg-white pb-[60px] min-h-[40vh] flex items-center justify-center">
        <LoaderStyleTwo />
      </div>
    );
  }

  return (
    <>
      <div className="checkout-page-wrapper w-full bg-white pb-[60px]">
        {/* Page Title */}
        <div className="w-full mb-5">
          <PageTitle
            title="Ödeme"
            breadcrumb={[
              { name: "Ana Sayfa", path: "/" },
              { name: "Ödeme", path: "/checkout" },
            ]}
          />
        </div>

        {/* Main Checkout Content */}
        <div className="checkout-main-content w-full">
          <div className="container-x mx-auto">
            {!isGetCheckoutDataLoading || !isGetGuestCheckoutDataLoading ? (
              <div className="w-full lg:flex lg:space-x-[30px] rtl:space-x-reverse">
                {/* Left Column - Address Section */}
                <div className="lg:w-4/6 w-full">
                  <h2 className="sm:text-2xl text-xl text-qblack font-medium mt-5 mb-5">
                    Adresler
                  </h2>

                  {auth() ? (
                    <AddressTabs
                      addresses={addresses}
                      isAddressLoading={isAddressesFetching}
                      activeAddress={activeAddress}
                      selectedBilling={selectedBilling}
                      selectedShipping={selectedShipping}
                      webSettings={webSettings}
                      setActiveAddress={setActiveAddress}
                      setBilling={setBilling}
                      shippingHandler={shippingHandler}
                      deleteAddress={deleteAddress}
                      onAddressRefresh={handleAddressRefresh}
                    />
                  ) : (
                    <GuestCheckoutAddressForm {...guestCheckoutAddressProps} />
                  )}
                </div>

                {/* Right Column - Order Summary and Payment */}
                <div className="flex-1">
                  {/* Coupon Section */}
                  <CouponSection
                    couponData={couponData}
                    updateCouponData={updateCouponData}
                    submitCoupon={submitCoupon}
                    isApplyCouponLoading={isApplyCouponLoading}
                  />

                  {/* Order Summary */}
                  <h2 className="sm:text-2xl text-xl text-qblack font-medium mt-5 mb-5">
                    Sipariş Özeti
                  </h2>

                  <OrderSummary
                    carts={carts}
                    subTotal={subTotal}
                    totalPrice={totalPrice}
                    discountCoupon={couponData.discountCoupon}
                    mainTotalPrice={mainTotalPrice}
                    shippingRulesByCityId={shippingRulesByCityId}
                    selectedRule={selectedRule}
                    shippingCharge={shippingCharge}
                    locationShippingPrice={locationShippingPrice}
                    webSettings={webSettings}
                    selectedRuleHandler={selectedRuleHandler}
                    price={price}
                    totalWeight={totalWeight}
                    totalQty={totalQty}
                  />

                  {/* Payment Methods */}
                  <PaymentMethods
                    selectPayment={selectPayment}
                    setPaymentMethod={setPaymentMethod}
                    paymentStatuses={paymentStatuses}
                    bankInfo={bankInfo}
                    transactionInfo={transactionInfo}
                    setTransactionInfo={setTransactionInfo}
                    legalConsentValues={legalConsentValues}
                    setLegalConsentValues={setLegalConsentValues}
                    placeOrderHandler={placeOrderHandler}
                    totalPrice={mainTotalPrice}
                  />
                </div>
              </div>
            ) : (
              <div className="w-full flex justify-center min-h-screen">
                <LoaderStyleTwo width="100px" height="100px" margin="0" />
              </div>
            )}
          </div>
        </div>
      </div>
    </>
  );
}
