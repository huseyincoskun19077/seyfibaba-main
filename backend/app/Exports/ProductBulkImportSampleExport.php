<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProductBulkImportSampleExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new ProductBulkImportSampleDataSheet(),
            new ProductBulkImportSampleGuideSheet(),
        ];
    }
}

class ProductBulkImportSampleDataSheet implements FromArray, WithHeadings, WithTitle
{
    public function title(): string
    {
        return 'Urunler';
    }

    public function headings(): array
    {
        return [
            'name',
            'short_name',
            'slug',
            'category',
            'sub_category',
            'child_category',
            'brand',
            'price',
            'offer_price',
            'qty',
            'short_description',
            'long_description',
            'sku',
            'weight',
            'tags',
            'image_url',
        ];
    }

    public function array(): array
    {
        return [
            [
                'Profesyonel Erkek Berber Koltuğu',
                'Berber Koltuğu',
                'profesyonel-erkek-berber-koltugu',
                'Kuaför Ekipmanları',
                'Berber Koltukları',
                '',
                '',
                '12500.00',
                '10999.00',
                '5',
                'Hidrolik pompalı, deri döşemeli profesyonel berber koltuğu',
                '360° dönebilen kafa bölümü, ayarlanabilir yükseklik, kolay temizlenen suni deri kaplama. Yoğun salon kullanımına uygundur.',
                'BK-001',
                '45',
                'berber koltugu,kuaför ekipmanlari,salon mobilyasi',
                'https://picsum.photos/id/26/800/800',
            ],
            [
                'Kuaför Tarama Koltuğu',
                'Tarama Koltuğu',
                'kuafor-tarama-koltugu',
                'Kuaför Ekipmanları',
                'Kuaför Koltukları',
                '',
                '',
                '4500.00',
                '',
                '8',
                'Yıkama ve tarama işlemleri için kuaför koltuğu',
                'Ergonomik sırt desteği, su geçirmez döşeme, ayak dayama aparatı. Kuaför ve güzellik salonları için ideal.',
                'TK-002',
                '28',
                'tarama koltugu,kuaför koltugu,guzellik salonu',
                '',
            ],
            [
                'Profesyonel Berber Makası Seti',
                'Berber Makası',
                'profesyonel-berber-makasi-seti',
                'Kuaför Malzemeleri',
                'Makas ve Aletler',
                '',
                '',
                '890.00',
                '749.00',
                '25',
                'Paslanmaz çelik profesyonel berber makası seti',
                'Düz kesim ve inceltme makası dahil, ergonomik sap, taşıma çantası ile birlikte. Berber ve kuaförler için.',
                'MK-003',
                '0.3',
                'berber makasi,kuaför malzemeleri,sac kesimi',
                'https://picsum.photos/id/37/800/800',
            ],
        ];
    }
}

class ProductBulkImportSampleGuideSheet implements FromArray, WithTitle
{
    public function title(): string
    {
        return 'Nasil Kullanilir';
    }

    public function array(): array
    {
        return [
            ['Seyfibaba Toplu Ürün Yükleme — Berber & Kuaför Örnek Dosya'],
            [''],
            ['Bu dosya berber, kuaför ve güzellik salonu satıcıları için hazırlanmıştır.'],
            [''],
            ['1. "Urunler" sekmesindeki örnek satırları silin veya kendi ürünlerinizle değiştirin.'],
            ['2. name = ürün adı, category = yaklaşık kategori adı (birebir olmasa da AI doğru kategoriye yerleştirir).'],
            ['3. price = satış fiyatı, qty = stok adedi, offer_price = indirimli fiyat (yoksa boş).'],
            ['4. image_url = ürün fotoğrafının internet adresi (https://...).'],
            ['5. Görsel VAR ve geçerli → ürün otomatik YAYINA alınır.'],
            ['6. Görsel YOK veya boş → ürün TASLAK kalır; panelden fotoğraf ekleyince açarsınız.'],
            ['7. Dosyayı .xlsx veya .csv olarak kaydedip "Toplu Excel Yükle" sayfasından yükleyin.'],
            [''],
            ['Zorunlu alanlar: name, category, price, qty'],
            ['Opsiyonel: short_name, slug, sub_category, brand, açıklamalar, sku, weight, tags, image_url'],
            [''],
            ['Örnek satır 2 (Tarama Koltuğu): image_url boş — taslak olarak kaydedilir.'],
            ['Örnek satır 1 (Berber Koltuğu) ve 3 (Makas Seti): image_url dolu — yayına alınır.'],
        ];
    }
}
