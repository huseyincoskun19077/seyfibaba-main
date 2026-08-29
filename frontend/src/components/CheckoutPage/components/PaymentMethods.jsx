import LegalConsentCheckboxes, { allRequiredChecked } from "@/components/Legal/LegalConsentCheckboxes";
import { CHECKOUT_REQUIRED_CONSENTS } from "@/config/legalDocuments";
import CheckoutTickIco from "@/components/Helpers/icons/CheckoutTickIco";
import CurrencyConvert from "@/components/Shared/CurrencyConvert";
import { getWebSettings } from "../utils/checkoutUtils";

const PaymentMethods = ({
  // Payment state
  selectPayment,
  setPaymentMethod,
  paymentStatuses,

  // Bank data
  bankInfo,
  transactionInfo,
  setTransactionInfo,

  // Order handlers
  placeOrderHandler,
  legalConsentValues = {},
  setLegalConsentValues = () => {},
  
  // Total price from parent
  totalPrice = 0,
}) => {
  // Total price from parent - may arrive as string; normalize
  const totalPriceNumRaw =
    typeof totalPrice === "number"
      ? totalPrice
      : typeof totalPrice === "string"
        ? parseFloat(totalPrice)
        : 0;
  const totalPriceNum = Number.isFinite(totalPriceNumRaw) ? totalPriceNumRaw : 0;
  const displayTotal = totalPriceNum > 0 ? totalPriceNum : 0;
  
  // Get discount percent from settings, default to 3%
  const webSettings = getWebSettings();
  const rawPercent = webSettings?.bankTransferDiscountPercent;
  const bankTransferDiscountPercent =
    typeof rawPercent === "number"
      ? rawPercent
      : typeof rawPercent === "string"
        ? parseFloat(rawPercent)
        : 3;
  
  // Calculate bank transfer discount
  const bankDiscount = displayTotal > 0 ? (displayTotal * bankTransferDiscountPercent / 100) : 0;
  const bankTotal = displayTotal - bankDiscount;

  /**
   * Handle payment method selection
   * @param {string} method - Payment method name
   */
  const handlePaymentMethodSelect = (method) => {
    setPaymentMethod(method);
  };

  /**
   * Render payment method button
   * @param {string} method - Payment method key
   * @param {string} label - Payment method label
   * @param {boolean} isEnabled - Whether payment method is enabled
   * @param {JSX.Element} icon - Payment method icon
   * @returns {JSX.Element} Payment method button
   */
  const renderPaymentMethod = (method, label, isEnabled, icon = null, discountLabel = null) => {
    if (!isEnabled) return null;

    const isSelected = selectPayment === method;

    return (
      <div
        key={method}
        onClick={() => handlePaymentMethodSelect(method)}
        className={`payment-item relative bg-[#F8F8F8] text-center w-full h-[50px] text-sm flex justify-center items-center px-3 uppercase cursor-pointer ${
          isSelected ? "border-2 border-qyellow" : "border border-gray-200"
        }`}
      >
        <div className="w-full flex justify-center items-center gap-2">
          {icon || (
            <span className="text-qblack font-bold text-base notranslate">
              {label}
            </span>
          )}
          {discountLabel && (
            <span className="text-xs text-green-600 font-semibold">
              {discountLabel}
            </span>
          )}
        </div>
        {isSelected && (
          <span
            data-aos="zoom-in"
            className="absolute text-white z-10 w-6 h-6 rounded-full bg-qyellow -right-2.5 -top-2.5"
          >
            <CheckoutTickIco />
          </span>
        )}
      </div>
    );
  };

  return (
    <div className="mt-[30px] mb-5 relative">
      <div className="w-full">
        <div className="flex flex-col space-y-3">
          {/* Bank Payment with discount label */}
          {renderPaymentMethod(
            "bankpayment",
            "Banka Havalesi",
            paymentStatuses.bankPaymentInfo,
            null,
            `-%${bankTransferDiscountPercent} İndirim`
          )}

          {/* Kredi Kartı (Iyzico) */}
          {renderPaymentMethod(
            "iyzico",
            "Kredi Kartı ile Öde",
            paymentStatuses.iyzico
          )}
        </div>
      </div>

      {/* Bank Payment Form */}
      {selectPayment === "bankpayment" && (
        <div className="w-full bank-inputs mt-5">
          {/* Discount Info Box - detailed */}
          {displayTotal > 0 && (
            <div className="bank-info-alert w-full p-4 bg-green-50 rounded mb-4 border border-green-200">
              <div className="space-y-1">
                <p className="text-sm text-green-800">
                  <span className="font-semibold">Normal Tutar:</span>{" "}
                  <CurrencyConvert price={displayTotal} />
                </p>
                <p className="text-sm text-green-700">
                  <span className="font-semibold">Havale İndirimi:</span>{" "}
                  -<CurrencyConvert price={bankDiscount} /> (%{bankTransferDiscountPercent})
                </p>
                <p className="text-lg font-bold text-green-900 border-t border-green-200 pt-1 mt-1">
                  <span className="font-semibold">Toplam:</span>{" "}
                  <CurrencyConvert price={bankTotal} />
                </p>
              </div>
            </div>
          )}

          {/* Terms and Conditions */}
          <div className="mb-4 p-4 bg-amber-50 rounded border border-amber-200">
            <p className="text-sm font-semibold text-amber-900 mb-2">📋 Ödeme Şartları:</p>
            <ul className="text-xs text-amber-800 space-y-1">
              <li>• Havale/EFT yaparken sipariş numaranızı açıklama kısmına yazınız.</li>
              <li>• Ödeme yapıldıktan sonra dekontu buraya bildirin.</li>
              <li>• Siparişiniz, ödeme onaylandıktan sonra işleme alınacaktır.</li>
              <li>• %{bankTransferDiscountPercent} indirim sadece havale/EFT için geçerlidir.</li>
            </ul>
          </div>

          {/* Bank Account Info */}
          <div className="input-item mb-5">
            <div className="bank-info-alert w-full p-5 bg-amber-100 rounded mb-4">
              <p className="text-sm font-semibold text-amber-900 mb-2">Banka Hesap Bilgileri:</p>
              <p className="text-sm text-amber-800 whitespace-pre-wrap break-words">
                {bankInfo?.account_info || "Hesap Sahibi: Seyfibaba Tic. Ltd. Şti.\nBanka: Ziraat Bankası\nIBAN: TR00 0000 0000 0000 0000 0000 00\n\nHavale/EFT yaparken sipariş numaranızı açıklama kısmına yazınız."}
              </p>
            </div>
            <h6 className="input-label capitalize text-[13px] font-600 leading-[24px] text-qblack block mb-2">
              İşlem Bilgisi*
            </h6>
            <textarea
              cols="5"
              rows="5"
              value={transactionInfo}
              onChange={(e) => setTransactionInfo(e.target.value)}
              className="w-full focus:ring-0 focus:outline-none py-3 px-4 border placeholder:text-sm text-sm"
              placeholder={"Havale/EFT yaptıktan sonra dekont numarasını veya gönderici bilgisini buraya yazın."}
            ></textarea>
          </div>
        </div>
      )}

      <LegalConsentCheckboxes
        items={CHECKOUT_REQUIRED_CONSENTS}
        values={legalConsentValues}
        onChange={(key, value) =>
          setLegalConsentValues((prev) => ({ ...prev, [key]: value }))
        }
        required
        title="Yasal Onaylar"
        className="mb-5"
      />

      {/* Place Order Button */}
      <button type="button" onClick={placeOrderHandler} className="w-full">
        <div className="w-full h-[50px] black-btn flex justify-center items-center">
          <span className="text-sm font-semibold">
            {selectPayment === "bankpayment" && totalPriceNum > 0
              ? `Siparişi Ver - ${Number(bankTotal).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} TL (%${bankTransferDiscountPercent} İndirimli)`
              : "Siparişi Ver"}
          </span>
        </div>
      </button>
    </div>
  );
};

export default PaymentMethods;