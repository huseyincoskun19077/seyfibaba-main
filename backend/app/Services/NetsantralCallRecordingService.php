<?php

namespace App\Services;

use App\Models\CallRecording;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Netsipp Public API (çağrı listesi) + klasik Netgsm Netsantral CDR (ses URL).
 * Netsipp: GET https://api.netsipp.com/v1/reports/call-details
 * Netgsm ses: POST https://api.netgsm.com.tr/netsantral/report
 * AI Transkript kullanılmaz; ses dosyası lokal saklanır.
 */
class NetsantralCallRecordingService
{
    private const REPORT_URL = 'https://api.netgsm.com.tr/netsantral/report';

    private const NETSIPP_CALL_DETAILS_URL = 'https://api.netsipp.com/v1/reports/call-details';

    /**
     * @return array{fetched:int, upserted:int, downloaded:int, failed:int, message?:string}
     */
    public function syncDateRange(Carbon $from, Carbon $to, bool $downloadAudio = true): array
    {
        $creds = $this->credentials();
        if (! $creds) {
            return [
                'fetched' => 0,
                'upserted' => 0,
                'downloaded' => 0,
                'failed' => 0,
                'message' => 'API ayarı yok: Netsipp API key veya Netgsm kullanıcı/şifre girin ve senkronu aktif edin.',
            ];
        }

        $list = [];
        $sourceNote = null;
        $netsippKey = $creds['netsipp_api_key'] ?? null;

        // 1) Netsipp Public API — hesap Netsipp ise asıl kaynak bu
        if ($netsippKey) {
            $netsipp = $this->fetchNetsippCallDetails($netsippKey, $from, $to);
            if (isset($netsipp['error'])) {
                // Netsipp başarısızsa klasik dene; yoksa hatayı döndür
                if (empty($creds['usercode'])) {
                    return [
                        'fetched' => 0,
                        'upserted' => 0,
                        'downloaded' => 0,
                        'failed' => 0,
                        'message' => (string) $netsipp['error'],
                    ];
                }
                $sourceNote = (string) $netsipp['error'];
            } else {
                $list = $netsipp['items'];
                $sourceNote = 'Netsipp call-details';
            }
        }

        // 2) Klasik Netgsm CDR (ses URL için; Netsipp listesi yoksa ana kaynak)
        $classicError = null;
        if (! empty($creds['usercode']) && ! empty($creds['password'])) {
            $rows = $this->fetchReport(
                $creds['usercode'],
                $creds['password'],
                $from->format('dmYHi'),
                $to->format('dmYHi'),
                $creds['pbxnum'] ?? null
            );

            if ($this->isHardError($rows)) {
                $classicError = $this->explainError(
                    $rows['code'] ?? null,
                    (string) ($rows['error'] ?? $rows['message'] ?? $rows['durum'] ?? 'CDR sorgusu başarısız')
                );
            } else {
                $classicList = $this->normalizeReportList($rows);
                if ($list === []) {
                    $list = $classicList;
                    $sourceNote = 'Netgsm netsantral/report';
                } else {
                    $list = $this->mergeClassicRecordingsIntoList($list, $classicList);
                    $sourceNote = 'Netsipp + Netgsm ses URL';
                }
            }
        }

        if ($list === []) {
            return [
                'fetched' => 0,
                'upserted' => 0,
                'downloaded' => 0,
                'failed' => 0,
                'message' => $classicError
                    ?? $sourceNote
                    ?? 'Bu aralıkta çağrı bulunamadı.',
            ];
        }

        $upserted = 0;
        $downloaded = 0;
        $failed = 0;

        foreach ($list as $item) {
            $recording = $this->upsertFromCdrItem($item);
            if (! $recording) {
                continue;
            }
            $upserted++;

            if ($downloadAudio && $recording->remote_recording_url && ! $recording->hasLocalAudio()) {
                try {
                    $this->downloadAudio($recording);
                    $downloaded++;
                } catch (\Throwable $e) {
                    $failed++;
                    $recording->update([
                        'sync_status' => 'failed',
                        'sync_error' => $e->getMessage(),
                    ]);
                    Log::warning('Call recording download failed', [
                        'uniqueid' => $recording->uniqueid,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        }

        $result = [
            'fetched' => count($list),
            'upserted' => $upserted,
            'downloaded' => $downloaded,
            'failed' => $failed,
        ];

        if ($downloaded === 0 && $classicError) {
            $result['message'] = 'Ses API kapalı (331). Satırdan mp3 yükleyin veya Netgsm’den netsantral/report / FTP yedek açtırın.';
        } elseif ($downloaded === 0 && $sourceNote === 'Netsipp call-details') {
            $result['message'] = 'Netsipp liste verdi; ses URL yok. Satırdan dosya yükleyin veya Netgsm FTP yedek / klasik CDR izni kullanın.';
        }

        return $result;
    }

    public function attachUploadedFile(CallRecording $recording, \Illuminate\Http\UploadedFile $file): CallRecording
    {
        $dir = public_path('uploads/call-recordings');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'mp3');
        if (! in_array($ext, ['mp3', 'wav', 'ogg', 'm4a', 'gsm'], true)) {
            $ext = 'mp3';
        }

        $safeId = preg_replace('/[^a-zA-Z0-9._-]/', '_', $recording->uniqueid) ?: Str::random(12);
        $filename = $safeId . '-upload-' . date('YmdHis') . '.' . $ext;
        $relative = 'uploads/call-recordings/' . $filename;
        $file->move($dir, $filename);

        if ($recording->local_path && is_file(public_path($recording->local_path))) {
            @unlink(public_path($recording->local_path));
        }

        $recording->update([
            'local_path' => $relative,
            'file_size' => @filesize(public_path($relative)) ?: null,
            'sync_status' => 'downloaded',
            'sync_error' => null,
        ]);

        return $recording->fresh();
    }

    /**
     * Netgsm FTP / manuel kopyalanan sesleri uniqueid ile eşleştir.
     * Klasör: storage/app/netsipp-audio
     *
     * @return array{matched:int, skipped:int}
     */
    public function importFromInbox(): array
    {
        $dir = storage_path('app/netsipp-audio');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $matched = 0;
        $skipped = 0;
        $files = array_merge(
            glob($dir . '/*.mp3') ?: [],
            glob($dir . '/*.wav') ?: [],
            glob($dir . '/*.ogg') ?: [],
            glob($dir . '/*.m4a') ?: [],
            glob($dir . '/*.gsm') ?: [],
            glob($dir . '/*.MP3') ?: [],
            glob($dir . '/*.WAV') ?: []
        );

        foreach ($files as $full) {
            $base = pathinfo($full, PATHINFO_FILENAME);
            $recording = CallRecording::query()
                ->where(function ($q) use ($base) {
                    $q->where('uniqueid', $base)
                        ->orWhere('uniqueid', 'like', '%' . addcslashes($base, '%_') . '%')
                        ->orWhereRaw('? LIKE CONCAT("%", uniqueid, "%")', [$base]);
                })
                ->orderByDesc('id')
                ->first();

            if (! $recording) {
                $skipped++;
                continue;
            }

            $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION) ?: 'mp3');
            $destDir = public_path('uploads/call-recordings');
            if (! is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            $safeId = preg_replace('/[^a-zA-Z0-9._-]/', '_', $recording->uniqueid) ?: Str::random(12);
            $filename = $safeId . '-inbox-' . date('YmdHis') . '.' . $ext;
            $relative = 'uploads/call-recordings/' . $filename;
            if (! @rename($full, public_path($relative)) && ! @copy($full, public_path($relative))) {
                $skipped++;
                continue;
            }
            @unlink($full);

            $recording->update([
                'local_path' => $relative,
                'file_size' => @filesize(public_path($relative)) ?: null,
                'sync_status' => 'downloaded',
                'sync_error' => null,
            ]);
            $matched++;
        }

        return compact('matched', 'skipped');
    }

    public function downloadAudio(CallRecording $recording): CallRecording
    {
        $url = trim((string) $recording->remote_recording_url);
        if ($url === '') {
            throw new \RuntimeException('Uzak ses URL yok.');
        }

        $response = Http::timeout(120)
            ->withHeaders(['User-Agent' => 'SeyfibabaCallSync/1.0'])
            ->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException('Ses indirme HTTP ' . $response->status());
        }

        $body = $response->body();
        if ($body === '' || strlen($body) < 64) {
            throw new \RuntimeException('Ses dosyası boş veya çok küçük.');
        }

        $dir = public_path('uploads/call-recordings');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $ext = $this->guessExtension($response->header('Content-Type'), $url);
        $safeId = preg_replace('/[^a-zA-Z0-9._-]/', '_', $recording->uniqueid) ?: Str::random(12);
        $filename = $safeId . '-' . date('YmdHis') . '.' . $ext;
        $relative = 'uploads/call-recordings/' . $filename;
        $full = public_path($relative);

        file_put_contents($full, $body);

        $recording->update([
            'local_path' => $relative,
            'file_size' => filesize($full) ?: strlen($body),
            'sync_status' => 'downloaded',
            'sync_error' => null,
        ]);

        return $recording->fresh();
    }

    /**
     * @return array{usercode?:string,password?:string,pbxnum?:string,netsipp_api_key?:string}|null
     */
    private function credentials(): ?array
    {
        $setting = Setting::first();
        if (! $setting) {
            return null;
        }

        $out = [];
        $netsippKey = trim((string) ($setting->netsipp_api_key ?? ''));
        $santralUser = trim((string) ($setting->netsantral_usercode ?? ''));
        $santralPass = trim((string) ($setting->netsantral_password ?? ''));
        $pbxnum = $this->normalizePbxnum($setting->netsantral_pbxnum ?? '');

        if ($setting->netsantral_enabled) {
            if ($santralUser !== '' && $santralPass !== '') {
                $out['usercode'] = $santralUser;
                $out['password'] = $santralPass;
            }
            if ($netsippKey !== '') {
                $out['netsipp_api_key'] = $netsippKey;
            }
        }

        if (empty($out['usercode'])) {
            $usercode = trim((string) ($setting->netgsm_usercode ?? ''));
            $password = trim((string) ($setting->netgsm_password ?? ''));
            if ($usercode !== '' && $password !== '') {
                $out['usercode'] = $usercode;
                $out['password'] = $password;
            }
        }

        if ($pbxnum) {
            $out['pbxnum'] = $pbxnum;
        }

        if (empty($out['usercode']) && empty($out['netsipp_api_key'])) {
            return null;
        }

        return $out;
    }

    /**
     * @return array{items:list<array<string,mixed>}>|array{error:string}
     */
    private function fetchNetsippCallDetails(string $apiKey, Carbon $from, Carbon $to): array
    {
        $items = [];
        $page = 1;
        $maxPages = 50;

        do {
            $response = Http::timeout(90)
                ->withToken($apiKey)
                ->acceptJson()
                ->get(self::NETSIPP_CALL_DETAILS_URL, [
                    'date_from' => $from->toDateString(),
                    'date_to' => $to->toDateString(),
                    'page' => $page,
                    'limit' => 100,
                ]);

            if ($response->status() === 401) {
                return ['error' => 'Netsipp API key geçersiz veya süresi dolmuş (401).'];
            }
            if ($response->status() === 403) {
                return ['error' => 'Netsipp API kullanıcısında report_call_detail_view izni yok (403). Netsipp panelinden bu izni açın.'];
            }
            if ($response->status() === 503) {
                return ['error' => 'Netsipp rapor backend’i geçici olarak erişilemiyor veya organizasyona PBX atanmamış (503).'];
            }
            if (! $response->successful()) {
                Log::warning('Netsipp call-details HTTP error', [
                    'status' => $response->status(),
                    'body' => Str::limit($response->body(), 400),
                ]);

                return ['error' => 'Netsipp call-details HTTP ' . $response->status() . ': ' . Str::limit(strip_tags($response->body()), 160)];
            }

            $json = $response->json();
            if (! is_array($json) || ($json['status'] ?? false) !== true) {
                $msg = is_array($json) ? (string) ($json['message'] ?? 'Netsipp yanıtı başarısız') : 'Netsipp yanıtı JSON değil';

                return ['error' => $msg];
            }

            $batch = $json['data'] ?? [];
            if (! is_array($batch)) {
                break;
            }

            foreach ($batch as $row) {
                $mapped = $this->mapNetsippCallDetail((array) $row);
                if ($mapped) {
                    $items[] = $mapped;
                }
            }

            $meta = is_array($json['meta'] ?? null) ? $json['meta'] : [];
            $lastPage = (int) ($meta['last_page'] ?? $meta['lastPage'] ?? $page);
            $hasMore = $page < $lastPage && count($batch) > 0;
            $page++;
        } while ($hasMore && $page <= $maxPages);

        return ['items' => $items];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>|null
     */
    private function mapNetsippCallDetail(array $row): ?array
    {
        $linkedid = trim((string) ($row['linkedid'] ?? ''));
        if ($linkedid === '') {
            return null;
        }

        $direction = match ((string) ($row['direction'] ?? '')) {
            'in' => 1,
            'out' => 0,
            default => null,
        };

        return [
            'uniqueid' => $linkedid,
            'source' => $row['src'] ?? null,
            'destination' => $row['dst'] ?? null,
            'direction' => $direction,
            'duration' => $row['total_duration_sec'] ?? $row['agent_talk_sec'] ?? null,
            'date' => $row['call_start_time'] ?? $row['call_date'] ?? null,
            'directory' => $row['answered_by_name'] ?? $row['queue_name'] ?? null,
            'line' => null,
            'recording' => null,
            'commonID' => null,
            '_netsipp' => $row,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $netsippList
     * @param  list<array<string, mixed>>  $classicList
     * @return list<array<string, mixed>>
     */
    private function mergeClassicRecordingsIntoList(array $netsippList, array $classicList): array
    {
        $byId = [];
        foreach ($classicList as $item) {
            $id = trim((string) ($item['uniqueid'] ?? ''));
            if ($id !== '') {
                $byId[$id] = $item;
            }
        }

        foreach ($netsippList as &$item) {
            $id = trim((string) ($item['uniqueid'] ?? ''));
            if ($id === '' || ! isset($byId[$id])) {
                continue;
            }
            $classic = $byId[$id];
            $url = trim((string) ($classic['recording'] ?? $classic['seskaydi'] ?? ''));
            if ($url !== '') {
                $item['recording'] = $url;
            }
            if (empty($item['line']) && ! empty($classic['line'])) {
                $item['line'] = $classic['line'];
            }
        }
        unset($item);

        $seen = [];
        foreach ($netsippList as $item) {
            $seen[trim((string) ($item['uniqueid'] ?? ''))] = true;
        }
        foreach ($classicList as $item) {
            $id = trim((string) ($item['uniqueid'] ?? ''));
            if ($id !== '' && empty($seen[$id])) {
                $netsippList[] = $item;
            }
        }

        return $netsippList;
    }

    /**
     * Netgsm örnekleri: 850xxxxxxx / 312xxxxxxx (başında 0 veya 90 yok).
     */
    private function normalizePbxnum(?string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw);
        if ($digits === null || $digits === '') {
            return null;
        }
        if (str_starts_with($digits, '90') && strlen($digits) >= 12) {
            $digits = substr($digits, 2);
        }
        $digits = ltrim($digits, '0');

        return $digits !== '' ? $digits : null;
    }

    /**
     * Resmi Netgsm paketi pbxnum göndermez; bazı hesaplarda eklemek 331/72 üretebiliyor.
     * Bu yüzden birkaç kombinasyonu sırayla deneriz.
     *
     * @return array<string, mixed>
     */
    private function fetchReport(
        string $usercode,
        string $password,
        string $startdate,
        string $stopdate,
        ?string $pbxnum
    ): array {
        $base = [
            'usercode' => $usercode,
            'password' => $password,
            'startdate' => $startdate,
            'stopdate' => $stopdate,
        ];

        $attempts = [];
        // 1) Resmi: sadece tarih (netgsm/netsantral Package::gorusmeDetay)
        $attempts[] = ['label' => 'json-no-pbx', 'mode' => 'json', 'body' => $base];
        // 2) pbxnum ile (bazı çoklu santral hesapları)
        if ($pbxnum) {
            $attempts[] = ['label' => 'json-pbx', 'mode' => 'json', 'body' => $base + ['pbxnum' => $pbxnum]];
        }
        $attempts[] = ['label' => 'form-no-pbx', 'mode' => 'form', 'body' => $base];
        if ($pbxnum) {
            $attempts[] = ['label' => 'form-pbx', 'mode' => 'form', 'body' => $base + ['pbxnum' => $pbxnum]];
            $attempts[] = ['label' => 'get-pbx', 'mode' => 'get', 'body' => $base + ['pbxnum' => $pbxnum]];
        }
        $attempts[] = ['label' => 'get-no-pbx', 'mode' => 'get', 'body' => $base];

        $lastError = ['code' => null, 'error' => 'CDR sorgusu başarısız'];

        foreach ($attempts as $attempt) {
            try {
                $response = match ($attempt['mode']) {
                    'form' => Http::timeout(90)->asForm()->post(self::REPORT_URL, $attempt['body']),
                    'get' => Http::timeout(90)->get(self::REPORT_URL, $attempt['body']),
                    default => Http::timeout(90)->acceptJson()->asJson()->post(self::REPORT_URL, $attempt['body']),
                };
                $data = $this->decodeReportResponse($response);
            } catch (\Throwable $e) {
                Log::warning('Netsantral CDR attempt failed', [
                    'attempt' => $attempt['label'],
                    'message' => $e->getMessage(),
                ]);
                $lastError = ['code' => null, 'error' => $e->getMessage()];
                continue;
            }

            if (! $this->isHardError($data)) {
                Log::info('Netsantral CDR ok', ['attempt' => $attempt['label']]);

                return $data;
            }

            // 60 = kayıt yok → başarı sayılır, boş liste
            if ((string) ($data['code'] ?? '') === '60') {
                return [];
            }

            Log::warning('Netsantral CDR soft-fail', [
                'attempt' => $attempt['label'],
                'code' => $data['code'] ?? null,
                'error' => $data['error'] ?? $data['message'] ?? null,
                'has_pbx' => isset($attempt['body']['pbxnum']),
                'startdate' => $startdate,
                'stopdate' => $stopdate,
            ]);
            $lastError = $data;
        }

        return $lastError;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeReportResponse(\Illuminate\Http\Client\Response $response): array
    {
        if (! $response->successful()) {
            Log::warning('Netsantral CDR HTTP error', [
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ]);
            throw new \RuntimeException('CDR HTTP ' . $response->status() . ': ' . Str::limit(strip_tags($response->body()), 180));
        }

        $data = $response->json();
        if (is_array($data)) {
            return $data;
        }

        $raw = trim($response->body());
        Log::warning('Netsantral CDR non-JSON', ['body' => Str::limit($raw, 500)]);
        throw new \RuntimeException('CDR yanıtı JSON değil: ' . Str::limit($raw, 180));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function isHardError(array $data): bool
    {
        return isset($data['code']) || isset($data['error']);
    }

    private function explainError(mixed $code, string $raw): string
    {
        $codeStr = $code !== null ? (string) $code : '';
        $map = [
            '30' => 'Geçersiz kullanıcı/şifre, API izni yok veya sunucu IP’si Netgsm’de kısıtlı. Netgsm → Ayarlar → API işlemleri kontrol edin.',
            '40' => 'Geçersiz santral bilgileri. Santral numarası (pbxnum / 850…) doğru mu bakın.',
            '60' => 'Bu aralıkta listelenecek kayıt yok.',
            '70' => 'Hatalı veya eksik parametre (tarih formatı / zorunlu alan). Kısa bir gün aralığı (ör. bugün) deneyin.',
            '72' => 'Netgsm 72: Santral/CDR isteği reddedildi. Genelde yanlış sabit hat (pbxnum), desteklenmeyen parametre veya santral rapor yetkisi. Sabit hattı 850…/312… formatında (başında 0 veya 90 olmadan) kaydedin; yine olmazsa Netgsm’den “netsantral/report” izni isteyin.',
            '80' => 'Sorgulama limiti aşıldı; birkaç dakika bekleyip tekrar deneyin.',
            '100' => 'Netgsm sistem hatası; sonra tekrar deneyin.',
            '331' => 'Netgsm 331: Klasik netsantral/report bu hesapta kapalı. Netsipp API key (/v1/me) çalışsa bile ses URL bu endpoint’ten gelmez. Çağrı listesi için Netsipp API key + Aktif yeterli; ses için Netgsm destekten netsantral/report izni isteyin.',
        ];

        $hint = $map[$codeStr] ?? null;
        if ($hint) {
            return $hint . ($codeStr !== '' ? " (kod: {$codeStr})" : '');
        }

        return trim($raw) . ($codeStr !== '' ? " (kod: {$codeStr})" : '');
    }

    /**
     * @param  array<mixed>  $rows
     * @return list<array<string, mixed>>
     */
    private function normalizeReportList(array $rows): array
    {
        // Success list is numeric array of { uniqueid, values: [...] }
        if ($rows === []) {
            return [];
        }

        // Associative error already handled; if keys are uniqueid-like wrappers
        if (isset($rows[0]) || array_is_list($rows)) {
            $out = [];
            foreach ($rows as $row) {
                $row = (array) $row;
                $uniqueid = (string) ($row['uniqueid'] ?? '');
                $values = $row['values'] ?? null;
                if (! is_array($values)) {
                    // Flat single-value shape
                    if ($uniqueid !== '') {
                        $out[] = $row;
                    }
                    continue;
                }
                foreach ($values as $value) {
                    $value = (array) $value;
                    $value['uniqueid'] = $uniqueid ?: (string) ($value['uniqueid'] ?? '');
                    $out[] = $value;
                }
            }

            return $out;
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function upsertFromCdrItem(array $item): ?CallRecording
    {
        $uniqueid = trim((string) ($item['uniqueid'] ?? ''));
        if ($uniqueid === '') {
            return null;
        }

        $remoteUrl = trim((string) ($item['recording'] ?? $item['seskaydi'] ?? ''));
        $calledAt = $this->parseCallDate($item['date'] ?? null);

        $attrs = [
            'common_id' => isset($item['commonID']) ? (string) $item['commonID'] : null,
            'source' => isset($item['source']) ? (string) $item['source'] : null,
            'destination' => isset($item['destination']) ? (string) $item['destination'] : null,
            'direction' => isset($item['direction']) ? (int) $item['direction'] : null,
            'duration_sec' => isset($item['duration']) ? (int) $item['duration'] : null,
            'called_at' => $calledAt,
            'line' => isset($item['line']) ? (string) $item['line'] : null,
            'directory' => isset($item['directory']) ? (string) $item['directory'] : null,
            'remote_recording_url' => $remoteUrl !== '' ? $remoteUrl : null,
            'raw_payload' => $item,
        ];

        if ($remoteUrl === '') {
            $attrs['sync_status'] = 'no_recording';
        } elseif (! CallRecording::where('uniqueid', $uniqueid)->whereNotNull('local_path')->exists()) {
            $attrs['sync_status'] = 'pending';
        }

        return CallRecording::updateOrCreate(
            ['uniqueid' => $uniqueid],
            $attrs
        );
    }

    private function parseCallDate(mixed $date): ?Carbon
    {
        if (! $date) {
            return null;
        }
        $date = trim((string) $date);
        foreach (['d.m.Y H:i:s', 'd.m.Y H:i', 'Y-m-d H:i:s', 'Y-m-d H:i'] as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $date);
            } catch (\Throwable) {
                // try next
            }
        }
        try {
            return Carbon::parse($date);
        } catch (\Throwable) {
            return null;
        }
    }

    private function guessExtension(?string $contentType, string $url): string
    {
        $ct = strtolower((string) $contentType);
        if (str_contains($ct, 'mpeg') || str_contains($ct, 'mp3')) {
            return 'mp3';
        }
        if (str_contains($ct, 'wav')) {
            return 'wav';
        }
        if (str_contains($ct, 'ogg')) {
            return 'ogg';
        }
        if (preg_match('/\.(mp3|wav|ogg|m4a)(\?|$)/i', $url, $m)) {
            return strtolower($m[1]);
        }

        return 'mp3';
    }
}
