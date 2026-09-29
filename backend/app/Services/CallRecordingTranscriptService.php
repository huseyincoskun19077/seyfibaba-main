<?php

namespace App\Services;

use App\Models\CallRecording;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CallRecordingTranscriptService
{
    private const MAX_BYTES = 25 * 1024 * 1024;

    public function queue(CallRecording $recording): void
    {
        if (! $recording->hasLocalAudio() || $recording->transcript_status === 'done') {
            return;
        }

        $recording->update(['transcript_status' => 'pending']);
        $id = $recording->id;
        dispatch(function () use ($id) {
            $row = CallRecording::find($id);
            if ($row && $row->transcript_status === 'pending') {
                app(self::class)->transcribe($row);
            }
        })->afterResponse();
    }

    public function transcribe(CallRecording $recording, bool $force = false): CallRecording
    {
        if (! $force && $recording->transcript_status === 'done' && $recording->transcript_text) {
            return $recording;
        }

        if (! $recording->hasLocalAudio()) {
            return $this->fail($recording, 'Yerel ses dosyası yok.');
        }

        $path = public_path($recording->local_path);
        $size = @filesize($path) ?: 0;
        if ($size < 64) {
            return $this->fail($recording, 'Ses dosyası boş.');
        }
        if ($size > self::MAX_BYTES) {
            return $this->fail($recording, 'Ses 25 MB sınırını aşıyor.');
        }

        $setting = Setting::first();
        $apiKey = trim((string) ($setting->openai_api_key ?? ''));
        if ($apiKey === '' || ! $setting) {
            return $this->fail($recording, 'Metin için admin sohbet ayarlarındaki API anahtarı gerekli.');
        }

        $recording->update(['transcript_status' => 'pending']);

        try {
            if (str_starts_with($apiKey, 'sk-ant-')) {
                return $this->fail($recording, 'Ses metni bu anahtarla çıkmıyor. OpenAI veya Groq anahtarı gerekli.');
            }

            if (str_starts_with($apiKey, 'gsk_')) {
                $segments = $this->transcribeWithGroq($apiKey, $path, $setting, $recording);
                $method = 'groq-split';
            } else {
                $segments = $this->transcribeWithOpenAi($apiKey, $path);
                $method = 'openai-diarize';
            }

            $payload = $this->buildDialogue($recording, $segments, $method);
            if ($payload['turns'] === []) {
                return $this->fail($recording, 'Konuşma metni çıkmadı.');
            }

            $recording->update([
                'transcript_status' => 'done',
                'transcript_text' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Call transcript failed', [
                'id' => $recording->id,
                'message' => $e->getMessage(),
            ]);

            return $this->fail($recording, $this->safeMessage($e->getMessage()));
        }

        return $recording->fresh() ?? $recording;
    }

    public function swapRoles(CallRecording $recording): CallRecording
    {
        $data = json_decode((string) $recording->transcript_text, true);
        if (! is_array($data) || empty($data['turns'])) {
            return $recording;
        }

        foreach ($data['turns'] as &$turn) {
            $role = (string) ($turn['role'] ?? '');
            if ($role === 'ours') {
                $turn['role'] = 'other';
            } elseif ($role === 'other') {
                $turn['role'] = 'ours';
            }
        }
        unset($turn);

        $recording->update([
            'transcript_text' => json_encode($data, JSON_UNESCAPED_UNICODE),
        ]);

        return $recording->fresh() ?? $recording;
    }

    /**
     * @param  list<array{speaker?:string,text?:string,start?:float|null,end?:float|null}>  $segments
     * @return array{method:string,our_label:string,other_label:string,single_label:string,turns:list<array{role:string,text:string,start:float|null,end:float|null}>}
     */
    public function buildDialogue(CallRecording $recording, array $segments, string $method = 'openai-diarize'): array
    {
        $parties = $this->parties($recording);
        $ourLabel = 'Bizim konuşmacı'.($parties['ours'] ? ' ('.$parties['ours'].')' : '');
        $otherLabel = 'Karşı taraf konuşmacısı'.($parties['other'] ? ' ('.$parties['other'].')' : '');

        $cleaned = [];
        foreach ($segments as $seg) {
            $text = trim((string) ($seg['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $cleaned[] = [
                'speaker' => trim((string) ($seg['speaker'] ?? '')),
                'text' => $text,
                'start' => isset($seg['start']) ? (float) $seg['start'] : null,
                'end' => isset($seg['end']) ? (float) $seg['end'] : null,
            ];
        }

        $labeled = false;
        foreach ($cleaned as $seg) {
            if ($seg['speaker'] === 'ours' || $seg['speaker'] === 'other') {
                $labeled = true;
                break;
            }
        }

        $firstSpeaker = $cleaned[0]['speaker'] ?? '';
        $distinct = array_values(array_unique(array_filter(array_map(
            fn (array $seg) => $seg['speaker'],
            $cleaned
        ))));

        $turns = [];
        foreach ($cleaned as $seg) {
            if ($labeled) {
                $role = $seg['speaker'] === 'other' ? 'other' : 'ours';
            } elseif (count($distinct) < 2) {
                $role = 'single';
            } else {
                $role = $seg['speaker'] === $firstSpeaker ? 'ours' : 'other';
            }

            $last = count($turns) - 1;
            if ($last >= 0 && $turns[$last]['role'] === $role) {
                $turns[$last]['text'] .= ' '.$seg['text'];
                $turns[$last]['end'] = $seg['end'];
                continue;
            }

            $turns[] = [
                'role' => $role,
                'text' => $seg['text'],
                'start' => $seg['start'],
                'end' => $seg['end'],
            ];
        }

        return [
            'method' => $method,
            'our_label' => $ourLabel,
            'other_label' => $otherLabel,
            'single_label' => 'Konuşma',
            'turns' => $turns,
        ];
    }

    /**
     * @return array{ours:?string,other:?string}
     */
    private function parties(CallRecording $recording): array
    {
        $src = preg_replace('/\D+/', '', (string) $recording->source) ?: '';
        $dst = preg_replace('/\D+/', '', (string) $recording->destination) ?: '';
        $srcInternal = $src !== '' && strlen($src) <= 5;
        $dstInternal = $dst !== '' && strlen($dst) <= 5;

        if ($srcInternal && ! $dstInternal) {
            return ['ours' => $src, 'other' => $dst !== '' ? $dst : null];
        }
        if ($dstInternal && ! $srcInternal) {
            return ['ours' => $dst, 'other' => $src !== '' ? $src : null];
        }

        return [
            'ours' => null,
            'other' => $dst !== '' ? $dst : ($src !== '' ? $src : null),
        ];
    }

    /**
     * @return list<array{speaker:string,text:string,start:?float,end:?float}>
     */
    private function transcribeWithOpenAi(string $apiKey, string $path): array
    {
        $payload = [
            'model' => 'gpt-4o-transcribe-diarize',
            'response_format' => 'diarized_json',
            'chunking_strategy' => 'auto',
            'language' => 'tr',
        ];

        $data = $this->postAudio('https://api.openai.com/v1/audio/transcriptions', $apiKey, $path, $payload);
        if (isset($data['_retry_without_language'])) {
            unset($payload['language']);
            $data = $this->postAudio('https://api.openai.com/v1/audio/transcriptions', $apiKey, $path, $payload);
        }

        return $this->segmentsFromDiarized($data);
    }

    /**
     * @return list<array{speaker:string,text:string,start:?float,end:?float}>
     */
    private function transcribeWithGroq(string $apiKey, string $path, Setting $setting, CallRecording $recording): array
    {
        $data = $this->postAudio('https://api.groq.com/openai/v1/audio/transcriptions', $apiKey, $path, [
            'model' => 'whisper-large-v3-turbo',
            'language' => 'tr',
            'response_format' => 'json',
            'temperature' => '0',
        ]);

        $text = trim((string) ($data['text'] ?? ''));
        if ($text === '') {
            return [];
        }

        return $this->splitSpeakersWithChat($apiKey, $setting, $recording, $text);
    }

    /**
     * @param  array<string, string>  $fields
     * @return array<string, mixed>
     */
    private function postAudio(string $url, string $apiKey, string $path, array $fields): array
    {
        $response = Http::timeout(180)
            ->withToken($apiKey)
            ->attach('file', file_get_contents($path), basename($path))
            ->post($url, $fields);

        $data = $response->json();
        if (! is_array($data)) {
            $data = [];
        }

        if (! $response->successful()) {
            $message = (string) ($data['error']['message'] ?? ('HTTP '.$response->status()));
            if ($response->status() === 400 && isset($fields['language']) && str_contains(strtolower($message), 'language')) {
                return ['_retry_without_language' => true];
            }
            throw new \RuntimeException($message);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{speaker:string,text:string,start:?float,end:?float}>
     */
    private function segmentsFromDiarized(array $data): array
    {
        $segments = $data['segments'] ?? null;
        if (! is_array($segments) || $segments === []) {
            $text = trim((string) ($data['text'] ?? ''));
            if ($text === '') {
                return [];
            }

            return [['speaker' => '', 'text' => $text, 'start' => null, 'end' => null]];
        }

        $out = [];
        foreach ($segments as $seg) {
            if (! is_array($seg)) {
                continue;
            }
            $out[] = [
                'speaker' => trim((string) ($seg['speaker'] ?? '')),
                'text' => trim((string) ($seg['text'] ?? '')),
                'start' => isset($seg['start']) ? (float) $seg['start'] : null,
                'end' => isset($seg['end']) ? (float) $seg['end'] : null,
            ];
        }

        return $out;
    }

    /**
     * @return list<array{speaker:string,text:string,start:?float,end:?float}>
     */
    private function splitSpeakersWithChat(string $apiKey, Setting $setting, CallRecording $recording, string $text): array
    {
        $parties = $this->parties($recording);
        $ours = $parties['ours'] ? 'dahili '.$parties['ours'] : 'santral görevlisi';
        $other = $parties['other'] ? 'numara '.$parties['other'] : 'müşteri';
        $model = trim((string) ($setting->openai_model ?? ''));
        if ($model === '' || str_starts_with($model, 'gpt-')) {
            $model = 'llama-3.3-70b-versatile';
        }

        $prompt = "Bu bir telefon görüşmesi dökümü. İki taraf var.\n"
            ."Bizim konuşmacı: {$ours}.\n"
            ."Karşı taraf konuşmacısı: {$other}.\n"
            ."Metni konuşma sırasına göre ayır. Uydurma cümle ekleme. Sadece JSON dizi döndür:\n"
            ."[{\"role\":\"ours\",\"text\":\"...\"},{\"role\":\"other\",\"text\":\"...\"}]\n\n"
            .$text;

        $response = Http::timeout(max(60, (int) ($setting->openai_timeout ?? 60)))
            ->withToken($apiKey)
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => $model,
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'temperature' => 0,
            ]);

        $content = (string) ($response->json('choices.0.message.content') ?? '');
        if (! preg_match('/\[[\s\S]*\]/', $content, $match)) {
            return [['speaker' => '', 'text' => $text, 'start' => null, 'end' => null]];
        }

        $rows = json_decode($match[0], true);
        if (! is_array($rows)) {
            return [['speaker' => '', 'text' => $text, 'start' => null, 'end' => null]];
        }

        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $role = (string) ($row['role'] ?? '');
            $line = trim((string) ($row['text'] ?? ''));
            if ($line === '' || ($role !== 'ours' && $role !== 'other')) {
                continue;
            }
            $out[] = ['speaker' => $role, 'text' => $line, 'start' => null, 'end' => null];
        }

        return $out !== [] ? $out : [['speaker' => '', 'text' => $text, 'start' => null, 'end' => null]];
    }

    private function fail(CallRecording $recording, string $message): CallRecording
    {
        $recording->update([
            'transcript_status' => 'failed',
            'transcript_text' => 'Hata: '.$this->safeMessage($message),
        ]);

        return $recording->fresh() ?? $recording;
    }

    private function safeMessage(string $message): string
    {
        $message = preg_replace('/sk-[A-Za-z0-9_\-]+/', '[anahtar]', $message) ?? $message;
        $message = preg_replace('/gsk_[A-Za-z0-9_\-]+/', '[anahtar]', $message) ?? $message;

        return mb_substr(trim($message), 0, 180);
    }
}
