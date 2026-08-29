<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\CategoryInstallmentService;
use Illuminate\Console\Command;

class TestIyzicoInstallments extends Command
{
    protected $signature = 'iyzico:test-installments
                            {product_id? : Ürün ID (ör. 3072)}
                            {--extra= : Karışık sepet için ikinci ürün ID}';

    protected $description = 'Sandbox testi: Iyzico enabledInstallments ve basket kategori çıktısını gösterir';

    public function handle(CategoryInstallmentService $service): int
    {
        $productId = (int) ($this->argument('product_id') ?: 3072);
        $extraId = $this->option('extra') ? (int) $this->option('extra') : null;

        $product = Product::query()->with(['category'])->find($productId);
        if (! $product) {
            $this->error("Ürün bulunamadı: {$productId}");

            return self::FAILURE;
        }

        $cart = [['product_id' => $product->id, 'qty' => 1]];
        if ($extraId) {
            $cart[] = ['product_id' => $extraId, 'qty' => 1];
        }

        $category = $service->resolveIyzicoCategory($product);
        $enabled = $service->enabledInstallmentsForCart($cart);

        $this->info('=== Iyzico Sandbox Test Çıktısı ===');
        $this->table(
            ['Alan', 'Değer'],
            [
                ['product_id', (string) $product->id],
                ['product_name', (string) $product->name],
                ['category_id', (string) ($product->category_id ?? '-')],
                ['sub_category_id', (string) ($product->sub_category_id ?? '-')],
                ['max_installment (ürün)', (string) $service->maxInstallmentForProduct($product)],
                ['enabledInstallments', json_encode($enabled)],
                ['category_1', $category['category_1']],
                ['category_2', $category['category_2']],
            ]
        );

        if ($extraId) {
            $this->warn('Karışık sepet: en kısıtlayıcı ürünün limiti uygulandı.');
        }

        $this->newLine();
        $this->comment('Tam ödeme testi için: frontend checkout → Iyzico sandbox sayfası');
        $this->comment('Sandbox kart: 5528790000000008 | SKT: 12/30 | CVV: 123');

        return self::SUCCESS;
    }
}
