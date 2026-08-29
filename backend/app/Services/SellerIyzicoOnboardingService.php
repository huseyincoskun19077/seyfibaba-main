<?php

namespace App\Services;

use App\Models\Vendor;
use App\Services\CallCenter\QuickSellerRegistrationService;
use Illuminate\Support\Facades\Log;
use Iyzipay\Model\SubMerchantType;

class SellerIyzicoOnboardingService
{
    /** @deprecated Bireysel kaldırıldı; normalize şahıs şirketine map edilir */
    public const TYPE_INDIVIDUAL = 'individual';
    public const TYPE_SOLE = 'sole_proprietorship';
    public const TYPE_LIMITED = 'limited_company';

    /** Yeni kayıtlarda seçilebilir satıcı tipleri */
    public const SELECTABLE_TYPES = [
        self::TYPE_SOLE,
        self::TYPE_LIMITED,
    ];

    /** Yeni yüklemelerde kabul edilen belgeler (eski kimlik vb. arşivde kalır) */
    public const UPLOADABLE_DOCUMENT_TYPES = [
        'tax_certificate',
    ];

    public function __construct(private readonly IyzicoService $iyzicoService)
    {
    }

    /**
     * Normalize legacy seller_type values.
     * Bireysel/personal artık desteklenmez → şahıs şirketi.
     */
    public function normalizeSellerType(?string $sellerType): string
    {
        return match ($sellerType) {
            self::TYPE_INDIVIDUAL, 'personal' => self::TYPE_SOLE,
            self::TYPE_SOLE, 'private_company', 'sahis' => self::TYPE_SOLE,
            self::TYPE_LIMITED, 'corporate', 'ltd', 'company' => self::TYPE_LIMITED,
            default => self::TYPE_LIMITED,
        };
    }

    public function iyzicoTypeFor(string $sellerType): string
    {
        return match ($this->normalizeSellerType($sellerType)) {
            self::TYPE_SOLE => SubMerchantType::PRIVATE_COMPANY,
            default => SubMerchantType::LIMITED_OR_JOINT_STOCK_COMPANY,
        };
    }

    public function requiredDocumentTypes(string $sellerType): array
    {
        return self::UPLOADABLE_DOCUMENT_TYPES;
    }

    /**
     * @return list<string> Human-readable missing field labels
     */
    public function missingFields(Vendor $vendor): array
    {
        $user = $vendor->relationLoaded('user')
            ? $vendor->user
            : $vendor->user()->first(['id', 'email', 'phone', 'address', 'tc_identity', 'name']);
        $type = $this->normalizeSellerType($vendor->seller_type);
        $tc = $vendor->tc_identity ?: data_get($user, 'tc_identity');
        $iban = strtoupper(preg_replace('/\s+/', '', (string) ($vendor->iban ?? '')));
        $phone = $vendor->phone ?: data_get($user, 'phone');
        $address = $vendor->address ?: data_get($user, 'address');
        $placeholderAddress = 'Adres bilgisi sonra tamamlanacak';

        $missing = [];

        if (! $this->hasValidContactEmail($user?->email)) {
            $missing[] = 'E-posta';
        }
        if (empty($iban) || ! preg_match('/^TR\d{24}$/', $iban)) {
            $missing[] = 'IBAN';
        }
        if (empty($phone)) {
            $missing[] = 'Telefon';
        }
        if (empty($address) || $address === $placeholderAddress) {
            $missing[] = 'Adres';
        }

        if ($type === self::TYPE_SOLE) {
            if (empty($tc) || strlen(preg_replace('/\D/', '', (string) $tc)) !== 11) {
                $missing[] = 'TC Kimlik No';
            }
        }

        if ($type === self::TYPE_SOLE || $type === self::TYPE_LIMITED) {
            if (empty($vendor->tax_office)) {
                $missing[] = 'Vergi Dairesi';
            }
            if (empty($vendor->legal_company_title)) {
                $missing[] = 'Ticari Unvan';
            }
        }

        if ($type === self::TYPE_LIMITED) {
            if (empty($vendor->tax_number)) {
                $missing[] = 'Vergi No';
            }
            // Iyzico 5024: payout onayında identityNumber gerekir
            if (empty($tc) || strlen(preg_replace('/\D/', '', (string) $tc)) !== 11) {
                $missing[] = 'Yetkili TC Kimlik No';
            }
        }

        return $missing;
    }

    public function hasValidContactEmail(?string $email): bool
    {
        $email = strtolower(trim((string) $email));

        if ($email === '' || QuickSellerRegistrationService::isPendingEmail($email)) {
            return false;
        }

        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * @return array{ok: bool, message: ?string, sub_merchant_key: ?string}
     */
    public function createForVendor(Vendor $vendor): array
    {
        $vendor->loadMissing('user');

        if ($vendor->iyzico_sub_merchant_key) {
            return [
                'ok' => true,
                'message' => 'Sub-merchant zaten mevcut.',
                'sub_merchant_key' => $vendor->iyzico_sub_merchant_key,
            ];
        }

        $missing = $this->missingFields($vendor);
        if ($missing !== []) {
            $reason = 'Eksik bilgiler: ' . implode(', ', $missing);
            Log::warning('Iyzico sub-merchant oluşturulamadı — eksik veri', [
                'vendor_id' => $vendor->id,
                'missing' => $missing,
            ]);

            return ['ok' => false, 'message' => $reason, 'sub_merchant_key' => null];
        }

        try {
            $payload = $this->buildPayload($vendor);
            $result = $this->iyzicoService->createSubMerchant($payload);

            if ($result->getStatus() === 'success') {
                $vendor->iyzico_sub_merchant_key = $result->getSubMerchantKey();
                $vendor->iyzico_sub_merchant_type = $payload['type'];
                $vendor->seller_type = $this->normalizeSellerType($vendor->seller_type);
                $vendor->save();

                Log::info('Iyzico sub-merchant oluşturuldu', [
                    'vendor_id' => $vendor->id,
                    'type' => $payload['type'],
                    'sub_merchant_key' => $result->getSubMerchantKey(),
                ]);

                return [
                    'ok' => true,
                    'message' => null,
                    'sub_merchant_key' => $result->getSubMerchantKey(),
                ];
            }

            $errorMsg = $result->getErrorMessage() ?: ('Hata kodu: ' . $result->getErrorCode());
            Log::error('Iyzico sub-merchant oluşturulamadı', [
                'vendor_id' => $vendor->id,
                'error_code' => $result->getErrorCode(),
                'error_message' => $result->getErrorMessage(),
            ]);

            return ['ok' => false, 'message' => 'Iyzico hatası: ' . $errorMsg, 'sub_merchant_key' => null];
        } catch (\Throwable $e) {
            Log::error('Iyzico sub-merchant exception', [
                'vendor_id' => $vendor->id,
                'message' => $e->getMessage(),
            ]);

            return ['ok' => false, 'message' => 'Sistem hatası: ' . $e->getMessage(), 'sub_merchant_key' => null];
        }
    }

    public function buildPayload(Vendor $vendor): array
    {
        $user = $vendor->user;
        $type = $this->normalizeSellerType($vendor->seller_type);
        $iyzicoType = $this->iyzicoTypeFor($type);
        $tc = (string) ($vendor->tc_identity ?: data_get($user, 'tc_identity'));
        $iban = strtoupper(preg_replace('/\s+/', '', (string) ($vendor->iban ?? '')));
        $phone = $this->normalizePhone((string) ($vendor->phone ?: data_get($user, 'phone') ?: ''));
        $nameParts = $user ? explode(' ', trim($user->name ?? ''), 2) : ['Satıcı'];

        $data = [
            'external_id' => 'VENDOR_' . $vendor->id,
            'type' => $iyzicoType,
            'name' => $vendor->shop_name ?? ('Satıcı ' . $vendor->id),
            'email' => $this->hasValidContactEmail($user?->email) ? $user->email : '',
            'gsm_number' => $phone,
            'iban' => $iban,
            'identity_number' => $tc,
            'address' => $vendor->address ?? data_get($user, 'address', 'Türkiye'),
            'contact_name' => $nameParts[0] ?? '',
            'contact_surname' => $nameParts[1] ?? ($nameParts[0] ?? ''),
        ];

        if ($type === self::TYPE_SOLE || $type === self::TYPE_LIMITED) {
            $data['tax_office'] = $vendor->tax_office ?? '';
            $data['legal_company_title'] = $vendor->legal_company_title ?? $vendor->shop_name;
        }

        if ($type === self::TYPE_LIMITED) {
            $data['tax_number'] = $vendor->tax_number;
        }

        // Şahıs şirketinde vergi no çoğu zaman TC ile aynıdır; varsa gönder
        if ($type === self::TYPE_SOLE && ! empty($vendor->tax_number)) {
            $data['tax_number'] = $vendor->tax_number;
        }

        return $data;
    }

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        if (str_starts_with($digits, '90') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0') && strlen($digits) === 11) {
            $digits = substr($digits, 1);
        }

        return '+90' . $digits;
    }
}
