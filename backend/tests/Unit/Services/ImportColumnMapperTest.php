<?php

namespace Tests\Unit\Services;

use App\Services\ImportColumnMapper;
use Tests\TestCase;

class ImportColumnMapperTest extends TestCase
{
    private ImportColumnMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new ImportColumnMapper();
    }

    public function test_maps_turkish_client_headers(): void
    {
        $result = $this->mapper->mapHeaders([
            'Kod',
            'Barkod',
            'Ürün Adı',
            'Stok',
            'Miad Tarihi',
            'Birim Fiyat',
            'Kdv Oran',
            'Marka',
            'Resim Url',
            'ERP Ürün Kodu',
        ]);

        $this->assertTrue($result['valid']);
        $this->assertContains('name', $result['headers']);
        $this->assertContains('price', $result['headers']);
        $this->assertContains('qty', $result['headers']);
        $this->assertContains('brand', $result['headers']);
        $this->assertContains('image_url', $result['headers']);
        $this->assertContains('sku', $result['headers']);
    }

    public function test_maps_english_and_turkish_synonyms(): void
    {
        $result = $this->mapper->mapHeaders(['başlık', 'fiyat', 'stok', 'marka']);

        $this->assertTrue($result['valid']);
        $this->assertSame('name', $result['headers'][0]);
        $this->assertSame('price', $result['headers'][1]);
        $this->assertSame('qty', $result['headers'][2]);
        $this->assertSame('brand', $result['headers'][3]);
    }

    public function test_rejects_file_without_name_or_price(): void
    {
        $result = $this->mapper->mapHeaders(['Kod', 'Marka', 'Stok']);

        $this->assertFalse($result['valid']);
    }

    public function test_normalizes_turkish_decimal_price(): void
    {
        $this->assertSame('1234.56', $this->mapper->normalizeNumeric('1.234,56'));
        $this->assertSame('99.90', $this->mapper->normalizeNumeric('99,90'));
        $this->assertSame('1500', $this->mapper->normalizeNumeric('1500'));
    }
}
