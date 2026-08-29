<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ChildCategory;
use App\Models\Setting;
use App\Models\SubCategory;
use App\Models\Vendor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImportCategoryResolver
{
    /** @var array<string, array<string, mixed>> */
    private array $cache = [];

    /** @var null|array<int, array<string, mixed>> */
    private ?array $categoryTree = null;

    /** @var array<string, Category> */
    private array $categoryCacheByBrand = [];

    private bool $bulkMode = false;

    private int $bulkAiCallsRemaining = 0;

    private int $bulkSubAiCallsRemaining = 0;

    /** @var array<string, ?SubCategory> */
    private array $subCategoryAiCache = [];

    public function beginBulkImport(int $estimatedRows): void
    {
        $this->bulkMode = $estimatedRows > 30;
        $this->bulkAiCallsRemaining = match (true) {
            $estimatedRows > 1000 => 2,
            $estimatedRows > 200 => 5,
            $estimatedRows > 50 => 10,
            default => 50,
        };
        $this->bulkSubAiCallsRemaining = match (true) {
            $estimatedRows > 1000 => 300,
            $estimatedRows > 200 => 200,
            default => min(150, max(50, $estimatedRows)),
        };
        $this->categoryCacheByBrand = [];
        $this->cache = [];
        $this->subCategoryAiCache = [];
    }

    public function endBulkImport(): void
    {
        $this->bulkMode = false;
        $this->bulkAiCallsRemaining = 0;
        $this->bulkSubAiCallsRemaining = 0;
        $this->subCategoryAiCache = [];
    }

    /**
     * @return array{
     *   category: ?Category,
     *   sub_category: ?SubCategory,
     *   child_category: ?ChildCategory,
     *   brand: ?Brand,
     *   notes: list<string>
     * }
     */
    public function resolve(
        string $productName,
        ?string $categoryInput,
        ?string $subCategoryInput = null,
        ?string $childCategoryInput = null,
        ?string $brandInput = null,
        ?string $description = null,
        ?Vendor $vendor = null
    ): array {
        $notes = [];
        $tree = $this->getCategoryTree();

        $category = $this->resolveCategoryEntity(
            $productName,
            $categoryInput,
            $description,
            $tree,
            $notes,
            $brandInput
        );
        if (! $category) {
            return [
                'category' => null,
                'sub_category' => null,
                'child_category' => null,
                'brand' => null,
                'notes' => $notes,
            ];
        }

        $subCategory = $this->resolveSubCategoryEntity(
            $productName,
            $subCategoryInput,
            $childCategoryInput,
            $category,
            $description,
            $notes
        );

        $childCategory = $this->resolveChildCategoryEntity(
            $subCategoryInput,
            $childCategoryInput,
            $subCategory,
            $notes
        );

        $brand = $this->resolveBrandEntity($brandInput, $notes, $vendor);

        return [
            'category' => $category,
            'sub_category' => $subCategory,
            'child_category' => $childCategory,
            'brand' => $brand,
            'notes' => $notes,
        ];
    }

    public function resolveBrand(?string $brandInput, ?Vendor $vendor = null): ?Brand
    {
        $notes = [];

        return $this->resolveBrandEntity($brandInput, $notes, $vendor);
    }

    /**
     * @param  array<int, array<string, mixed>>  $tree
     */
    private function resolveCategoryEntity(
        string $productName,
        ?string $categoryInput,
        ?string $description,
        array $tree,
        array &$notes,
        ?string $brandInput = null
    ): ?Category {
        $input = trim((string) $categoryInput);
        $brandKey = Str::lower(trim((string) $brandInput));

        if ($brandKey !== '' && isset($this->categoryCacheByBrand[$brandKey])) {
            return $this->categoryCacheByBrand[$brandKey];
        }

        if ($input !== '') {
            $exact = Category::query()->whereRaw('LOWER(name) = ?', [Str::lower($input)])->first();
            if ($exact) {
                return $this->rememberCategoryForBrand($brandKey, $exact);
            }

            $fuzzy = $this->fuzzyCategoryMatch($input, Category::query()->get());
            if ($fuzzy) {
                $notes[] = "Kategori otomatik eşleştirildi: \"{$input}\" → \"{$fuzzy->name}\"";

                return $this->rememberCategoryForBrand($brandKey, $fuzzy);
            }
        }

        if (! $this->bulkMode || $this->bulkAiCallsRemaining > 0) {
            $ai = $this->matchWithAi($productName, $input, null, null, $description, $tree);
            if ($this->bulkMode) {
                $this->bulkAiCallsRemaining = max(0, $this->bulkAiCallsRemaining - 1);
            }
            if ($ai && ! empty($ai['category_id'])) {
                $category = Category::query()->find($ai['category_id']);
                if ($category) {
                    $via = $input !== '' ? "\"{$input}\"" : 'ürün adından';
                    $notes[] = "Kategori AI ile eşleştirildi ({$via}): \"{$category->name}\"";

                    return $this->rememberCategoryForBrand($brandKey, $category);
                }
            }
        }

        $scored = $this->scoreBestCategory($productName, $input, $description, $tree);
        if ($scored) {
            $notes[] = "Kategori anahtar kelime ile eşleştirildi: \"{$scored->name}\"";

            return $this->rememberCategoryForBrand($brandKey, $scored);
        }

        if ($brandKey !== '' && ! empty($this->categoryCacheByBrand)) {
            $fallback = reset($this->categoryCacheByBrand);
            if ($fallback instanceof Category) {
                return $fallback;
            }
        }

        if ($input !== '') {
            $notes[] = "Kategori bulunamadı: {$input}";
        }

        return null;
    }

    private function rememberCategoryForBrand(string $brandKey, Category $category): Category
    {
        if ($brandKey !== '') {
            $this->categoryCacheByBrand[$brandKey] = $category;
        }

        return $category;
    }

    private function resolveSubCategoryEntity(
        string $productName,
        ?string $subInput,
        ?string $childInput,
        Category &$category,
        ?string $description,
        array &$notes
    ): ?SubCategory {
        $candidates = array_values(array_unique(array_filter([
            trim((string) $subInput),
            trim((string) $childInput),
        ], fn (string $value) => $value !== '')));

        foreach ($candidates as $candidate) {
            $matched = $this->matchSubCategoryUnderCategory(
                $productName,
                $candidate,
                $category,
                $description,
                $notes
            );
            if ($matched) {
                return $matched;
            }
        }

        foreach ($candidates as $candidate) {
            $global = $this->findSubCategoryGlobally($candidate);
            if (! $global) {
                continue;
            }

            if ((int) $global->category_id !== (int) $category->id) {
                $correctCategory = Category::query()->find($global->category_id);
                if ($correctCategory) {
                    $category = $correctCategory;
                    $notes[] = "Ana kategori alt kategori eşleşmesine göre düzeltildi: \"{$correctCategory->name}\"";
                }
            }

            $notes[] = "Alt kategori genel arama ile eşleştirildi: \"{$candidate}\" → \"{$global->name}\"";

            return $global;
        }

        if ($candidates === [] && $this->canUseSubCategoryAi()) {
            $aiMatched = $this->matchSubCategoryWithAi($productName, $category, '', $description, $notes);
            if ($aiMatched) {
                return $aiMatched;
            }
        }

        if ($candidates !== []) {
            $notes[] = 'Alt kategori eşleştirilemedi, yalnızca ana kategori kullanıldı: ' . implode(' / ', $candidates);
        }

        return null;
    }

    private function matchSubCategoryUnderCategory(
        string $productName,
        string $input,
        Category $category,
        ?string $description,
        array &$notes
    ): ?SubCategory {
        $exact = SubCategory::query()
            ->where('category_id', $category->id)
            ->whereRaw('LOWER(name) = ?', [Str::lower($input)])
            ->first();
        if ($exact) {
            return $exact;
        }

        $subs = SubCategory::query()->where('category_id', $category->id)->get();
        $fuzzy = $this->fuzzySubCategoryMatch($input, $subs);
        if ($fuzzy) {
            $notes[] = "Alt kategori otomatik eşleştirildi: \"{$input}\" → \"{$fuzzy->name}\"";

            return $fuzzy;
        }

        $wordMatch = $this->scoreBestSubCategoryByWords($input, $subs);
        if ($wordMatch) {
            $notes[] = "Alt kategori kelime benzerliği ile eşleştirildi: \"{$input}\" → \"{$wordMatch->name}\"";

            return $wordMatch;
        }

        $productWordMatch = $this->scoreBestSubCategoryByWords(
            implode(' ', array_filter([$productName, $description ?? ''])),
            $subs
        );
        if ($productWordMatch) {
            $notes[] = "Alt kategori ürün adından eşleştirildi: \"{$productWordMatch->name}\"";

            return $productWordMatch;
        }

        $aiMatched = $this->matchSubCategoryWithAi($productName, $category, $input, $description, $notes);
        if ($aiMatched) {
            return $aiMatched;
        }

        $scored = $this->scoreBestSubCategory($productName, $input, $description, $subs);
        if ($scored) {
            $notes[] = "Alt kategori anahtar kelime ile eşleştirildi: \"{$scored->name}\"";

            return $scored;
        }

        return null;
    }

    private function findSubCategoryGlobally(string $input): ?SubCategory
    {
        $normalized = Str::lower(trim($input));
        if ($normalized === '') {
            return null;
        }

        $exact = SubCategory::query()->whereRaw('LOWER(name) = ?', [$normalized])->first();
        if ($exact) {
            return $exact;
        }

        return $this->fuzzySubCategoryMatch($input, SubCategory::query()->get());
    }

    private function canUseSubCategoryAi(): bool
    {
        return ! $this->bulkMode || $this->bulkSubAiCallsRemaining > 0;
    }

    private function matchSubCategoryWithAi(
        string $productName,
        Category $category,
        string $subInput,
        ?string $description,
        array &$notes
    ): ?SubCategory {
        $cacheKey = $category->id . ':' . $this->normalizeText($subInput !== '' ? $subInput : $productName);
        if (array_key_exists($cacheKey, $this->subCategoryAiCache)) {
            return $this->subCategoryAiCache[$cacheKey];
        }

        if (! $this->canUseSubCategoryAi()) {
            return null;
        }

        $setting = $this->getAiSetting();
        if (! $setting || (! $setting->openai_enabled && ! $setting->claude_enabled)) {
            $this->subCategoryAiCache[$cacheKey] = null;

            return null;
        }

        $tree = collect($this->getCategoryTree())->firstWhere('id', $category->id);
        if (! $tree) {
            return null;
        }

        $subs = $tree['subcategories'] ?? [];
        if ($subs === []) {
            $this->subCategoryAiCache[$cacheKey] = null;

            return null;
        }

        $subList = collect($subs)
            ->map(fn (array $sub) => "- ID {$sub['id']}: {$sub['name']}")
            ->implode("\n");

        $descLine = $description ? "Ürün açıklaması: {$description}" : '';
        $subLine = $subInput !== ''
            ? "Excel'deki alt kategori metni: \"{$subInput}\""
            : 'Excel\'de alt kategori boş; ürün adına göre en uygun alt kategoriyi seç.';

        $prompt = <<<PROMPT
Sen Seyfibaba kuaför/berber pazaryeri için alt kategori eşleştirme uzmanısın.

Ana kategori: "{$category->name}"
Ürün adı: {$productName}
{$subLine}
{$descLine}

Bu ana kategori altındaki alt kategoriler (SADECE bu ID'lerden birini seç):
{$subList}

Görev: Excel metni birebir tutmasa bile anlamca en yakın alt kategoriyi bul.
Türkçe eşanlamlılar, yazım farkları ve kısaltmaları dikkate al.
Gerçekten uygun alt kategori yoksa 0 döndür.

SADECE geçerli JSON:
{"sub_category_id":0}
PROMPT;

        try {
            if ($this->bulkMode) {
                $this->bulkSubAiCallsRemaining = max(0, $this->bulkSubAiCallsRemaining - 1);
            }

            $raw = $this->callAiText($setting, $prompt);
            $parsed = $this->parseJson($raw);
            $subId = (int) ($parsed['sub_category_id'] ?? 0);
            $sub = $subId > 0
                ? SubCategory::query()->where('id', $subId)->where('category_id', $category->id)->first()
                : null;

            $this->subCategoryAiCache[$cacheKey] = $sub;

            if ($sub) {
                $via = $subInput !== '' ? "\"{$subInput}\"" : 'ürün adından';
                $notes[] = "Alt kategori AI ile eşleştirildi ({$via}): \"{$sub->name}\"";

                return $sub;
            }
        } catch (\Throwable $e) {
            Log::warning('Import subcategory AI match failed', [
                'message' => $e->getMessage(),
                'category_id' => $category->id,
                'sub_input' => $subInput,
            ]);
            $this->subCategoryAiCache[$cacheKey] = null;
        }

        return null;
    }

    /**
     * @param  Collection<int, SubCategory>  $subs
     */
    private function scoreBestSubCategoryByWords(string $input, Collection $subs): ?SubCategory
    {
        $inputWords = $this->significantWords($input);
        if ($inputWords === []) {
            return null;
        }

        $best = null;
        $bestScore = 0.0;

        foreach ($subs as $sub) {
            $nameWords = $this->significantWords($sub->name);
            if ($nameWords === []) {
                continue;
            }

            $overlap = count(array_intersect($inputWords, $nameWords));
            if ($overlap === 0) {
                continue;
            }

            $score = $overlap / max(count($inputWords), count($nameWords));
            $minOverlap = count($inputWords) === 1 ? 1 : 2;
            if ($overlap >= $minOverlap && $score > $bestScore) {
                $bestScore = $score;
                $best = $sub;
            }
        }

        return $bestScore >= 0.34 ? $best : null;
    }

    /**
     * @return list<string>
     */
    private function significantWords(string $text): array
    {
        $words = preg_split('/\s+/', $this->normalizeText($text)) ?: [];
        $stopWords = ['ve', 'icin', 'ile', 'the', 'and', 'for'];

        return array_values(array_filter($words, function (string $word) use ($stopWords) {
            return strlen($word) >= 3 && ! in_array($word, $stopWords, true);
        }));
    }

    private function resolveChildCategoryEntity(
        ?string $subInput,
        ?string $childInput,
        ?SubCategory $subCategory,
        array &$notes
    ): ?ChildCategory {
        $input = trim((string) $childInput);
        if ($input === '' || ! $subCategory) {
            return null;
        }

        $exact = ChildCategory::query()
            ->where('sub_category_id', $subCategory->id)
            ->whereRaw('LOWER(name) = ?', [Str::lower($input)])
            ->first();
        if ($exact) {
            return $exact;
        }

        $children = ChildCategory::query()->where('sub_category_id', $subCategory->id)->get();
        $fuzzy = $this->fuzzyGenericMatch($input, $children);
        if ($fuzzy instanceof ChildCategory) {
            $notes[] = "Alt alt kategori otomatik eşleştirildi: \"{$input}\" → \"{$fuzzy->name}\"";

            return $fuzzy;
        }

        $notes[] = "Alt alt kategori boş bırakıldı: {$input}";

        return null;
    }

    private function resolveBrandEntity(?string $brandInput, array &$notes, ?Vendor $vendor = null): ?Brand
    {
        $input = trim((string) $brandInput);
        if ($input === '') {
            return null;
        }

        $exact = Brand::query()->whereRaw('LOWER(name) = ?', [Str::lower($input)])->first();
        if ($exact) {
            return $exact;
        }

        $fuzzy = $this->fuzzyGenericMatch($input, Brand::query()->get());
        if ($fuzzy instanceof Brand) {
            $notes[] = "Marka otomatik eşleştirildi: \"{$input}\" → \"{$fuzzy->name}\"";

            return $fuzzy;
        }

        return $this->createBrand($input, $vendor, $notes);
    }

    private function createBrand(string $name, ?Vendor $vendor, array &$notes): Brand
    {
        $slug = Str::slug($name) ?: 'marka';
        $candidate = $slug;
        $i = 1;

        while (Brand::query()->where('slug', $candidate)->exists()) {
            $candidate = $slug . '-' . $i;
            $i++;
        }

        $brand = Brand::query()->create([
            'name' => $name,
            'slug' => $candidate,
            'logo' => 'uploads/website-images/preview.png',
            'status' => 1,
            'created_by' => $vendor?->id,
            'created_by_type' => $vendor ? Vendor::class : null,
            'is_admin_created' => $vendor === null,
        ]);

        $notes[] = $vendor
            ? "Marka otomatik oluşturuldu (markalarınızda görünür): \"{$name}\""
            : "Marka otomatik oluşturuldu: \"{$name}\"";

        return $brand;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCategoryTree(): array
    {
        if ($this->categoryTree !== null) {
            return $this->categoryTree;
        }

        $this->categoryTree = Category::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (Category $category) {
                $subs = SubCategory::query()
                    ->where('category_id', $category->id)
                    ->orderBy('name')
                    ->get(['id', 'name', 'category_id'])
                    ->map(function (SubCategory $sub) {
                        $children = ChildCategory::query()
                            ->where('sub_category_id', $sub->id)
                            ->orderBy('name')
                            ->get(['id', 'name'])
                            ->map(fn (ChildCategory $c) => ['id' => $c->id, 'name' => $c->name])
                            ->values()
                            ->all();

                        return [
                            'id' => $sub->id,
                            'name' => $sub->name,
                            'child_categories' => $children,
                        ];
                    })
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

        return $this->categoryTree;
    }

    /**
     * @param  array<int, array<string, mixed>>  $tree
     */
    private function matchWithAi(
        string $productName,
        ?string $categoryInput,
        ?string $subCategoryInput,
        ?string $childCategoryInput,
        ?string $description,
        array $tree
    ): ?array {
        $cacheKey = md5(json_encode([
            $productName,
            $categoryInput,
            $subCategoryInput,
            $childCategoryInput,
            $description,
        ]));

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $setting = $this->getAiSetting();
        if (! $setting || (! $setting->openai_enabled && ! $setting->claude_enabled)) {
            return null;
        }

        $categoryJson = json_encode($tree, JSON_UNESCAPED_UNICODE);
        $catLine = $categoryInput ? "Satıcının yazdığı kategori: \"{$categoryInput}\"" : 'Satıcı kategori yazmadı.';
        $subLine = $subCategoryInput ? "Satıcının yazdığı alt kategori: \"{$subCategoryInput}\"" : '';
        $descLine = $description ? "Ürün açıklaması: {$description}" : '';

        $prompt = <<<PROMPT
Sen Seyfibaba berber/kuaför/güzellik salonu ekipmanları pazaryeri için kategori eşleştirme uzmanısın.

Ürün adı: {$productName}
{$catLine}
{$subLine}
{$descLine}

Mevcut kategori ağacı (SADECE bu ID'leri kullan):
{$categoryJson}

Satıcının yazdığı kategori adı birebir eşleşmese bile ürün adına ve bağlama göre EN UYGUN kategori ve alt kategoriyi seç.
Berber koltuğu, kuaför tezgahı, makas, fön makinesi gibi ürünleri doğru gruba yerleştir.

SADECE geçerli JSON döndür:
{"category_id":0,"sub_category_id":0,"child_category_id":0}
Alt kategori yoksa sub_category_id ve child_category_id 0 olsun.
PROMPT;

        try {
            $raw = $this->callAiText($setting, $prompt);
            $parsed = $this->parseJson($raw);
            $this->cache[$cacheKey] = $parsed;

            return $parsed;
        } catch (\Throwable $e) {
            Log::warning('Import category AI match failed', ['message' => $e->getMessage()]);

            return null;
        }
    }

    private function callAiText(Setting $setting, string $prompt): string
    {
        $openaiEnabled = (bool) $setting->openai_enabled;
        $claudeEnabled = (bool) $setting->claude_enabled;
        $apiKey = trim($setting->openai_api_key ?? '');

        if ($openaiEnabled) {
            $endpoint = str_starts_with($apiKey, 'gsk_')
                ? 'https://api.groq.com/openai/v1/chat/completions'
                : 'https://api.openai.com/v1/chat/completions';

            $response = Http::timeout(max(30, (int) ($setting->openai_timeout ?? 30)))
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->post($endpoint, [
                    'model' => $setting->openai_model ?? 'gpt-4o-mini',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'max_tokens' => 500,
                    'temperature' => 0.2,
                ]);

            $data = $response->json();
            if (isset($data['choices'][0]['message']['content'])) {
                return trim($data['choices'][0]['message']['content']);
            }
        }

        if ($claudeEnabled) {
            $response = Http::timeout(max(30, (int) ($setting->claude_timeout ?? 30)))
                ->withHeaders([
                    'x-api-key' => trim($setting->claude_api_key ?? ''),
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json',
                ])->post('https://api.anthropic.com/v1/messages', [
                    'model' => $setting->claude_model ?? 'claude-sonnet-4-5-20250929',
                    'max_tokens' => 500,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                ]);

            $data = $response->json();
            if (isset($data['content'][0]['text'])) {
                return trim($data['content'][0]['text']);
            }
        }

        throw new \RuntimeException('AI yanıt vermedi');
    }

    private function parseJson(string $raw): ?array
    {
        $decoded = json_decode(trim($raw), true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{[\s\S]*\}/', $raw, $m)) {
            $decoded = json_decode($m[0], true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    private function fuzzyCategoryMatch(string $input, Collection $categories): ?Category
    {
        return $this->fuzzyGenericMatch($input, $categories);
    }

    private function fuzzySubCategoryMatch(string $input, Collection $subs): ?SubCategory
    {
        return $this->fuzzyGenericMatch($input, $subs);
    }

    /**
     * @template T of Category|SubCategory|ChildCategory|Brand
     * @param  Collection<int, T>  $items
     * @return T|null
     */
    private function fuzzyGenericMatch(string $input, Collection $items): mixed
    {
        $normalizedInput = $this->normalizeText($input);
        if ($normalizedInput === '') {
            return null;
        }

        $best = null;
        $bestScore = 0;

        foreach ($items as $item) {
            $name = $this->normalizeText($item->name);
            if ($name === $normalizedInput) {
                return $item;
            }

            if (str_contains($name, $normalizedInput) || str_contains($normalizedInput, $name)) {
                similar_text($name, $normalizedInput, $pct);
                if ($pct > $bestScore) {
                    $bestScore = $pct;
                    $best = $item;
                }
            }
        }

        if ($best && $bestScore >= 55) {
            return $best;
        }

        foreach ($items as $item) {
            similar_text($this->normalizeText($item->name), $normalizedInput, $pct);
            if ($pct > $bestScore) {
                $bestScore = $pct;
                $best = $item;
            }
        }

        return $bestScore >= 72 ? $best : null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $tree
     */
    private function scoreBestCategory(
        string $productName,
        string $categoryInput,
        ?string $description,
        array $tree
    ): ?Category {
        $haystack = $this->normalizeText(implode(' ', array_filter([$productName, $categoryInput, $description])));
        $bestId = null;
        $bestScore = 0;

        foreach ($tree as $cat) {
            $score = $this->keywordScore($haystack, $this->normalizeText($cat['name']));
            $score += $this->industryCategoryBoost($haystack, $cat['name']);
            foreach ($cat['subcategories'] ?? [] as $sub) {
                $score += $this->keywordScore($haystack, $this->normalizeText($sub['name'])) * 0.5;
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestId = $cat['id'];
            }
        }

        return $bestScore >= 1.0 ? Category::query()->find($bestId) : null;
    }

    private function industryCategoryBoost(string $haystack, string $categoryName): float
    {
        $barberHints = ['berber', 'koltuk', 'kuaför', 'kuafor', 'makas', 'tarama', 'salon', 'fön', 'fon'];
        foreach ($barberHints as $hint) {
            if (! str_contains($haystack, $hint)) {
                continue;
            }
            $cat = $this->normalizeText($categoryName);
            if (str_contains($cat, 'kuafor') || str_contains($cat, 'ekipman') || str_contains($cat, 'malzeme')) {
                return 2.0;
            }
            break;
        }

        return 0;
    }

    private function scoreBestSubCategory(
        string $productName,
        string $subInput,
        ?string $description,
        Collection $subs
    ): ?SubCategory {
        $haystack = $this->normalizeText(implode(' ', array_filter([$productName, $subInput, $description])));
        $best = null;
        $bestScore = 0;

        foreach ($subs as $sub) {
            $score = $this->keywordScore($haystack, $this->normalizeText($sub->name));
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $sub;
            }
        }

        return $bestScore >= 1.0 ? $best : null;
    }

    private function keywordScore(string $haystack, string $needle): float
    {
        $score = 0.0;
        foreach (preg_split('/\s+/', $needle) as $word) {
            if (strlen($word) >= 3 && str_contains($haystack, $word)) {
                $score += 1.0;
            }
        }

        return $score;
    }

    private function normalizeText(string $text): string
    {
        $text = Str::ascii(mb_strtolower(trim($text)));

        return preg_replace('/[^a-z0-9\s]/', ' ', $text) ?? '';
    }

    private function getAiSetting(): ?Setting
    {
        try {
            return Setting::query()->first();
        } catch (\Throwable) {
            return null;
        }
    }
}
