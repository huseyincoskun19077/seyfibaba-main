<?php

namespace App\Console\Commands;

use App\Services\CustomerSegmentService;
use Illuminate\Console\Command;

/**
 * Kategori dökümü + önerilen alan eşleşmesi. Yazmaz (apply yok).
 */
class SegmentsPreviewMapping extends Command
{
    protected $signature = 'segments:preview-mapping
                            {--json : JSON çıktı}
                            {--tree : Kategori ağacı + ürün sayıları}
                            {--queue-sample=30 : Belirsiz ürün örnek sayısı}';

    protected $description = 'Müşteri alanı eşleşme önizlemesi (kategori silmez / URL değiştirmez)';

    public function handle(CustomerSegmentService $service): int
    {
        try {
            $service->ensureDefaults();
        } catch (\Throwable $e) {
            $this->warn('Segment tabloları yoksa önce migrate edin: '.$e->getMessage());
        }

        if ($this->option('tree')) {
            try {
                $tree = $service->dumpCategoryTreeWithCounts();
                if ($this->option('json')) {
                    $this->line(json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                } else {
                    foreach ($tree as $c) {
                        $this->line(sprintf('[%d] %s — %d ürün', $c['id'], $c['name'], $c['products']));
                        foreach ($c['subs'] as $s) {
                            $this->line(sprintf('  [%d] %s — %d ürün', $s['id'], $s['name'], $s['products']));
                        }
                    }
                }
            } catch (\Throwable $e) {
                $this->error('DB erişilemedi: '.$e->getMessage());

                return self::FAILURE;
            }
        }

        try {
            $preview = $service->previewSubCategoryMapping();
        } catch (\Throwable $e) {
            $this->error('Eşleşme önizlemesi başarısız: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(json_encode([
                'sub_mapping' => $preview,
                'ambiguous_sample' => $service->queueAmbiguousProducts(true, (int) $this->option('queue-sample')),
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->info('Alt kategori → önerilen müşteri alanları');
        $this->table(
            ['Sub ID', 'Üst', 'Alt kategori', 'Ürün', 'Öneri', 'Belirsiz?'],
            collect($preview)->map(fn ($r) => [
                $r['sub_id'],
                $r['category'],
                $r['sub_name'],
                $r['product_count'],
                implode(',', $r['suggested']) ?: '—',
                $r['ambiguous'] ? 'EVET' : 'hayır',
            ])->all()
        );

        $amb = $service->queueAmbiguousProducts(true, (int) $this->option('queue-sample'));
        $this->info('Belirsiz ürün örnekleri (dry-run, yazılmadı): '.count($amb));
        foreach (array_slice($amb, 0, 15) as $a) {
            $this->line(sprintf(
                '  #%d %s | %s / %s | %s',
                $a['product_id'],
                mb_substr($a['name'], 0, 50),
                $a['category'],
                $a['sub_category'],
                $a['reason']
            ));
        }

        $this->warn('Not: apply veya kategori silme/URL değişimi bu komutta YOK. Admin panelinden onaylayın.');

        return self::SUCCESS;
    }
}
