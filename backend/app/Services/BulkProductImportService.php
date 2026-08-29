<?php

namespace App\Services;

use App\Models\BulkImport;
use App\Models\Product;
use App\Models\Vendor;
use App\Jobs\ProcessBulkImportJob;
use App\Support\ProductImageUrl;
use App\Support\ProductSlug;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class BulkProductImportService
{
    private const TEMPLATE_HEADERS = [
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

    public function __construct(
        private ProductImageStorage $imageStorage,
        private ImportCategoryResolver $categoryResolver,
        private ImportColumnMapper $columnMapper
    ) {}

    public function createImportRecord(int $userId, string $userType, UploadedFile $file): BulkImport
    {
        $storedFilePath = $file->storeAs(
            'private/bulk-imports/' . $userType . '/' . $userId,
            now()->format('YmdHis') . '-' . Str::uuid() . '.' . $file->getClientOriginalExtension(),
            'local'
        );

        return BulkImport::query()->create([
            'user_id' => $userId,
            'user_type' => $userType,
            'file_path' => $storedFilePath,
            'original_name' => $file->getClientOriginalName(),
            'status' => 'pending',
        ]);
    }

    public function queueProcess(BulkImport $bulkImport, ?Vendor $vendor = null): BulkImport
    {
        $job = new ProcessBulkImportJob($bulkImport->id, $vendor?->id);

        if (app()->runningUnitTests()) {
            $job->handle($this);

            return $bulkImport->fresh();
        }

        ProcessBulkImportJob::dispatch($bulkImport->id, $vendor?->id)->afterResponse();

        return $bulkImport;
    }

    public function failStaleProcessingImports(int $userId, string $userType, int $minutes = 30): void
    {
        BulkImport::query()
            ->where('user_id', $userId)
            ->where('user_type', $userType)
            ->where('status', 'processing')
            ->where('started_at', '<', now()->subMinutes($minutes))
            ->update([
                'status' => 'failed',
                'completed_at' => now(),
            ]);
    }

    public function processingMessage(BulkImport $bulkImport): string
    {
        return 'Dosyanız yüklendi. ' . ($bulkImport->original_name ?? 'Import')
            . ' arka planda işleniyor — birkaç dakika sonra «Son Yüklemeler» tablosundan durumu kontrol edin.';
    }

    public function process(BulkImport $bulkImport, ?Vendor $vendor = null): BulkImport
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '512M');

        $bulkImport->update([
            'status' => 'processing',
            'started_at' => now(),
            'error_log' => [],
        ]);

        $fileAbsolutePath = Storage::disk('local')->path($bulkImport->file_path);
        $worksheets = Excel::toArray([], $fileAbsolutePath);
        $rows = $worksheets[0] ?? [];

        if (count($rows) < 2) {
            return $this->failImport($bulkImport, [[
                'row' => 0,
                'message' => 'Dosyada başlık satırı ve en az bir ürün satırı olmalı.',
            ]]);
        }

        $rawHeaderRow = array_shift($rows);
        $sampleRow = $rows[0] ?? null;
        $headerMapping = $this->columnMapper->mapHeaders($rawHeaderRow, $sampleRow);

        if (! $headerMapping['valid']) {
            return $this->failImport($bulkImport, [[
                'row' => 1,
                'message' => 'Geçersiz dosya. Ürün adı ve fiyat sütunları bulunamadı. Başlıklar: name/ürün adı/başlık ve price/fiyat/birim fiyat olabilir.',
            ]]);
        }

        $headers = $headerMapping['headers'];
        $errorLog = collect($headerMapping['notes'])
            ->map(fn ($note) => ['row' => 1, 'type' => 'info', 'message' => $note])
            ->all();

        $estimatedRows = count($rows);
        $this->categoryResolver->beginBulkImport($estimatedRows);

        $isSellerImport = $vendor !== null;
        $publishedCount = 0;
        $draftCount = 0;
        $processedRows = 0;
        $successCount = 0;
        $aiMatchCount = 0;

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $processedRows++;

            $normalizedRow = $this->mapRow($headers, $row);
            if ($this->isEmptyRow($normalizedRow)) {
                continue;
            }

            $normalizedRow['price'] = $this->columnMapper->normalizeNumeric($normalizedRow['price']);
            if ($normalizedRow['offer_price'] !== null && $normalizedRow['offer_price'] !== '') {
                $normalizedRow['offer_price'] = $this->columnMapper->normalizeNumeric($normalizedRow['offer_price']);
            }
            if ($normalizedRow['qty'] === null || $normalizedRow['qty'] === '') {
                $normalizedRow['qty'] = 1;
            }

            $validationError = $this->validateRow($normalizedRow, $isSellerImport);
            if ($validationError) {
                $errorLog[] = ['row' => $rowNumber, 'message' => $validationError];
                continue;
            }

            $categoryMatch = $this->categoryResolver->resolve(
                $normalizedRow['name'],
                $normalizedRow['category'] ?: null,
                $normalizedRow['sub_category'] ?? null,
                $normalizedRow['child_category'] ?? null,
                $normalizedRow['brand'] ?? null,
                $normalizedRow['short_description'] ?? null,
                $vendor
            );

            $category = $categoryMatch['category'];
            if (! $category) {
                $errorLog[] = [
                    'row' => $rowNumber,
                    'message' => 'Kategori eşleştirilemedi: ' . ($normalizedRow['category'] ?: $normalizedRow['name']),
                ];
                continue;
            }

            foreach ($categoryMatch['notes'] as $note) {
                if ($estimatedRows <= 100 || $processedRows <= 15) {
                    $errorLog[] = ['row' => $rowNumber, 'type' => 'info', 'message' => $note];
                }
                if (str_contains($note, 'AI ile') || str_contains($note, 'otomatik eşleştirildi') || str_contains($note, 'anahtar kelime ile') || str_contains($note, 'Sütun eşleştirildi') || str_contains($note, 'AI sütun')) {
                    $aiMatchCount++;
                }
            }

            $subCategory = $categoryMatch['sub_category'];
            $childCategory = $categoryMatch['child_category'];
            $brand = $categoryMatch['brand'];

            $productName = $normalizedRow['name'];
            $normalizedSlug = ProductSlug::normalize($normalizedRow['slug'] ?: $productName);
            $shortName = $normalizedRow['short_name'] ?: mb_substr($productName, 0, 30);

            $product = Product::query()
                ->when($vendor, fn ($query) => $query->where('vendor_id', $vendor->id))
                ->where(function ($query) use ($normalizedRow, $normalizedSlug) {
                    $query->where('slug', $normalizedSlug);

                    if (! empty($normalizedRow['sku'])) {
                        $query->orWhere('sku', $normalizedRow['sku']);
                    }
                })
                ->first();

            if (! $product) {
                $product = new Product();
                $product->vendor_id = $vendor?->id ?? 0;
            }

            $thumbImage = null;
            $imageUrl = trim((string) ($normalizedRow['image_url'] ?? ''));

            if (ProductImageUrl::hasImage($product->thumb_image ?? null)) {
                $thumbImage = $product->thumb_image;
            } elseif ($imageUrl !== '') {
                $externalUrl = ProductImageUrl::normalizeForStorage($imageUrl);
                if ($externalUrl) {
                    $thumbImage = $externalUrl;
                } else {
                    $thumbImage = $this->imageStorage->storeFromUrl($imageUrl, $shortName ?: 'product', 15);
                    if (! $thumbImage) {
                        $errorLog[] = [
                            'row' => $rowNumber,
                            'message' => 'Görsel indirilemedi — ürün taslak olarak kaydedildi. URL: ' . $imageUrl,
                        ];
                    }
                }
            }

            $canPublish = ProductImageUrl::hasImage($thumbImage);

            $product->short_name = $shortName;
            $product->name = $productName;
            $product->slug = $this->uniqueSlug($normalizedSlug, $product->id);
            $product->category_id = $category->id;
            $product->sub_category_id = $subCategory?->id ?? 0;
            $product->child_category_id = $childCategory?->id ?? 0;
            $product->brand_id = $brand?->id ?? 0;
            $product->price = (float) $normalizedRow['price'];
            $product->offer_price = $normalizedRow['offer_price'] !== '' && $normalizedRow['offer_price'] !== null
                ? (float) $normalizedRow['offer_price']
                : 0;
            $product->qty = (int) $normalizedRow['qty'];
            $product->short_description = $normalizedRow['short_description'] ?: $productName;
            $product->long_description = $normalizedRow['long_description'] ?: '<p>' . e($productName) . '</p>';
            $product->sku = $normalizedRow['sku'] ?: '';
            $product->weight = ($normalizedRow['weight'] !== '' && $normalizedRow['weight'] !== null)
                ? $normalizedRow['weight']
                : 0;
            $product->tags = $normalizedRow['tags'] ?: $productName;
            $product->is_undefine = 1;
            $product->is_specification = 0;
            $product->seo_title = $productName;
            $product->seo_description = mb_substr($productName, 0, 155);
            $product->thumb_image = $thumbImage ?? ($product->thumb_image ?: '');

            if ($isSellerImport) {
                $product->status = $canPublish ? 1 : 0;
                $product->approve_by_admin = 1;
            } else {
                $statusFromFile = (int) ($normalizedRow['status'] ?? 1);
                $product->status = $canPublish ? ($statusFromFile === 1 ? 1 : 0) : 0;
                $product->approve_by_admin = $canPublish ? 1 : 0;
            }

            $product->save();
            $successCount++;

            if ($canPublish) {
                $publishedCount++;
            } else {
                $draftCount++;
            }

            if ($processedRows % 100 === 0) {
                $bulkImport->update([
                    'processed_rows' => $processedRows,
                    'success_count' => $successCount,
                    'error_count' => count(array_filter($errorLog, fn ($e) => ($e['type'] ?? '') !== 'info')),
                ]);
            }
        }

        $this->categoryResolver->endBulkImport();

        $dataRows = max($processedRows, 0);
        $realErrors = array_filter($errorLog, fn ($e) => ($e['type'] ?? '') !== 'info');
        $bulkImport->update([
            'total_rows' => $dataRows,
            'processed_rows' => $processedRows,
            'success_count' => $successCount,
            'error_count' => count($realErrors),
            'status' => $successCount === 0 && count($errorLog) > 0 ? 'failed' : 'completed',
            'error_log' => array_merge(
                $errorLog,
                [[
                    '_summary' => true,
                    'published_count' => $publishedCount,
                    'draft_count' => $draftCount,
                    'success_count' => $successCount,
                    'error_count' => count($realErrors),
                    'ai_match_count' => $aiMatchCount,
                ]]
            ),
            'completed_at' => now(),
        ]);

        return $bulkImport->fresh();
    }

    public function templateHeaders(): array
    {
        return self::TEMPLATE_HEADERS;
    }

    public function templateCsv(): string
    {
        $sampleRow = [
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
            'Hidrolik pompalı profesyonel berber koltuğu',
            'Deri döşeme, 360° dönebilen kafa bölümü, ayarlanabilir yükseklik.',
            'BK-001',
            '45',
            'berber koltugu,kuaför ekipmanlari',
            'https://ornek.com/berber-koltugu.jpg',
        ];

        return implode(',', self::TEMPLATE_HEADERS) . "\n" . implode(',', array_map(
            fn ($value) => '"' . str_replace('"', '""', $value) . '"',
            $sampleRow
        )) . "\n";
    }

    public function summaryMessage(BulkImport $import): string
    {
        $summary = collect($import->error_log ?? [])->firstWhere('_summary', true) ?? [];
        $published = $summary['published_count'] ?? 0;
        $draft = $summary['draft_count'] ?? 0;
        $errors = $summary['error_count'] ?? $import->error_count;

        if ($import->success_count === 0) {
            return 'Hiçbir ürün yüklenemedi.' . ($errors > 0 ? ' ' . $errors . ' satırda hata var.' : '');
        }

        $msg = $import->success_count . ' ürün işlendi: ' . $published . ' yayında';
        if ($draft > 0) {
            $msg .= ', ' . $draft . ' taslak (görsel eksik — panelden fotoğraf ekleyin)';
        }
        if ($errors > 0) {
            $msg .= ', ' . $errors . ' satır uyarı/hata';
        }
        $aiMatches = $summary['ai_match_count'] ?? 0;
        if ($aiMatches > 0) {
            $msg .= ' (' . $aiMatches . ' kategori AI/akıllı eşleştirme)';
        }

        return $msg . '.';
    }

    /**
     * @param  list<string>  $headers
     * @return array<string, mixed>
     */
    private function mapRow(array $headers, array $row): array
    {
        $data = [];
        foreach ($headers as $index => $key) {
            $value = $row[$index] ?? null;
            $data[$key] = is_string($value) ? trim($value) : $value;
        }

        foreach (self::TEMPLATE_HEADERS as $header) {
            if (! array_key_exists($header, $data)) {
                $data[$header] = null;
            }
        }

        // Eski şablondaki status sütunu
        if (! array_key_exists('status', $data) && in_array('status', $headers, true)) {
            $data['status'] = $data['status'] ?? 1;
        }

        return $data;
    }

    private function validateRow(array $row, bool $isSellerImport): ?string
    {
        if (empty($row['name'])) {
            return 'Ürün adı zorunlu.';
        }

        if ($row['price'] === null || $row['price'] === '' || ! is_numeric($row['price'])) {
            return 'Fiyat sayısal olmalı.';
        }

        if ($row['qty'] !== null && $row['qty'] !== '' && filter_var($row['qty'], FILTER_VALIDATE_INT) === false) {
            return 'Adet (stok) tam sayı olmalı.';
        }

        return null;
    }

    private function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = $base ?: 'urun';
        $candidate = $slug;
        $i = 1;

        while (Product::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $candidate)
            ->exists()) {
            $candidate = $slug . '-' . $i;
            $i++;
        }

        return $candidate;
    }

    private function isEmptyRow(array $row): bool
    {
        return collect($row)->every(fn ($value) => $value === null || $value === '');
    }

    private function failImport(BulkImport $bulkImport, array $errors, int $totalRows = 0): BulkImport
    {
        $bulkImport->update([
            'total_rows' => $totalRows,
            'processed_rows' => 0,
            'success_count' => 0,
            'error_count' => count($errors),
            'status' => 'failed',
            'error_log' => $errors,
            'completed_at' => now(),
        ]);

        return $bulkImport->fresh();
    }
}
