import { useRouter } from "next/navigation";
import { useDispatch } from "react-redux";
import { toast } from "react-toastify";
import clearCartAfterOrder from "@/utils/clearCartAfterOrder";
import auth from "@/utils/auth";
import appConfig from "@/appConfig";
import {
  useBankPaymentApiMutation,
  useCashOnDeliveryGuestApiMutation,
  useCashOnDeliveryApiMutation,
  useBankPaymentGuestApiMutation,
  useDraftOrderApiMutation,
  useDraftOrderGuestApiMutation,
  useIyzicoCheckoutApiMutation,
  useIyzicoCheckoutGuestApiMutation,
} from "@/redux/features/order/paymentGetways/apiSlice";
import guestFormValidation from "../utils/guestFormValidation";
import { allRequiredChecked } from "@/components/Legal/LegalConsentCheckboxes";
import { CHECKOUT_REQUIRED_CONSENTS } from "@/config/legalDocuments";
import { getGuestConsentId, recordLegalConsents } from "@/api/recordLegalConsents";
import { validateInvoiceForm } from "../components/InvoiceCheckoutSection";

export default function usePlaceOrder({
  carts,
  addresses,
  webSettings,
  selectedBilling,
  selectedShipping,
  selectedRule,
  couponCode,
  selectPayment,
  transactionInfo,
  legalConsentValues = {},
  invoiceData = {},
  guestFields = {},
  setNewAddress = () => {},
  ServeLangItem = () => ({}),
}) {
  const router = useRouter();
  const dispatch = useDispatch();

  /**
   * @Initialization Payment Getway Apis
   * @const cashOnDeliveryApi, @const cashOnDeliveryGuestApi, @const bankPaymentApi, @const bankPaymentGuestApi, @const draftOrderApi, @const draftOrderGuestApi
   */
  const [cashOnDeliveryApi, { isLoading: isCashOnDeliveryLoading }] =
    useCashOnDeliveryApiMutation();
  const [cashOnDeliveryGuestApi, { isLoading: isCashOnDeliveryGuestLoading }] =
    useCashOnDeliveryGuestApiMutation();
  const [bankPaymentApi, { isLoading: isBankPaymentLoading }] =
    useBankPaymentApiMutation();
  const [bankPaymentGuestApi, { isLoading: isBankPaymentGuestLoading }] =
    useBankPaymentGuestApiMutation();
  const [draftOrderApi, { isLoading: isDraftOrderLoading }] =
    useDraftOrderApiMutation();
  const [draftOrderGuestApi, { isLoading: isDraftOrderGuestLoading }] =
    useDraftOrderGuestApiMutation();
  const [iyzicoCheckoutApi, { isLoading: isIyzicoLoading }] =
    useIyzicoCheckoutApiMutation();
  const [iyzicoCheckoutGuestApi, { isLoading: isIyzicoGuestLoading }] =
    useIyzicoCheckoutGuestApiMutation();

  const removeCouponFromStorage = () => {
    localStorage.removeItem("coupon_set_date");
    localStorage.removeItem("coupon");
  };

  const getSuccessFailUrlParams = (orderId) => {
    const origin = typeof window !== "undefined" ? window.location.origin : "";
    return `&order_id=${orderId}&request_from=react_web&frontend_success_url=${JSON.stringify(
      origin + "/order/"
    )}&frontend_faild_url=${JSON.stringify(origin + "/payment-failed")}`;
  };

  const buildGuestAddressObject = () => ({
    name:
      guestFields.fName && guestFields.lName
        ? `${guestFields.fName} ${guestFields.lName}`
        : null,
    email: guestFields.email,
    phone: guestFields.phone,
    address: guestFields.address,
    type: guestFields.home || guestFields.office || null,
    country: guestFields.countryName || String(guestFields.country || ""),
    state: String(guestFields.state || ""),
    city: String(guestFields.city || ""),
    invoice_type: invoiceData.invoice_type,
    tc_identity: invoiceData.tc_identity,
    tax_number: invoiceData.tax_number,
    tax_office: invoiceData.tax_office,
    latitude:
      Number(webSettings?.map_status) === 1 && guestFields.location
        ? guestFields.location.lat
        : undefined,
    longitude:
      Number(webSettings?.map_status) === 1 && guestFields.location
        ? guestFields.location.lng
        : undefined,
  });

  // when NOT REDIRECT payment getway route
  const handleApiResponse = async (response) => {
    try {
      const res = await response;
      if (res) {
        try {
          await recordLegalConsents({
            consents: CHECKOUT_REQUIRED_CONSENTS.flatMap((item, index) => {
              const key = item.key || item.slug || `consent-${index}`;
              if (!legalConsentValues[key]) return [];
              const slugs = item.slugs || (item.slug ? [item.slug] : []);
              return slugs.map((slug) => ({ slug, status: true }));
            }),
            context: "checkout",
            orderId: res.order_id,
            guestIdentifier: getGuestConsentId(),
          });
        } catch {
          // consent logging should not block order success UX
        }
        toast.success(res.message);
        await clearCartAfterOrder(dispatch, res.order_id);
        router.push(`/order/${res.order_id}`);
        removeCouponFromStorage();
      }
    } catch (err) {
      const msg = err?.data?.message || err?.data?.errors?.join?.(", ") || "Sipariş oluşturulurken bir hata oluştu. Lütfen tekrar deneyin.";
      toast.error(msg);
    }
  };

  // when REDIRECT payment getway route
  const handleDraftOrderAndRedirect = async (
    apiMethod,
    urlPrefix,
    queryParams = "",
    guest = false
  ) => {
    try {
      const res = await apiMethod().unwrap();
      const url = `${appConfig.BASE_URL}${urlPrefix}?${
        guest ? "" : "token=" + auth()?.access_token
      }${queryParams}${getSuccessFailUrlParams(res.order_id)}`;
      router.push(url);
      removeCouponFromStorage();
    } catch (err) {
      const msg = err?.data?.message || err?.response?.data?.message || "Ödeme yönlendirmesi başlatılamadı. Lütfen tekrar deneyin.";
      toast.error(msg);
    }
  };

  const placeOrderHandler = () => {
    if (!auth()) {
      toast.error("Sipariş vermek için giriş yapmalısınız.");
      return;
    }

    if (!allRequiredChecked(CHECKOUT_REQUIRED_CONSENTS, legalConsentValues)) {
      toast.error("Sipariş vermek için zorunlu yasal metinleri kabul etmelisiniz.");
      return;
    }

    const invoiceError = validateInvoiceForm(invoiceData);
    if (invoiceError) {
      toast.error(invoiceError);
      return;
    }

    const isGuest = false;
    const invoicePayload = {
      invoice_type: invoiceData.invoice_type,
      tc_identity: invoiceData.tc_identity,
      tax_number: invoiceData.tax_number,
      tax_office: invoiceData.tax_office,
    };
    const basePayload = isGuest
      ? {
          address: buildGuestAddressObject(),
          cart_products: carts,
          shipping_method_id: parseInt(selectedRule),
          coupon: couponCode && couponCode.code,
          ...invoicePayload,
        }
      : {
          cart_products: carts,
          shipping_address_id: selectedShipping,
          billing_address_id: selectedBilling,
          shipping_method_id: parseInt(selectedRule),
          coupon: couponCode && couponCode.code,
          ...invoicePayload,
        };

    const routes = {
      cashOnDelivery: () =>
        handleApiResponse(
          isGuest
            ? cashOnDeliveryGuestApi({
                data: basePayload,
              }).unwrap()
            : cashOnDeliveryApi({
                data: basePayload,
                token: auth()?.access_token,
              }).unwrap()
        ),
      bankpayment: () =>
        handleApiResponse(
          isGuest
            ? bankPaymentGuestApi({
                data: {
                  ...basePayload,
                  tnx_info: transactionInfo,
                },
              }).unwrap()
            : bankPaymentApi({
                data: {
                  ...basePayload,
                  tnx_info: transactionInfo,
                },
                token: auth()?.access_token,
              }).unwrap()
        ),
      iyzico: async () => {
        try {
          const apiCall = isGuest
            ? iyzicoCheckoutGuestApi({ data: basePayload })
            : iyzicoCheckoutApi({
                data: basePayload,
                token: auth()?.access_token,
              });
          const res = await apiCall.unwrap();
          if (res.success && res.data.checkout_url) {
            sessionStorage.setItem("seyfibaba_pending_order_id", String(res.data.order_id || res.order_id || ""));
            router.push(res.data.checkout_url);
            removeCouponFromStorage();
          } else {
            toast.error(res.message || "Iyzico oturumu başlatılamadı");
          }
        } catch (err) {
          const baseMsg = err?.data?.message || "";
          const detail = err?.data?.error || "";
          const msg = detail && baseMsg ? `${baseMsg} (${detail})` : baseMsg || detail;
          if (msg.includes("api bilgileri") || msg.includes("API anahtarları")) {
            toast.error("Kredi kartı ödeme sistemi henüz yapılandırılmamış. Lütfen başka bir ödeme yöntemi seçin.");
          } else {
            toast.error(msg || "Kredi kartı ile ödeme başlatılırken bir hata oluştu");
          }
        }
      },
    };

    if (isGuest) {
      const validationFields = [
        { field: "fName", message: "Ad alanı zorunludur" },
        { field: "lName", message: "Soyad alanı zorunludur" },
        { field: "email", message: "E-posta alanı zorunludur" },
        { field: "phone", message: "Telefon alanı zorunludur" },
        { field: "address", message: "Adres alanı zorunludur" },
        { field: "country", message: "Ülke alanı zorunludur" },
        { field: "state", message: "İl alanı zorunludur" },
        { field: "city", message: "İlçe alanı zorunludur" },
      ];

      // Validate all fields
      let errors = {};
      validationFields.forEach(({ field, message }) => {
        if (guestFormValidation(guestFields, field, message)) {
          errors = {
            ...errors,
            [field]: guestFormValidation(guestFields, field, message),
          };
        }
      });

      const isGuestFormValid = Object.keys(errors).length === 0;

      if (!isGuestFormValid) {
        guestFields.setErrors(errors);
        return toast.error("Lütfen tüm zorunlu alanları doldurun");
      } else {
        guestFields.setErrors(null);
      }
    }

    if (!isGuest && !selectedShipping) {
      return toast.error("Lütfen teslimat adresi seçin");
    }

    if (!isGuest && !selectedBilling) {
      return toast.error("Lütfen fatura adresi seçin");
    }

    if (!selectedRule)
      return toast.error(ServeLangItem()?.Please_Select_Shipping_Rule);

    if (!selectPayment)
      return toast.error(ServeLangItem()?.Please_Select_Your_Payment_Method);

    if (!isGuest) {
      const findAddress = addresses?.find(
        (f) => parseInt(f.id) === parseInt(selectedShipping)
      );

      if (!findAddress) {
        return toast.error("Seçilen teslimat adresi bulunamadı");
      }

      if (
        !addresses?.some((f) => parseInt(f.id) === parseInt(selectedBilling))
      ) {
        return toast.error("Seçilen fatura adresi bulunamadı");
      }

      if (Number(webSettings?.map_status) !== 1) {
        const hasValidShippingRule = Number.isInteger(parseInt(selectedRule));
        if (!hasValidShippingRule) {
          return toast.error("Lütfen geçerli bir kargo seçeneği seçin");
        }
      }
    }

    if (!isGuest && Number(webSettings?.map_status) === 1) {
      const findAddress = addresses?.find(
        (f) => parseInt(f.id) === parseInt(selectedShipping)
      );
      if (!Number(findAddress?.latitude) || !Number(findAddress?.longitude)) {
        toast.error("Konum bulunamadı. Lütfen yeni bir konum oluşturun");
        return setNewAddress(true);
      }
    }

    return routes[selectPayment]
      ? routes[selectPayment]()
      : toast.error(ServeLangItem()?.Select_your_payment_system);
  };

  return { placeOrderHandler };
}
