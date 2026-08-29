<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\Vendor;
use App\Support\ProductSlug;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class QuickProductService
{
    public function __construct(
        private AiVisionService $aiVision,
        private ProductImageStorage $imageStorage,
        private ImportCategoryResolver $categoryResolver
    ) {}

    /**
     * @return array{product: Product, ai: array<string, mixed>}
     */
    public function create(
        Vendor $seller,
        string $inputName,
        int $quantity,
        float $price,
        ?float $offerPrice,
        UploadedFile $image,
        ?int $brandId = null,
        ?string $brandName = null,
        ?int $selectedCategoryId = null,
        ?int $selectedSubCategoryId = null,
        array $userContent = []
    ): array {
        $categoryTree = $this->buildCategoryTree();
        $validOffer = $offerPrice && $offerPrice > 0 && $offerPrice < $price ? $offerPrice : null;

        try {
            $ai = $this->aiVision->analyzeProduct(
                $image,
                $inputName,
                $price,
                $validOffer,
                $quantity,
                $categoryTree
            );
        } catch (\Throwable $e) {
            Log::warning('Quick product AI failed, using fallback', ['message' => $e->getMessage()]);
            $ai = $this->fallbackContent($inputName, $categoryTree);
        }

        if ($selectedCategoryId) {
            $categoryId = $this->resolveCategoryId($selectedCategoryId, $categoryTree);
            $subCategoryId = $this->resolveSubCategoryId((int) ($selectedSubCategoryId ?? 0), $categoryId);
        } else {
            $categoryId = $this->resolveCategoryId((int) ($ai['category_id'] ?? 0), $categoryTree);
            $subCategoryId = $this->resolveSubCategoryId((int) ($ai['sub_category_id'] ?? 0), $categoryId);

            if ($categoryId === ($categoryTree[0]['id'] ?? null) && ($ai['category_id'] ?? 0) <= 0) {
                $resolved = $this->categoryResolver->resolve(
                    $inputName,
                    null,
                    null,
                    null,
                    $ai['brand_name'] ?? null,
                    $ai['short_description'] ?? $inputName,
                    $seller
                );
                if ($resolved['category']) {
                    $categoryId = $resolved['category']->id;
                    $subCategoryId = $resolved['sub_category']?->id ?? 0;
                }
            }
        }
        $brand = $this->resolveBrand($seller, $brandId, $brandName, $ai['brand_name'] ?? null);

        $uc = $userContent;
        $hasUserDesc = !empty(trim($uc['short_description'] ?? ''));

        $name = $inputName;
        $shortName = trim($uc['short_name'] ?? '') ?: ($ai['short_name'] ?: mb_substr($inputName, 0, 30));
        $slug = $this->uniqueSlug(ProductSlug::normalize($inputName) ?: 'urun');

        $thumbPath = $this->imageStorage->store($image, $inputName);

        $product = new Product();
        $product->vendor_id = $seller->id;
        $product->short_name = $shortName;
        $product->name = $name;
        $product->slug = $slug;
        $product->category_id = $categoryId;
        $product->sub_category_id = $subCategoryId;
        $product->child_category_id = (int) ($uc['child_category_id'] ?? 0);
        $product->brand_id = $brand?->id ?? 0;
        $product->sku = trim($uc['sku'] ?? '');
        $product->price = $price;
        $product->offer_price = $validOffer ?? 0;
        $product->qty = $quantity;
        $product->sale_unit_qty = max(1, (int) ($uc['sale_unit_qty'] ?? 1));
        $product->short_description = $hasUserDesc ? $uc['short_description'] : ($ai['short_description'] ?: $inputName);
        $product->long_description = clean(
            trim($uc['long_description'] ?? '') ?: ($ai['long_description'] ?: '<p>' . e($inputName) . '</p>')
        );
        $product->tags = trim($uc['tags'] ?? '') ?: ($ai['tags'] ?: $inputName);
        $product->status = 1;
        $product->approve_by_admin = 1;
        $product->weight = (float) ($uc['weight'] ?? 0);
        $product->is_undefine = 1;
        $product->is_specification = 0;
        $product->seo_title = trim($uc['seo_title'] ?? '') ?: ($ai['seo_title'] ?: mb_substr($name, 0, 60));
        $product->seo_description = trim($uc['seo_description'] ?? '') ?: ($ai['seo_description'] ?: mb_substr($name, 0, 155));
        $product->thumb_image = $thumbPath;
        $product->new_product = 1;
        $product->save();

        if (!empty($uc['colors']) && is_array($uc['colors'])) {
            app(\App\Services\SimpleProductColorService::class)->sync($product, $uc['colors']);
        }

        // Gallery images
        $galleryFiles = $uc['gallery_images'] ?? [];
        if (is_array($galleryFiles)) {
            foreach ($galleryFiles as $galleryFile) {
                if ($galleryFile instanceof UploadedFile) {
                    $galleryPath = $this->imageStorage->store($galleryFile, $inputName . '-gallery');
                    $product->gallery()->create(['image' => $galleryPath]);
                }
            }
        }

        return ['product' => $product, 'ai' => $ai];
    }

    /**
     * @return array<int, array{id:int,name:string,subcategories:array<int,array{id:int,name:string}>}>
     */
    public function buildCategoryTree(): array
    {
        return Category::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (Category $category) {
                $subs = SubCategory::query()
                    ->where('category_id', $category->id)
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (SubCategory $sub) => ['id' => $sub->id, 'name' => $sub->name])
                    ->values()
                    ->all();

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'subcategories' => $subs,
                ];
            })
            ->values()
            ->all();
    }

    private function uniqueSlug(string $base): string
    {
        $slug = mb_substr($base !== '' ? $base : 'urun', 0, 160) ?: 'urun';
        $candidate = $slug;
        $i = 1;

        while (Product::where('slug', $candidate)->exists()) {
            $candidate = $slug . '-' . $i;
            $i++;
        }

        return $candidate;
    }

    /**
     * @param  array<int, array{id:int,name:string,subcategories:array<int,array{id:int,name:string}>}>  $tree
     */
    private function resolveCategoryId(int $aiCategoryId, array $tree): int
    {
        foreach ($tree as $cat) {
            if ($cat['id'] === $aiCategoryId) {
                return $aiCategoryId;
            }
        }

        $fallback = Category::query()
            ->where('status', 1)
            ->whereRaw('LOWER(name) NOT LIKE ?', ['%kozmetik%'])
            ->orderBy('name')
            ->value('id');

        return $fallback ?: ($tree[0]['id'] ?? 1);
    }

    private function resolveSubCategoryId(int $aiSubId, int $categoryId): int
    {
        if ($aiSubId <= 0) {
            return 0;
        }

        $exists = SubCategory::where('id', $aiSubId)
            ->where('category_id', $categoryId)
            ->exists();

        return $exists ? $aiSubId : 0;
    }

    /**
     * @param  array<int, array{id:int,name:string,subcategories:array<int,array{id:int,name:string}>}>  $tree
     * @return array<string, mixed>
     */
    public function textOnlyFallback(string $inputName): array
    {
        return $this->fallbackContent($inputName, $this->buildCategoryTree());
    }

    private function fallbackContent(string $inputName, array $tree): array
    {
        $resolved = $this->categoryResolver->resolve($inputName, null, null, null, null, $inputName);
        $categoryId = $resolved['category']?->id ?? ($tree[0]['id'] ?? 1);

        return [
            'category_id' => $categoryId,
            'sub_category_id' => $resolved['sub_category']?->id ?? 0,
            'short_name' => mb_substr($inputName, 0, 30),
            'name' => $inputName,
            'short_description' => $inputName . ' — Seyfibaba\'da güvenle alışveriş yapın.',
            'long_description' => '<p>' . e($inputName) . '</p><p>Kaliteli ürün, hızlı kargo ve güvenli alışveriş deneyimi için Seyfibaba\'yı tercih edin.</p>',
            'seo_title' => mb_substr($inputName, 0, 60),
            'seo_description' => mb_substr($inputName . ' — Seyfibaba\'da en uygun fiyatlarla.', 0, 155),
            'tags' => $inputName,
            'brand_name' => '',
        ];
    }

    private function resolveBrand(
        Vendor $seller,
        ?int $brandId,
        ?string $brandName,
        ?string $aiBrandName
    ): ?Brand {
        if ($brandId) {
            $existing = Brand::query()->find($brandId);
            if ($existing) {
                return $existing;
            }
        }

        $name = trim((string) ($brandName ?: $aiBrandName));
        if ($name === '') {
            return null;
        }

        return $this->categoryResolver->resolveBrand($name, $seller);
    }
}
