<?php

namespace App\Services;

use App\Models\CallRecording;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Netgsm Netsantral / Netsipp görüşme detayı (CDR) + ses kaydı indirme.
 * Endpoint: POST https://api.netgsm.com.tr/netsantral/report
 * AI Transkript kullanılmaz; ses dosyası lokal saklanır.
 */
class NetsantralCallRecordingService
{
    private const REPORT_URL = 'https://api.netgsm.com.tr/netsantral/report';

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
                'message' => 'Netgsm kullanıcı kodu / şifre eksik veya Netsantral senkron kapalı (Çağrı Kayıtları → API Ayarları).',
            ];
        }

        $rows = $this->fetchReport($creds['usercode'], $creds['password'], array_filter([
            'startdate' => $from->format('dmYHi'),
            'stopdate' => $to->format('dmYHi'),
            'pbxnum' => $creds['pbxnum'] ?? null,
        ], fn ($v) => $v !== null && $v !== ''));

        if (isset($rows['error']) || isset($rows['code'])) {
            $code = $rows['code'] ?? null;
            $raw = (string) ($rows['error'] ?? $rows['message'] ?? $rows['durum'] ?? 'CDR sorgusu başarısız');

            return [
                'fetched' => 0,
                'upserted' => 0,
                'downloaded' => 0,
                'failed' => 0,
                'message' => $this->explainError($code, $raw),
            ];
        }

        $list = $this->normalizeReportList($rows);
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

        return [
            'fetched' => count($list),
            'upserted' => $upserted,
            'downloaded' => $downloaded,
            'failed' => $failed,
        ];
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
     * @return array{usercode:string,password:string,pbxnum?:string}|null
     */
    private function credentials(): ?array
    {
        $setting = Setting::first();
        if (! $setting) {
            return null;
        }

        $santralUser = trim((string) ($setting->netsantral_usercode ?? ''));
        $santralPass = trim((string) ($setting->netsantral_password ?? ''));
        $pbxnum = trim((string) ($setting->netsantral_pbxnum ?? ''));

        if ($santralUser !== '' && $santralPass !== '') {
            if (! $setting->netsantral_enabled) {
                return null;
            }

            $out = ['usercode' => $santralUser, 'password' => $santralPass];
            if ($pbxnum !== '') {
                $out['pbxnum'] = $pbxnum;
            }

            return $out;
        }

        $usercode = trim((string) ($setting->netgsm_usercode ?? ''));
        $password = trim((string) ($setting->netgsm_password ?? ''));
        if ($usercode === '' || $password === '') {
            return null;
        }

        $out = compact('usercode', 'password');
        if ($pbxnum !== '') {
            $out['pbxnum'] = $pbxnum;
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function fetchReport(string $usercode, string $password, array $filters): array
    {
        $body = array_merge([
            'usercode' => $usercode,
            'password' => $password,
        ], $filters);

        // 1) Resmi paket gibi JSON POST
        $response = Http::timeout(90)
            ->acceptJson()
            ->asJson()
            ->post(self::REPORT_URL, $body);

        $data = $this->decodeReportResponse($response);
        if ($this->isHardError($data) && (string) ($data['code'] ?? '') === '331') {
            // 2) Form POST dene (bazı hesaplarda JSON reddediliyor)
            $response = Http::timeout(90)
                ->asForm()
                ->post(self::REPORT_URL, $body);
            $data = $this->decodeReportResponse($response);
        }

        if ($this->isHardError($data) && (string) ($data['code'] ?? '') === '331' && ! empty($filters['pbxnum'])) {
            // 3) GET query (eski doküman örnekleri)
            $response = Http::timeout(90)->get(self::REPORT_URL, $body);
            $data = $this->decodeReportResponse($response);
        }

        return $data;
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
            '70' => 'Hatalı veya eksik parametre (tarih formatı / zorunlu alan).',
            '80' => 'Sorgulama limiti aşıldı; birkaç dakika bekleyip tekrar deneyin.',
            '100' => 'Netgsm sistem hatası; sonra tekrar deneyin.',
            '331' => 'Netgsm 331: Bu hesap için klasik Netsantral CDR servisi kabul etmedi. Genelde (1) santral numarası (850…) eksik, (2) API’de santral rapor izni yok, (3) Netsipp hesabı farklı yetki istiyor. API ayarlarına santral numaranızı ekleyin; Netgsm destekten “netsantral/report + ses kaydı API” izni isteyin. Netsipp API key tek başına bu CDR endpoint’ini açmaz.',
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
