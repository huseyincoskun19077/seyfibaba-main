<?php

namespace Tests\Unit\Services;

use App\Models\Vendor;
use App\Services\IyzicoService;
use App\Services\SellerIyzicoOnboardingService;
use Iyzipay\Model\SubMerchantType;
use Mockery;
use PHPUnit\Framework\TestCase;

class SellerIyzicoOnboardingServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_normalizes_legacy_types_and_maps_individual_to_sole(): void
    {
        $service = new SellerIyzicoOnboardingService(Mockery::mock(IyzicoService::class));

        $this->assertSame('limited_company', $service->normalizeSellerType('corporate'));
        $this->assertSame('sole_proprietorship', $service->normalizeSellerType('private_company'));
        $this->assertSame('sole_proprietorship', $service->normalizeSellerType('individual'));
        $this->assertSame('sole_proprietorship', $service->normalizeSellerType('personal'));
    }

    public function test_maps_iyzico_sub_merchant_types(): void
    {
        $service = new SellerIyzicoOnboardingService(Mockery::mock(IyzicoService::class));

        $this->assertSame(SubMerchantType::PRIVATE_COMPANY, $service->iyzicoTypeFor('individual'));
        $this->assertSame(SubMerchantType::PRIVATE_COMPANY, $service->iyzicoTypeFor('sole_proprietorship'));
        $this->assertSame(SubMerchantType::LIMITED_OR_JOINT_STOCK_COMPANY, $service->iyzicoTypeFor('limited_company'));
    }

    public function test_required_document_is_only_tax_certificate(): void
    {
        $service = new SellerIyzicoOnboardingService(Mockery::mock(IyzicoService::class));

        $this->assertSame(['tax_certificate'], $service->requiredDocumentTypes('sole_proprietorship'));
        $this->assertSame(['tax_certificate'], $service->requiredDocumentTypes('limited_company'));
        $this->assertSame(['tax_certificate'], $service->requiredDocumentTypes('individual'));
    }

    public function test_limited_company_requires_tax_and_authorized_tc(): void
    {
        $service = new SellerIyzicoOnboardingService(Mockery::mock(IyzicoService::class));
        $vendor = new Vendor([
            'seller_type' => 'limited_company',
            'iban' => 'TR960015700000000083650899',
            'phone' => '05551234567',
            'address' => 'Test Mah. No:1 Istanbul',
            'tax_number' => null,
            'tax_office' => null,
            'legal_company_title' => null,
            'tc_identity' => null,
        ]);
        $vendor->setRelation('user', (object) ['email' => 'seller@example.com']);

        $missing = $service->missingFields($vendor);

        $this->assertContains('Vergi No', $missing);
        $this->assertContains('Vergi Dairesi', $missing);
        $this->assertContains('Ticari Unvan', $missing);
        $this->assertContains('Yetkili TC Kimlik No', $missing);
        $this->assertNotContains('E-posta', $missing);
    }

    public function test_missing_email_is_reported_for_iyzico(): void
    {
        $service = new SellerIyzicoOnboardingService(Mockery::mock(IyzicoService::class));
        $vendor = new Vendor([
            'seller_type' => 'sole_proprietorship',
            'iban' => 'TR960015700000000083650899',
            'phone' => '05551234567',
            'address' => 'Test Mah. No:1 Istanbul',
            'tc_identity' => '12345678901',
            'tax_office' => 'Kadıköy',
            'legal_company_title' => 'Test Unvan',
        ]);
        $vendor->setRelation('user', (object) ['email' => null]);

        $missing = $service->missingFields($vendor);

        $this->assertContains('E-posta', $missing);
    }

    public function test_legacy_individual_treated_as_sole_for_missing_fields(): void
    {
        $service = new SellerIyzicoOnboardingService(Mockery::mock(IyzicoService::class));
        $vendor = new Vendor([
            'seller_type' => 'individual',
            'iban' => 'TR960015700000000083650899',
            'phone' => '05551234567',
            'address' => 'Test Mah. No:1 Istanbul',
            'tc_identity' => null,
            'tax_office' => null,
            'legal_company_title' => null,
        ]);
        $vendor->setRelation('user', (object) ['email' => 'seller@example.com']);

        $missing = $service->missingFields($vendor);

        $this->assertContains('TC Kimlik No', $missing);
        $this->assertContains('Vergi Dairesi', $missing);
        $this->assertContains('Ticari Unvan', $missing);
        $this->assertNotContains('Vergi No', $missing);
    }
}
