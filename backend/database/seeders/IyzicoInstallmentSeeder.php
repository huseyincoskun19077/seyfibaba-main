<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Services\CategoryInstallmentService;
use Illuminate\Database\Seeder;

/**
 * Iyzico onaylı kategori bazlı taksit limitleri.
 *
 * Iyzico mail:
 *   - Gıda & Kozmetik          → Tek çekim (max 1)
 *   - Tablet                   → 6 taksit
 *   - Telefon                  → Tek çekim (max 1)
 *   - Mobilya                  → 12 taksit (Kuaför Mobilyaları vb.)
 *   - Dayanıklı Tüketim / Küçük Ev Aletleri → 9 taksit
 *
 * Örnek eşleşmeler (CategoryInstallmentService::classifyRule):
 *   Kuaför Mobilyaları       → 12
 *   Kuaför Malzemeleri       → 9
 *   Kozmetik                 → 1
 *   Kuaför Yedek Parçaları   → 9
 *   Servis ve Boya Arabası   → 9
 */
class IyzicoInstallmentSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(CategoryInstallmentService::class);
        $rows = [];

        Category::query()->orderBy('id')->each(function (Category $category) use ($service, &$rows) {
            $suggestion = $service->suggestedInstallmentsByCategoryName($category->name);
            $category->max_installment = $suggestion['max_installment'];
            $category->save();

            $rows[] = [
                $category->id,
                $category->name,
                $suggestion['max_installment'],
                $suggestion['rule'],
            ];
        });

        $this->command?->info('Iyzico taksit limitleri kategori adına göre güncellendi:');
        $this->command?->table(
            ['ID', 'Kategori', 'Max Taksit', 'Iyzico Kuralı'],
            $rows
        );

        $this->command?->warn('Alt kategori override gerekiyorsa Admin → Taksit Kategori Ayarlarından düzenleyin.');
    }
}
