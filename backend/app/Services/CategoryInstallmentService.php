<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use Illuminate\Support\Str;

/**
 * Iyzico kategori bazlı taksit kuralları.
 *
 * Öncelik: SubCategory.max_installment → Category.max_installment → 1 (tek çekim)
 * SubCategory.max_installment = 0 → ana kategoriye düş
 * Sepet: en kısıtlayıcı ürünün max taksiti tüm ödemeye uygulanır.
 */
class CategoryInstallmentService
{
    /** Iyzico'nun kabul ettiği taksit sayıları (docs: enabledInstallments enum) */
    public const VALID_INSTALLMENTS = [1, 2, 3, 6, 9, 12];

    /** Iyzico onay tablosu → site eşlemesi (berber/kuaför pazaryeri) */
    public const IYZICO_RULES = [
        'gida_kozmetik' => [
            'label' => 'Gıda & Kozmetik',
            'max_installment' => 1,
            'iyzico_category_1' => 'Kozmetik',
            'iyzico_category_2' => 'Kisisel Bakim',
            'site_hint' => 'Kozmetik ana kategorisi ve kozmetik alt kategorileri',
        ],
        'tablet' => [
            'label' => 'Tablet',
            'max_installment' => 6,
            'iyzico_category_1' => 'Elektronik',
            'iyzico_category_2' => 'Tablet',
            'site_hint' => 'Tablet satıyorsanız ilgili alt kategoriye 6 yazın',
        ],
        'telefon' => [
            'label' => 'Telefon',
            'max_installment' => 1,
            'iyzico_category_1' => 'Elektronik',
            'iyzico_category_2' => 'Telefon',
            'site_hint' => 'Telefon satıyorsanız ilgili alt kategoriye 1 yazın',
        ],
        'mobilya' => [
            'label' => 'Mobilya',
            'max_installment' => 12,
            'iyzico_category_1' => 'Mobilya',
            'iyzico_category_2' => 'Ev Mobilyalari',
            'site_hint' => 'Kuaför mobilyası, berber koltuğu, salon mobilyası',
        ],
        'dayanikli_kucuk_ev' => [
            'label' => 'Dayanıklı Tüketim & Küçük Ev Aletleri',
            'max_installment' => 9,
            'iyzico_category_1' => 'Kucuk Ev Aletleri',
            'iyzico_category_2' => 'Kuafor Ekipmanlari',
            'site_hint' => 'Kuaför ekipmanları, malzemeleri, yedek parça vb.',
        ],
    ];

    public function normalizeMaxInstallment(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        $n = (int) $value;
        if ($n <= 1) {
            return 1;
        }

        return min(12, $n);
    }

    public function maxInstallmentForProduct(Product $product): int
    {
        $category = $product->relationLoaded('category')
            ? $product->category
            : Category::query()->find($product->category_id);

        $subCategoryId = (int) ($product->sub_category_id ?? 0);
        if ($subCategoryId > 0) {
            $sub = SubCategory::query()->find($subCategoryId);
            if ($sub && $sub->max_installment !== null) {
                // 0 = ana kategoriden devral
                if ((int) $sub->max_installment !== 0) {
                    return $this->normalizeMaxInstallment($sub->max_installment);
                }
            }
        }

        if ($category && $category->max_installment !== null) {
            return $this->normalizeMaxInstallment($category->max_installment);
        }

        // Iyzico onayı için bilinmeyen kategori → tek çekim (ihtiyatlı)
        return 1;
    }

    /**
     * @param  iterable<int|string, array<string, mixed>|object>  $cartProducts
     * @return list<int>
     */
    public function enabledInstallmentsForCart(iterable $cartProducts): array
    {
        $max = 12;

        foreach ($cartProducts as $cartProduct) {
            $productId = is_array($cartProduct)
                ? ($cartProduct['product_id'] ?? null)
                : ($cartProduct->product_id ?? null);

            if (! $productId) {
                continue;
            }

            $product = Product::query()
                ->with(['category'])
                ->find($productId);

            if (! $product) {
                continue;
            }

            $max = min($max, $this->maxInstallmentForProduct($product));
        }

        $max = max(1, min(12, (int) $max));

        return array_values(array_filter(
            self::VALID_INSTALLMENTS,
            static fn (int $n) => $n <= $max
        ));
    }

    /**
     * Kategori adına göre Iyzico kural anahtarı.
     */
    public function classifyRule(?string $name, ?string $slug, ?Category $parentCategory = null): string
    {
        if ($this->isKozmetikLike($name, $slug)) {
            return 'gida_kozmetik';
        }

        $haystack = $this->normalizeHaystack($name, $slug);

        if (str_contains($haystack, 'tablet')) {
            return 'tablet';
        }

        if (str_contains($haystack, 'telefon') || str_contains($haystack, 'phone')) {
            return 'telefon';
        }

        if ($this->isMobilyaLike($name, $slug, $parentCategory)) {
            return 'mobilya';
        }

        return 'dayanikli_kucuk_ev';
    }

    /**
     * @return array{category_1: string, category_2: string}
     */
    public function resolveIyzicoCategory(Product $product): array
    {
        $category = $product->relationLoaded('category')
            ? $product->category
            : Category::query()->find($product->category_id);

        $sub = null;
        $subCategoryId = (int) ($product->sub_category_id ?? 0);
        if ($subCategoryId > 0) {
            $sub = SubCategory::query()->find($subCategoryId);
        }

        $ruleKey = $this->classifyRule(
            $sub?->name ?? $category?->name,
            $sub?->slug ?? $category?->slug,
            $category
        );

        $rule = self::IYZICO_RULES[$ruleKey];

        return [
            'category_1' => $rule['iyzico_category_1'],
            'category_2' => $rule['iyzico_category_2'],
        ];
    }

    private function isKozmetikLike(?string $name, ?string $slug): bool
    {
        $haystack = $this->normalizeHaystack($name, $slug);

        return str_contains($haystack, 'kozmetik')
            || str_contains($haystack, 'makyaj')
            || str_contains($haystack, 'cilt bakim')
            || str_contains($haystack, 'parfum');
    }

    /**
     * Mobilya ana kategori veya alt isimde "ekipman" olsa bile mobilya kalır.
     */
    private function isMobilyaLike(?string $name, ?string $slug, ?Category $parentCategory = null): bool
    {
        $haystack = $this->normalizeHaystack($name, $slug);

        if (
            str_contains($haystack, 'mobilya')
            || str_contains($haystack, 'koltuk')
            || str_contains($haystack, 'berber koltuk')
            || str_contains($haystack, 'salon mobilya')
        ) {
            return true;
        }

        $parentHaystack = $parentCategory
            ? $this->normalizeHaystack($parentCategory->name, $parentCategory->slug)
            : '';

        // Ana kategori mobilya ise alt kategoride "ekipman" geçse bile Mobilya kuralı
        if ($parentHaystack !== '' && str_contains($parentHaystack, 'mobilya')) {
            return true;
        }

        return false;
    }

    private function normalizeHaystack(?string $name, ?string $slug): string
    {
        return Str::lower(Str::ascii(trim(($name ?? '').' '.($slug ?? ''))));
    }

    /**
     * Admin / seeder için önerilen max_installment değerleri (kategori adına göre).
     *
     * @return array{max_installment: int, rule: string}
     */
    public function suggestedInstallmentsByCategoryName(string $categoryName): array
    {
        $ruleKey = $this->classifyRule($categoryName, null, null);
        $rule = self::IYZICO_RULES[$ruleKey];

        return [
            'max_installment' => $rule['max_installment'],
            'rule' => $ruleKey,
        ];
    }
}
