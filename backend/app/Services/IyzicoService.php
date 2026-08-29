<?php

namespace App\Services;

use App\Models\IyzicoPayment;
use Iyzipay\Model\Approval;
use Iyzipay\Model\Address;
use Iyzipay\Model\BasketItem;
use Iyzipay\Model\BasketItemType;
use Iyzipay\Model\Buyer;
use Iyzipay\Model\CheckoutForm;
use Iyzipay\Model\CheckoutFormInitialize;
use Iyzipay\Model\Currency;
use Iyzipay\Model\Locale;
use Iyzipay\Model\PaymentGroup;
use Iyzipay\Model\Refund;
use Iyzipay\Model\SubMerchant;
use Iyzipay\Model\SubMerchantType;
use Iyzipay\Options;
use Iyzipay\Request\CreateApprovalRequest;
use Iyzipay\Request\CreateCheckoutFormInitializeRequest;
use Iyzipay\Request\CreateRefundRequest;
use Iyzipay\Request\CreateSubMerchantRequest;
use Iyzipay\Request\RetrieveCheckoutFormRequest;
use Iyzipay\Request\RetrieveSubMerchantRequest;
use Iyzipay\Request\UpdateSubMerchantRequest;

class IyzicoService
{
    public function getConfig(): IyzicoPayment
    {
        $config = IyzicoPayment::first();
        if (!$config || !$config->status) {
            throw new \Exception('Iyzico ödeme yapılandırması bulunamadı veya aktif değil.');
        }
        if (!$config->api_key || !$config->secret_key) {
            throw new \Exception('Iyzico API anahtarları eksik.');
        }

        return $config;
    }

    private function options(): Options
    {
        $config = $this->getConfig();
        $options = new Options();
        $options->setApiKey($config->api_key);
        $options->setSecretKey($config->secret_key);
        $options->setBaseUrl(
            $config->is_test_mode
                ? 'https://sandbox-api.iyzipay.com'
                : 'https://api.iyzipay.com'
        );
        return $options;
    }

    public function createCheckoutForm(array $data): CheckoutFormInitialize
    {
        $request = new CreateCheckoutFormInitializeRequest();
        $request->setLocale($data['locale'] ?? Locale::TR);
        $request->setConversationId((string)$data['conversation_id']);
        $request->setPrice((string)$data['price']);
        $request->setPaidPrice((string)$data['paid_price']);
        $request->setCurrency($data['currency'] ?? Currency::TL);
        $request->setBasketId((string)$data['basket_id']);
        $request->setPaymentGroup($data['payment_group'] ?? PaymentGroup::PRODUCT);
        $request->setCallbackUrl((string)$data['callback_url']);
        $request->setBuyer($this->makeBuyer($data['buyer']));
        $request->setShippingAddress($this->makeAddress($data['shipping_address']));
        $request->setBillingAddress($this->makeAddress($data['billing_address']));
        $request->setBasketItems($this->makeBasketItems($data['basket_items'] ?? []));
        if (isset($data['enabled_installments']) && is_array($data['enabled_installments'])) {
            // Iyzico: taksit seçeneklerini ödeme bazlı kısıtlar
            $request->setEnabledInstallments(array_values($data['enabled_installments']));
        }

        return CheckoutFormInitialize::create($request, $this->options());
    }

    public function retrieveCheckoutForm(string $token, string $conversationId): CheckoutForm
    {
        $request = new RetrieveCheckoutFormRequest();
        $request->setLocale(Locale::TR);
        $request->setConversationId($conversationId);
        $request->setToken($token);

        return CheckoutForm::retrieve($request, $this->options());
    }

    /**
     * Process a refund via Iyzico API.
     *
     * @param string $paymentTransactionId The payment transaction ID from Iyzico
     * @param float $amount The refund amount (supports partial refunds)
     * @param string $conversationId Unique identifier for this refund request
     * @return Refund
     */
    public function refund(string $paymentTransactionId, float $amount, string $conversationId): Refund
    {
        $request = new CreateRefundRequest();
        $request->setLocale(Locale::TR);
        $request->setConversationId($conversationId);
        $request->setPaymentTransactionId($paymentTransactionId);
        $request->setPrice((string) number_format($amount, 2, '.', ''));
        $request->setCurrency(Currency::TL);
        $request->setIp('127.0.0.1');

        return Refund::create($request, $this->options());
    }

    /**
     * Create a sub-merchant on Iyzico.
     *
     * @param array $data Required: external_id, type (PERSONAL|PRIVATE_COMPANY|LIMITED_OR_JOINT_STOCK_COMPANY),
     *                     name, email, gsm_number, iban, identity_number. Optional: address, tax_office,
     *                     tax_number, legal_company_title, contact_name, contact_surname.
     */
    public function createSubMerchant(array $data): SubMerchant
    {
        $request = new CreateSubMerchantRequest();
        $request->setLocale(Locale::TR);
        $request->setConversationId($data['conversation_id'] ?? 'sm_' . $data['external_id']);
        $request->setSubMerchantExternalId($data['external_id']);
        $request->setSubMerchantType($data['type']);
        $request->setName($data['name']);
        $request->setEmail($data['email']);
        $request->setGsmNumber($data['gsm_number']);
        $request->setIban($data['iban']);
        $request->setIdentityNumber($data['identity_number']);
        $request->setCurrency(Currency::TL);
        $request->setAddress($data['address'] ?? '');

        if (!empty($data['contact_name'])) {
            $request->setContactName($data['contact_name']);
        }
        if (!empty($data['contact_surname'])) {
            $request->setContactSurname($data['contact_surname']);
        }
        if (!empty($data['tax_office'])) {
            $request->setTaxOffice($data['tax_office']);
        }
        if (!empty($data['tax_number'])) {
            $request->setTaxNumber($data['tax_number']);
        }
        if (!empty($data['legal_company_title'])) {
            $request->setLegalCompanyTitle($data['legal_company_title']);
        }

        return SubMerchant::create($request, $this->options());
    }

    /**
     * Update an existing sub-merchant on Iyzico.
     */
    public function updateSubMerchant(array $data): SubMerchant
    {
        $request = new UpdateSubMerchantRequest();
        $request->setLocale(Locale::TR);
        $request->setConversationId($data['conversation_id'] ?? 'sm_update_' . $data['sub_merchant_key']);
        $request->setSubMerchantKey($data['sub_merchant_key']);

        if (isset($data['name'])) $request->setName($data['name']);
        if (isset($data['email'])) $request->setEmail($data['email']);
        if (isset($data['gsm_number'])) $request->setGsmNumber($data['gsm_number']);
        if (isset($data['iban'])) $request->setIban($data['iban']);
        if (isset($data['identity_number'])) $request->setIdentityNumber($data['identity_number']);
        if (isset($data['address'])) $request->setAddress($data['address']);
        if (isset($data['tax_office'])) $request->setTaxOffice($data['tax_office']);
        if (isset($data['tax_number'])) $request->setTaxNumber($data['tax_number']);
        if (isset($data['legal_company_title'])) $request->setLegalCompanyTitle($data['legal_company_title']);
        if (isset($data['contact_name'])) $request->setContactName($data['contact_name']);
        if (isset($data['contact_surname'])) $request->setContactSurname($data['contact_surname']);
        $request->setCurrency(Currency::TL);

        return SubMerchant::update($request, $this->options());
    }

    /**
     * Retrieve a sub-merchant from Iyzico.
     */
    public function retrieveSubMerchant(string $subMerchantExternalId): SubMerchant
    {
        $request = new RetrieveSubMerchantRequest();
        $request->setLocale(Locale::TR);
        $request->setConversationId('sm_retrieve_' . $subMerchantExternalId);
        $request->setSubMerchantExternalId($subMerchantExternalId);

        return SubMerchant::retrieve($request, $this->options());
    }

    private function makeBuyer(array $payload): Buyer
    {
        $buyer = new Buyer();
        $buyer->setId((string)$payload['id']);
        $buyer->setName((string)$payload['name']);
        $buyer->setSurname((string)$payload['surname']);
        $buyer->setGsmNumber((string)$payload['gsm_number']);
        $buyer->setEmail((string)$payload['email']);
        $buyer->setIdentityNumber((string)$payload['identity_number']);
        $buyer->setLastLoginDate((string)$payload['last_login_date']);
        $buyer->setRegistrationDate((string)$payload['registration_date']);
        $buyer->setRegistrationAddress((string)$payload['registration_address']);
        $buyer->setIp((string)$payload['ip']);
        $buyer->setCity((string)$payload['city']);
        $buyer->setCountry((string)$payload['country']);
        $buyer->setZipCode((string)$payload['zip_code']);
        return $buyer;
    }

    private function makeAddress(array $payload): Address
    {
        $address = new Address();
        $address->setContactName((string)$payload['contact_name']);
        $address->setCity((string)$payload['city']);
        $address->setCountry((string)$payload['country']);
        $address->setAddress((string)$payload['address']);
        $address->setZipCode((string)$payload['zip_code']);
        return $address;
    }

    private function makeBasketItems(array $items): array
    {
        $basketItems = [];

        foreach ($items as $item) {
            $basketItem = new BasketItem();
            $basketItem->setId((string)$item['id']);
            $basketItem->setName((string)$item['name']);
            $basketItem->setCategory1((string)($item['category_1'] ?? 'Genel'));
            $basketItem->setCategory2((string)($item['category_2'] ?? 'Urun'));
            $basketItem->setItemType((string)($item['item_type'] ?? BasketItemType::PHYSICAL));
            $basketItem->setPrice((string)$item['price']);
            if (!empty($item['sub_merchant_key'])) {
                $basketItem->setSubMerchantKey((string)$item['sub_merchant_key']);
            }
            if (isset($item['sub_merchant_price']) && $item['sub_merchant_price'] !== null) {
                $basketItem->setSubMerchantPrice((string)$item['sub_merchant_price']);
            }
            $basketItems[] = $basketItem;
        }

        return $basketItems;
    }

    /**
     * Marketplace: satıcı payını serbest bırakmak için ürün kalemi onayı.
     */
    public function approvePaymentItem(string $paymentTransactionId, string $conversationId): Approval
    {
        $request = new CreateApprovalRequest();
        $request->setLocale(Locale::TR);
        $request->setConversationId($conversationId);
        $request->setPaymentTransactionId($paymentTransactionId);

        return Approval::create($request, $this->options());
    }

    /**
     * @deprecated SellerPayoutService + approvePaymentItem kullanın.
     */
    public function transferToSubMerchant(string $subMerchantKey, float $amount, string $orderId, string $sellerName)
    {
        \Log::warning('transferToSubMerchant deprecated — use SellerPayoutService::approveIyzicoOrderItems', [
            'sub_merchant_key' => $subMerchantKey,
            'amount' => $amount,
            'order_id' => $orderId,
            'seller_name' => $sellerName,
        ]);

        return (object) [
            'status' => 'failure',
            'message' => 'transferToSubMerchant kullanımdan kaldırıldı. İyzico Approval API kullanın.',
        ];
    }
}
