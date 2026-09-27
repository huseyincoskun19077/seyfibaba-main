<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sıralama paketi — salt okunur teşhis.
 * Kategori 1 satıcı stokları, ROW_NUMBER desteği, mevcut sayfa dağılımı.
 * Yazma / migrate / deploy yok.
 */
class SegmentsSortPackageCheck extends Command
{
    protected $signature = 'segments:sort-package-check
                            {--category-id=1 : İncelenecek kategori}
                            {--page-size=24 : Sayfa boyutu}
                            {--json : JSON çıktı}';

    protected $description = 'Salt okunur: kategori satıcı sayımları + ROW_NUMBER desteği + sayfa 1 dağılımı';

    public function handle(): int
    {
        if (! Schema::hasTable('products')) {
            $this->error('products yok');

            return self::FAILURE;
        }

        $categoryId = (int) $this->option('category-id');
        $pageSize = min(48, max(5, (int) $this->option('page-size')));
        $hasQty = Schema::hasColumn('products', 'qty');

        $versionRow = DB::selectOne('SELECT VERSION() AS v');
        $version = (string) ($versionRow->v ?? '');
        $rowNumberOk = $this->supportsRowNumber();

        $vendorStats = $this->vendorStats($categoryId, $hasQty);
        $page1 = $this->page1Distribution($categoryId, $pageSize);
        $verdict = $this->buildVerdict($vendorStats, $page1, $pageSize);

        $payload = [
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'read_only' => true,
                'category_id' => $categoryId,
                'page_size' => $pageSize,
            ],
            'mysql' => [
                'version' => $version,
                'row_number_supported' => $rowNumberOk,
                'probe_sql' => 'SELECT ROW_NUMBER() OVER (ORDER BY id) AS rn FROM products LIMIT 1',
            ],
            'vendor_stats' => $vendorStats,
            'page1_recommended' => $page1,
            'verdict' => $verdict,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->info('MySQL: '.$version.' | ROW_NUMBER: '.($rowNumberOk ? 'EVET' : 'HAYIR'));
        $this->newLine();
        $this->info("Kategori {$categoryId} — satıcı başına aktif ürün");
        $this->table(
            ['Vendor', 'Aktif', 'qty>0', 'qty<=0/null'],
            collect($vendorStats['vendors'])->map(fn ($v) => [
                $v['vendor_id'],
                $v['active'],
                $v['in_stock'],
                $v['out_or_unknown'],
            ])->all()
        );
        $this->line(sprintf(
            'Toplam aktif=%d | satıcı=%d | en büyük satıcı=%d (%d ürün, %%%.1f)',
            $vendorStats['total_active'],
            $vendorStats['vendor_count'],
            $vendorStats['largest_vendor_id'],
            $vendorStats['largest_active'],
            $vendorStats['largest_share_pct']
        ));

        $this->newLine();
        $this->info('Sayfa 1 (ROW_NUMBER sıralama) satıcı dağılımı');
        $this->line('vendor_counts: '.json_encode($page1['vendor_counts']));
        $this->line(sprintf('max_share=%d / %d | elapsed=%.2f ms', $page1['max_share'], $pageSize, $page1['elapsed_ms']));

        $this->newLine();
        $this->info('Sonuç: '.$verdict['summary']);
        foreach ($verdict['reasons'] as $r) {
            $this->line('- '.$r);
        }
        $this->comment('Algoritma değişikliği: '.($verdict['algorithm_change_needed'] ? 'EVET (diğer satıcılarda yeterli ürün var)' : 'HAYIR (mevcut dağılım kaçınılmaz / beklenen)'));

        return self::SUCCESS;
    }

    private function supportsRowNumber(): bool
    {
        try {
            DB::select('SELECT ROW_NUMBER() OVER (ORDER BY id) AS rn FROM products LIMIT 1');

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function vendorStats(int $categoryId, bool $hasQty): array
    {
        $q = Product::query()
            ->where('category_id', $categoryId)
            ->where('status', 1)
            ->where('approve_by_admin', 1);

        if ($hasQty) {
            $rows = (clone $q)
                ->selectRaw('vendor_id, COUNT(*) as active, SUM(CASE WHEN qty > 0 THEN 1 ELSE 0 END) as in_stock, SUM(CASE WHEN qty IS NULL OR qty <= 0 THEN 1 ELSE 0 END) as out_or_unknown')
                ->groupBy('vendor_id')
                ->orderByDesc('active')
                ->get();
        } else {
            $rows = (clone $q)
                ->selectRaw('vendor_id, COUNT(*) as active, COUNT(*) as in_stock, 0 as out_or_unknown')
                ->groupBy('vendor_id')
                ->orderByDesc('active')
                ->get();
        }

        $vendors = [];
        $total = 0;
        foreach ($rows as $row) {
            $active = (int) $row->active;
            $total += $active;
            $vendors[] = [
                'vendor_id' => (int) $row->vendor_id,
                'active' => $active,
                'in_stock' => (int) $row->in_stock,
                'out_or_unknown' => (int) $row->out_or_unknown,
            ];
        }

        $largest = $vendors[0] ?? ['vendor_id' => 0, 'active' => 0];

        return [
            'total_active' => $total,
            'vendor_count' => count($vendors),
            'largest_vendor_id' => (int) $largest['vendor_id'],
            'largest_active' => (int) $largest['active'],
            'largest_share_pct' => $total > 0 ? round(100 * $largest['active'] / $total, 1) : 0,
            'vendors' => $vendors,
            'qty_column' => $hasQty,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function page1Distribution(int $categoryId, int $pageSize): array
    {
        $table = (new Product)->getTable();
        $q = Product::query()
            ->where('category_id', $categoryId)
            ->where('status', 1)
            ->where('approve_by_admin', 1)
            ->reorder()
            ->orderByRaw("ROW_NUMBER() OVER (PARTITION BY {$table}.vendor_id ORDER BY {$table}.id DESC)")
            ->orderByDesc($table.'.id')
            ->limit($pageSize);

        $t0 = microtime(true);
        $rows = $q->get(['id', 'vendor_id']);
        $elapsed = round((microtime(true) - $t0) * 1000, 2);

        $counts = [];
        foreach ($rows as $row) {
            $vid = (int) $row->vendor_id;
            $counts[$vid] = ($counts[$vid] ?? 0) + 1;
        }

        return [
            'elapsed_ms' => $elapsed,
            'vendor_counts' => $counts,
            'max_share' => $counts === [] ? 0 : max($counts),
            'distinct_vendors' => count($counts),
            'ids' => $rows->pluck('id')->map(fn ($id) => (int) $id)->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $vendorStats
     * @param  array<string, mixed>  $page1
     * @return array<string, mixed>
     */
    private function buildVerdict(array $vendorStats, array $page1, int $pageSize): array
    {
        $vendors = $vendorStats['vendors'];
        $reasons = [];
        $algorithmChangeNeeded = false;

        if (count($vendors) <= 1) {
            $reasons[] = 'Kategoride tek satıcı var; ilk sayfanın tamamının aynı satıcıdan gelmesi kaçınılmaz.';

            return [
                'summary' => 'Tek satıcılı kategori — 24/24 monopoly beklenen.',
                'reasons' => $reasons,
                'algorithm_change_needed' => false,
            ];
        }

        // En büyük hariç diğerlerinin aktif ürün toplamı
        $others = array_slice($vendors, 1);
        $othersActive = array_sum(array_column($others, 'active'));
        $othersInStock = array_sum(array_column($others, 'in_stock'));
        $largest = $vendors[0];

        $reasons[] = sprintf(
            'En büyük satıcı #%d: %d aktif (%%%s). Diğer %d satıcı toplam: %d aktif, %d stoklu (qty>0).',
            $largest['vendor_id'],
            $largest['active'],
            $vendorStats['largest_share_pct'],
            count($others),
            $othersActive,
            $othersInStock
        );

        // ROW_NUMBER ile sayfa1 teorik max diğer satıcı katkısı = diğer satıcıların ürün sayısı (her biri rank 1..n)
        $theoreticalOtherSlots = min($pageSize - 1, $othersActive);
        $reasons[] = sprintf(
            'ROW_NUMBER sırasıyla ilk %d üründe diğer satıcılardan en fazla %d slot dolabilir (her satıcının ürün adedi kadar).',
            $pageSize,
            $theoreticalOtherSlots
        );
        $reasons[] = sprintf(
            'Ölçülen sayfa1: max_share=%d, diğer satıcı slotları≈%d.',
            $page1['max_share'],
            $pageSize - $page1['max_share']
        );

        if ($othersActive >= 8 && $page1['max_share'] >= (int) ($pageSize * 0.75)) {
            // Diğerlerinde yeterli ürün var ama sayfa hâlâ tek satıcı ağırlıklı → algoritma şüphesi
            $algorithmChangeNeeded = true;
            $reasons[] = 'Diğer satıcılarda yeterli aktif ürün var (≥8) ama ilk sayfa hâlâ tek satıcı ağırlıklı — algoritma/ikincil sıralama gözden geçirilmeli.';
        } elseif ($othersActive < 8) {
            $reasons[] = sprintf(
                'Diğer satıcıların toplam aktif ürünü %d < 8; ilk sayfada ~%d/24 aynı satıcı kaçınılmaz (round-robin sonrası kalan slotlar baskın satıcıyla dolar).',
                $othersActive,
                $pageSize - $othersActive
            );
        } else {
            $reasons[] = 'Dağılım, satıcı ürün adedi dengesizliği ile uyumlu; zorunlu algoritma değişikliği yok.';
        }

        return [
            'summary' => $algorithmChangeNeeded
                ? 'Algoritma düzeltmesi önerilir.'
                : '22/24 tipi sonuç stok/ürün adedi dengesizliği ile açıklanabilir; algoritma değişikliği gerekmez.',
            'reasons' => $reasons,
            'algorithm_change_needed' => $algorithmChangeNeeded,
            'others_active' => $othersActive,
            'others_in_stock' => $othersInStock,
            'theoretical_min_dominant_on_page1' => max(0, $pageSize - $othersActive),
        ];
    }
}
