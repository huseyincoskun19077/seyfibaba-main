<?php

namespace App\Http\Controllers\API\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\CallRecording;
use App\Models\Setting;
use App\Services\NetsantralCallRecordingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NetsippWebhookController extends Controller
{
    public function handle(Request $request, NetsantralCallRecordingService $service)
    {
        $setting = Setting::first();
        $expected = trim((string) ($setting->netsipp_webhook_secret ?? ''));
        $token = trim((string) ($request->query('token') ?: $request->header('X-Netsipp-Token', '')));

        if ($expected === '' || $token === '' || ! hash_equals($expected, $token)) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        $scenario = (string) ($payload['scenario'] ?? '');

        if ($scenario !== 'cdr') {
            return response()->json(['status' => true, 'ignored' => true, 'scenario' => $scenario], 200);
        }

        try {
            $recording = $service->upsertFromNetsippCdrWebhook($payload);
        } catch (\Throwable $e) {
            Log::error('Netsipp CDR webhook failed', ['message' => $e->getMessage()]);

            return response()->json(['status' => false, 'message' => 'Processing error'], 500);
        }

        if ($recording && $recording->remote_recording_url && ! $recording->hasLocalAudio()) {
            $id = $recording->id;
            dispatch(function () use ($id) {
                try {
                    $row = CallRecording::find($id);
                    if ($row && ! $row->hasLocalAudio() && $row->remote_recording_url) {
                        app(NetsantralCallRecordingService::class)->downloadAudio($row);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Netsipp webhook audio download failed', [
                        'id' => $id,
                        'message' => $e->getMessage(),
                    ]);
                }
            })->afterResponse();
        }

        return response()->json(['status' => true], 200);
    }
}
