<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Product;
use App\Models\ShoppingCart;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Sipariş öncesi dinamik Ön Bilgilendirme + Ticari Mesafeli Satış metni.
 * Satıcı / alıcı / ürün satırları canlı veriden üretilir.
 */
class CheckoutLegalContractService
{
    public const PLATFORM_NAME = 'Kuaför Tedarik';
    public const PLATFORM_LEGAL_TITLE = 'HC YAZILIM';
    public const PLATFORM_ADDRESS = 'İstiklal Mahallesi Bülük Sokak No:9 Kat:2 No:208 Serdivan / Sakarya';
    public const PLATFORM_PHONE = '0850 303 50 73';
    public const PLATFORM_EMAIL = 'info@kuafortedarik.com';
    public const PLATFORM_URL = 'https://kuafortedarik.com';

    /**
     * @param  array{
     *   shipping_address_id?:int|null,
     *   billing_address_id?:int|null,
     *   shipping_address?:array|null,
     *   billing_address?:array|null,
     *   shipping_charge?:float|int|null,
     *   payment_method?:string|null,
     *   items?:array|null,
     *   user_id?:int|null
     * }  $input
     * @return array{pre_information:array,distance_sales:array}
     */
    public function build(array $input): array
    {
        $buyer = $this->resolveBuyer($input);
        $lines = $this->resolveLines($input);
        $shippingCharge = max(0, (float) ($input['shipping_charge'] ?? 0));
        $paymentMethod = trim((string) ($input['payment_method'] ?? 'Kredi / banka kartı'));
        $orderDate = Carbon::now('Europe/Istanbul')->format('d.m.Y H:i:s');

        $bySeller = $lines->groupBy(fn ($row) => (int) ($row['vendor_id'] ?? 0));
        $blocks = [];
        foreach ($bySeller as $vendorId => $sellerLines) {
            $seller = $this->resolveSeller((int) $vendorId);
            $blocks[] = $this->renderSellerBlock(
                $seller,
                $buyer,
                $sellerLines->values()->all(),
                $shippingCharge / max(1, $bySeller->count()),
                $paymentMethod,
                $orderDate
            );
        }

        if ($blocks === []) {
            $blocks[] = '<p>Sepetiniz boş olduğu için sözleşme önizlemesi oluşturulamadı.</p>';
        }

        $html = implode("\n<hr style=\"margin:24px 0;border:0;border-top:1px solid #e2e8f0\" />\n", $blocks);

        return [
            'pre_information' => [
                'slug' => 'pre-information',
                'title' => 'Ön Bilgilendirme Koşulları',
                'html' => $html,
            ],
            'distance_sales' => [
                'slug' => 'distance-sales',
                'title' => 'Ticari Nitelikli Mesafeli Satış Sözleşmesi',
                'html' => $html,
            ],
        ];
    }

    private function resolveBuyer(array $input): array
    {
        $shipping = $this->addressPayload(
            $input['shipping_address_id'] ?? null,
            $input['shipping_address'] ?? null
        );
        $billing = $this->addressPayload(
            $input['billing_address_id'] ?? null,
            $input['billing_address'] ?? null
        );
        if ($billing['name'] === '') {
            $billing = $shipping;
        }

        $title = trim((string) ($billing['company'] ?: $billing['name']));
        $tax = trim((string) ($billing['tax_number'] ?: $billing['identity'] ?: ''));

        return [
            'title' => $title !== '' ? $title : 'Alıcı',
            'contact_name' => $billing['name'] !== '' ? $billing['name'] : $shipping['name'],
            'tax' => $tax,
            'address' => $this->formatAddressLine($billing),
            'shipping_address' => $this->formatAddressLine($shipping),
            'shipping_name' => $shipping['name'] !== '' ? $shipping['name'] : $billing['name'],
            'phone' => $billing['phone'] !== '' ? $billing['phone'] : $shipping['phone'],
            'email' => $billing['email'] !== '' ? $billing['email'] : $shipping['email'],
            'invoice_address' => $this->formatAddressLine($billing),
        ];
    }

    private function addressPayload($id, $raw): array
    {
        if ($id) {
            $address = Address::with(['country', 'countryState', 'city'])->find((int) $id);
            if ($address) {
                return [
                    'name' => trim((string) ($address->name ?? '')),
                    'phone' => trim((string) ($address->phone ?? '')),
                    'email' => trim((string) ($address->email ?? '')),
                    'address' => trim((string) ($address->address ?? '')),
                    'company' => trim((string) ($address->company_name ?? $address->company ?? '')),
                    'tax_number' => trim((string) ($address->tax_number ?? '')),
                    'identity' => trim((string) ($address->tc_identity ?? $address->identity_number ?? '')),
                    'city' => trim((string) (optional($address->city)->name ?? '')),
                    'state' => trim((string) (optional($address->countryState)->name ?? '')),
                    'country' => trim((string) (optional($address->country)->name ?? 'Türkiye')),
                ];
            }
        }

        if (is_array($raw)) {
            return [
                'name' => trim((string) ($raw['name'] ?? '')),
                'phone' => trim((string) ($raw['phone'] ?? '')),
                'email' => trim((string) ($raw['email'] ?? '')),
                'address' => trim((string) ($raw['address'] ?? '')),
                'company' => trim((string) ($raw['company_name'] ?? $raw['company'] ?? '')),
                'tax_number' => trim((string) ($raw['tax_number'] ?? '')),
                'identity' => trim((string) ($raw['tc_identity'] ?? $raw['identity_number'] ?? '')),
                'city' => trim((string) ($raw['city'] ?? $raw['city_name'] ?? '')),
                'state' => trim((string) ($raw['state'] ?? $raw['state_name'] ?? '')),
                'country' => trim((string) ($raw['country'] ?? $raw['country_name'] ?? 'Türkiye')),
            ];
        }

        return [
            'name' => '', 'phone' => '', 'email' => '', 'address' => '',
            'company' => '', 'tax_number' => '', 'identity' => '',
            'city' => '', 'state' => '', 'country' => 'Türkiye',
        ];
    }

    private function formatAddressLine(array $a): string
    {
        $parts = array_filter([
            $a['country'] ?? '',
            $a['state'] ?? '',
            $a['city'] ?? '',
            $a['address'] ?? '',
        ], fn ($v) => trim((string) $v) !== '');

        return $parts !== [] ? implode(', ', $parts) : '—';
    }

    private function resolveLines(array $input): Collection
    {
        $userId = (int) ($input['user_id'] ?? 0);
        if ($userId > 0 && empty($input['items'])) {
            $cart = ShoppingCart::with(['product', 'variants.variantItem'])
                ->where('user_id', $userId)
                ->get();
            $priceService = app(CartPriceService::class);

            return $cart->map(function ($row) use ($priceService) {
                $product = $row->product;
                if (! $product) {
                    return null;
                }
                // İlişki select kısıtlı olabilir; tam alanlar için yeniden yükle
                $full = Product::query()->find($product->id) ?: $product;
                $qty = max(1, (int) $row->qty);
                $unit = $priceService->resolveUnitPriceFromCartLine($full, $row);
                $variantLabels = [];
                foreach ($row->variants ?? [] as $v) {
                    $item = $v->variantItem;
                    if (! $item) {
                        continue;
                    }
                    $g = trim((string) ($item->product_variant_name ?? 'Seçenek'));
                    $variantLabels[] = $g.': '.$item->name;
                }

                return [
                    'vendor_id' => (int) $full->vendor_id,
                    'name' => (string) $full->name,
                    'barcode' => (string) ($full->barcode ?? $full->sku ?? ''),
                    'sale_unit_qty' => max(1, (int) ($full->sale_unit_qty ?? 1)),
                    'variants' => implode(', ', $variantLabels),
                    'qty' => $qty,
                    'unit_price' => $unit,
                    'line_total' => $unit * $qty,
                ];
            })->filter()->values();
        }

        $items = is_array($input['items'] ?? null) ? $input['items'] : [];
        $out = collect();
        $priceService = app(CartPriceService::class);
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $productId = (int) ($item['product_id'] ?? 0);
            $product = Product::query()->find($productId);
            if (! $product) {
                continue;
            }
            $qty = max(1, (int) ($item['qty'] ?? 1));
            $variantItemIds = [];
            $variantLabels = [];
            if (! empty($item['variant_item_ids']) && is_array($item['variant_item_ids'])) {
                $variantItemIds = array_map('intval', $item['variant_item_ids']);
            }
            if (! empty($item['variants']) && is_array($item['variants'])) {
                foreach ($item['variants'] as $v) {
                    if (! is_array($v)) {
                        continue;
                    }
                    $vid = (int) ($v['variant_item_id'] ?? 0);
                    if ($vid > 0) {
                        $variantItemIds[] = $vid;
                    }
                    $g = trim((string) ($v['variant_name'] ?? $v['name'] ?? 'Seçenek'));
                    $val = trim((string) ($v['variant_value'] ?? $v['value'] ?? ''));
                    $variantLabels[] = $val !== '' ? "$g: $val" : $g;
                }
            }
            $unit = isset($item['unit_price'])
                ? (float) $item['unit_price']
                : $priceService->resolveUnitPrice($product, array_values(array_unique($variantItemIds)));

            $out->push([
                'vendor_id' => (int) $product->vendor_id,
                'name' => (string) $product->name,
                'barcode' => (string) ($product->barcode ?? $product->sku ?? ''),
                'sale_unit_qty' => max(1, (int) ($product->sale_unit_qty ?? 1)),
                'variants' => implode(', ', $variantLabels),
                'qty' => $qty,
                'unit_price' => $unit,
                'line_total' => $unit * $qty,
            ]);
        }

        return $out->values();
    }

    private function resolveSeller(int $vendorId): array
    {
        $vendor = $vendorId > 0 ? Vendor::query()->find($vendorId) : null;
        if (! $vendor) {
            return [
                'title' => self::PLATFORM_LEGAL_TITLE,
                'address' => self::PLATFORM_ADDRESS,
                'phone' => self::PLATFORM_PHONE,
                'email' => self::PLATFORM_EMAIL,
                'mersis' => '',
                'tax_number' => '',
            ];
        }

        $title = trim((string) ($vendor->legal_company_title ?: $vendor->shop_name ?: 'Satıcı'));
        $cols = Schema::getColumnListing('vendors');

        return [
            'title' => $title,
            'address' => trim((string) ($vendor->address ?? '')) ?: '—',
            'phone' => trim((string) ($vendor->phone ?? '')) ?: '—',
            'email' => trim((string) ($vendor->email ?? '')) ?: '—',
            'mersis' => in_array('mersis_number', $cols, true)
                ? trim((string) ($vendor->mersis_number ?? ''))
                : '',
            'tax_number' => trim((string) ($vendor->tax_number ?? '')),
        ];
    }

    private function renderSellerBlock(
        array $seller,
        array $buyer,
        array $lines,
        float $shippingShare,
        string $paymentMethod,
        string $orderDate
    ): string {
        $subtotal = 0.0;
        foreach ($lines as $line) {
            $subtotal += (float) $line['line_total'];
        }
        $grand = $subtotal + $shippingShare;
        $vatApprox = round($grand - ($grand / 1.20), 2);

        $rowsHtml = '';
        foreach ($lines as $line) {
            $meta = [];
            $pack = (int) ($line['sale_unit_qty'] ?? 1);
            $meta[] = $pack > 1 ? 'Paket (içindeki ürün sayısı: '.$pack.')' : 'Adet (içindeki ürün sayısı: 1)';
            if (! empty($line['barcode'])) {
                $meta[] = 'Barkod: '.e($line['barcode']);
            }
            if (! empty($line['variants'])) {
                $meta[] = e($line['variants']);
            }
            $rowsHtml .= '<tr>'
                .'<td style="padding:8px;border:1px solid #cbd5e1;vertical-align:top"><strong>'.e($line['name']).'</strong><br><span style="font-size:12px;color:#64748b">'.implode('<br>', $meta).'</span></td>'
                .'<td style="padding:8px;border:1px solid #cbd5e1;text-align:right">'.$this->money($line['unit_price']).'</td>'
                .'<td style="padding:8px;border:1px solid #cbd5e1;text-align:center">'.(int) $line['qty'].'</td>'
                .'<td style="padding:8px;border:1px solid #cbd5e1;text-align:right">'.$this->money($line['line_total']).'</td>'
                .'</tr>';
        }

        $platform = e(self::PLATFORM_NAME);
        $platformLegal = e(self::PLATFORM_LEGAL_TITLE);

        return <<<HTML
<section style="font-size:14px;line-height:1.55;color:#0f172a">
  <h3 style="margin:0 0 12px;font-size:16px">Ön Bilgilendirme Koşullarının Konusu</h3>
  <p><strong>1.1.</strong> İşbu Ön Bilgilendirme Koşulları; platform üzerinde satışa arz edilen mal ve/veya hizmetler bakımından siparişe konu ürünü satışa sunan SATICI ile web sitesi / mobil uygulama üzerinden ticari amaçla alışveriş yapan ALICI arasında akdedilecek Ticari Nitelikli Mesafeli Satış Sözleşmesi öncesinde ALICI'nın bilgilendirilmesi amacıyla düzenlenmiştir. İşbu metin, tüketici işlemlerine değil; tacir, tacir gibi sorumlu veya ticari amaçla hareket eden gerçek ve tüzel kişilere yöneliktir.</p>
  <p><strong>1.2.</strong> {$platformLegal} ({$platform}), işbu işlemde elektronik ticaret aracı hizmet sağlayıcı sıfatıyla platformu işleten aracı hizmet sağlayıcıdır. Ürüne ilişkin satış sözleşmesinin tarafları SATICI ile ALICI olup, {$platform} yalnızca platform altyapısını sağlayan aracı hizmet sağlayıcı konumundadır.</p>
  <p><strong>1.3.</strong> Platform üzerinde yer alan Kullanım Koşulları, Üyelik Sözleşmesi ve diğer platform metinleri; işbu koşullara aykırı olmadığı ölçüde Alıcı bakımından da geçerlidir. Alıcı, tüketicilere tanınan özel hükümlerin kendisi için geçerli olmadığını bildiğini ve ticari iş kapsamında hareket ettiğini kabul eder.</p>

  <h4 style="margin:18px 0 8px">Satıcı Bilgileri</h4>
  <p>Ticaret Ünvanı: {$this->e($seller['title'])}<br>
  Adres: {$this->e($seller['address'])}<br>
  Telefon: {$this->e($seller['phone'])}<br>
  E-Posta: {$this->e($seller['email'])}<br>
  Mersis Numarası: {$this->e($seller['mersis'] ?: '—')}<br>
  Vergi Numarası: {$this->e($seller['tax_number'] ?: '—')}</p>

  <h4 style="margin:18px 0 8px">Alıcı Bilgileri</h4>
  <p>Ticaret Ünvanı: {$this->e($buyer['title'])}<br>
  Ad Soyad (Yetkili Kişi): {$this->e($buyer['contact_name'])}<br>
  Vergi Dairesi ve Numarası: {$this->e($buyer['tax'] ?: '—')}<br>
  Adres: {$this->e($buyer['address'])}<br>
  Telefon: {$this->e($buyer['phone'])}<br>
  E-Posta: {$this->e($buyer['email'])}</p>

  <h4 style="margin:18px 0 8px">4. ÜRÜN / HİZMET BİLGİLERİ</h4>
  <p><strong>4.1.</strong> Ürünün temel özellikleri ürün sayfasında, sipariş özetinde ve aşağıdaki tabloda yer almaktadır.</p>
  <p><strong>4.2.</strong> Listelenen fiyatlar satış fiyatıdır. Güncelleme yapılana kadar geçerlidir.</p>
  <p><strong>4.3–4.4.</strong> Sözleşme konusu malın vergiler dâhil satış fiyatı:</p>
  <table style="width:100%;border-collapse:collapse;margin:12px 0;font-size:13px">
    <thead>
      <tr style="background:#f1f5f9">
        <th style="padding:8px;border:1px solid #cbd5e1;text-align:left">ÜRÜN ADI</th>
        <th style="padding:8px;border:1px solid #cbd5e1;text-align:right">FİYAT</th>
        <th style="padding:8px;border:1px solid #cbd5e1;text-align:center">ADET</th>
        <th style="padding:8px;border:1px solid #cbd5e1;text-align:right">TOPLAM</th>
      </tr>
    </thead>
    <tbody>
      {$rowsHtml}
      <tr><td colspan="3" style="padding:8px;border:1px solid #cbd5e1;text-align:right">ARA TOPLAM</td><td style="padding:8px;border:1px solid #cbd5e1;text-align:right">{$this->money($subtotal)}</td></tr>
      <tr><td colspan="3" style="padding:8px;border:1px solid #cbd5e1;text-align:right">KARGO</td><td style="padding:8px;border:1px solid #cbd5e1;text-align:right">{$this->money($shippingShare)}</td></tr>
      <tr><td colspan="3" style="padding:8px;border:1px solid #cbd5e1;text-align:right">KDV (yaklaşık, fiyata dahil)</td><td style="padding:8px;border:1px solid #cbd5e1;text-align:right">{$this->money($vatApprox)}</td></tr>
      <tr><td colspan="3" style="padding:8px;border:1px solid #cbd5e1;text-align:right"><strong>GENEL TOPLAM</strong></td><td style="padding:8px;border:1px solid #cbd5e1;text-align:right"><strong>{$this->money($grand)}</strong></td></tr>
    </tbody>
  </table>
  <p>Teslimat Adresi: {$this->e($buyer['shipping_address'])}<br>
  Teslim Edilecek Kişi: {$this->e($buyer['shipping_name'])}<br>
  Fatura Adresi: {$this->e($buyer['invoice_address'])}<br>
  Sipariş Tarihi: {$this->e($orderDate)}<br>
  Teslim Şekli: Kargo<br>
  Ödeme Şekli: {$this->e($paymentMethod)}</p>

  <p><strong>4.5.</strong> Kargo ücreti ve diğer ek ücretler ALICI tarafından ödenir.</p>
  <p><strong>4.6. Teslimat:</strong> Sipariş onayını takiben yurt içi siparişlerde makul süre içinde, en geç 30 gün içerisinde teslim edilir. Mücbir sebep hallerinde süre uzayabilir.</p>
  <p><strong>4.7.</strong> Ücretsiz kargo kampanyası hariç kargo bedeli sipariş toplamına eklenir.</p>

  <h4 style="margin:18px 0 8px">5. Ödeme Bilgileri</h4>
  <p><strong>5.1.</strong> Ödeme; kredi kartı, banka kartı, ticari kart, havale/EFT veya Platform'da sunulan diğer yöntemlerle yapılabilir. Kartlı işlemlerde 3D Secure uygulanabilir.</p>
  <p><strong>5.2.</strong> Alıcı, kullandığı ödeme aracında yasal kullanım hakkına sahip olduğunu; yetkisiz kullanım kaynaklı tüm zararlardan sorumlu olacağını kabul eder.</p>
  <p><strong>5.3.</strong> Sipariş, ödeme, 3D Secure, IP/oturum, fatura ve kargo kayıtları harcama itirazı / chargeback süreçlerinde delil olarak kullanılabilir.</p>

  <h4 style="margin:18px 0 8px">6. Teslimat Bilgileri</h4>
  <p><strong>6.1–6.4.</strong> Ürünler bildirilen teslimat adresinde Alıcı'ya veya adreste fiilen teslim alan kişiye teslim edilebilir. Teslim sırasında ayıp incelemesi yapılmalı; hasar varsa tutanak tutulmalıdır.</p>

  <h4 style="margin:18px 0 8px">7. Cayma Hakkı</h4>
  <p><strong>7.1.</strong> İşbu sözleşme B2B / ticari iş kapsamındadır; 6502 sayılı Kanun anlamında tüketici cayma hakkı uygulanmaz.</p>

  <h4 style="margin:18px 0 8px">8–9. Ayıplı Ürün, İade ve Değişim</h4>
  <p>Açık ayıplar teslimde veya 2 gün içinde; gizli ayıplar 8 gün içinde SATICI'ya bildirilmelidir. İade ancak SATICI onayı ve (tüzel kişi faturalarında) iade faturası ile tamamlanır.</p>

  <h4 style="margin:18px 0 8px">10–14. Diğer Hükümler</h4>
  <p>Garanti ve satış sonrası destek SATICI koşullarına tabidir. Kişisel veriler 6698 sayılı KVKK kapsamında işlenir. Mücbir sebep halinde tarafların sorumluluğu sınırlıdır. Uyuşmazlıklarda Türkiye Cumhuriyeti hukuku ve Sakarya mahkemeleri / icra daireleri yetkilidir. İşbu form elektronik ortamda düzenlenmiş olup yazılı sözleşme yerine geçer.</p>

  <p><strong>DİJİTAL ONAY VE HUKUKİ KAYITLAR:</strong> Taraflar, işbu metnin elektronik onay ile kurulacağını; onay tarih/saat, IP, oturum ve işlem kayıtlarının {$platform} tarafından saklanacağını ve uyuşmazlıklarda delil olarak kullanılabileceğini kabul eder.</p>
</section>
HTML;
    }

    private function money($amount): string
    {
        return '₺'.number_format((float) $amount, 2, ',', '.');
    }

    private function e(?string $value): string
    {
        return e((string) ($value ?? ''));
    }
}
