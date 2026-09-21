<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\CallRecording;
use App\Models\Setting;
use App\Services\NetsantralCallRecordingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CallRecordingController extends Controller
{
    public function index(Request $request)
    {
        $setting = Setting::first();
        $query = CallRecording::query()->orderByDesc('called_at')->orderByDesc('id');

        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function ($w) use ($q) {
                $w->where('source', 'like', "%{$q}%")
                    ->orWhere('destination', 'like', "%{$q}%")
                    ->orWhere('uniqueid', 'like', "%{$q}%")
                    ->orWhere('directory', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('sync_status', $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('called_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('called_at', '<=', $request->input('date_to'));
        }

        $recordings = $query->paginate(30)->withQueryString();
        $credsOk = $this->credentialsReady($setting);

        return view('admin.call_recordings', compact('recordings', 'setting', 'credsOk'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'netsantral_usercode' => 'nullable|string|max:100',
            'netsantral_password' => 'nullable|string|max:255',
            'netsantral_pbxnum' => 'nullable|string|max:32',
            'netsipp_api_key' => 'nullable|string|max:2000',
        ]);

        $setting = Setting::firstOrFail();

        $password = $request->input('netsantral_password');
        if ($password === null || $password === '' || str_contains((string) $password, '****')) {
            $password = $setting->netsantral_password;
        }

        $apiKey = $request->input('netsipp_api_key');
        if ($apiKey === null || $apiKey === '' || str_contains((string) $apiKey, '****')) {
            $apiKey = $setting->netsipp_api_key;
        }

        $setting->update([
            'netsantral_usercode' => $request->input('netsantral_usercode'),
            'netsantral_password' => $password,
            'netsantral_pbxnum' => $this->normalizePbxnum($request->input('netsantral_pbxnum')),
            'netsantral_enabled' => $request->boolean('netsantral_enabled'),
            'netsipp_api_key' => $apiKey,
        ]);

        return redirect()->route('admin.call-recordings')->with([
            'messege' => 'Netsantral / Netsipp API ayarları kaydedildi.',
            'alert-type' => 'success',
        ]);
    }

    private function normalizePbxnum(mixed $raw): ?string
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

    private function credentialsReady(?Setting $setting): bool
    {
        if (! $setting) {
            return false;
        }

        $netsippKey = trim((string) ($setting->netsipp_api_key ?? ''));
        if ($setting->netsantral_enabled && $netsippKey !== '') {
            return true;
        }

        $santralUser = trim((string) ($setting->netsantral_usercode ?? ''));
        $santralPass = trim((string) ($setting->netsantral_password ?? ''));
        if ($santralUser !== '' && $santralPass !== '') {
            return (bool) $setting->netsantral_enabled;
        }

        return trim((string) ($setting->netgsm_usercode ?? '')) !== ''
            && trim((string) ($setting->netgsm_password ?? '')) !== '';
    }

    public function sync(Request $request, NetsantralCallRecordingService $service)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'download_audio' => 'nullable',
        ]);

        if (! $this->credentialsReady(Setting::first())) {
            return redirect()->back()->with([
                'messege' => 'API ayarları eksik veya pasif. Üstteki formdan kullanıcı kodu/şifre girip aktif edin.',
                'alert-type' => 'error',
            ]);
        }

        $from = Carbon::parse($request->input('date_from'))->startOfDay();
        $to = Carbon::parse($request->input('date_to'))->endOfDay();

        if ($from->diffInDays($to) > 31) {
            return redirect()->back()->with([
                'messege' => 'Tek seferde en fazla 31 günlük aralık senkronlayın.',
                'alert-type' => 'error',
            ]);
        }

        try {
            $result = $service->syncDateRange(
                $from,
                $to,
                $request->has('download_audio')
            );
        } catch (\Throwable $e) {
            Log::error('Call recording sync failed', ['message' => $e->getMessage()]);

            return redirect()->back()->with([
                'messege' => 'Senkron hatası: ' . $e->getMessage(),
                'alert-type' => 'error',
            ]);
        }

        if (! empty($result['message']) && ($result['fetched'] ?? 0) === 0) {
            return redirect()->back()->with([
                'messege' => $result['message'],
                'alert-type' => 'error',
            ]);
        }

        $msg = sprintf(
            'CDR: %d kayıt çekildi, %d kaydedildi, %d ses indirildi, %d hata.',
            $result['fetched'],
            $result['upserted'],
            $result['downloaded'],
            $result['failed']
        );

        if (! empty($result['message'])) {
            $msg .= ' ' . $result['message'];
        }

        $type = (($result['fetched'] ?? 0) === 0) ? 'warning' : 'success';
        if (($result['fetched'] ?? 0) === 0) {
            $msg = 'Bu tarih aralığında CDR kaydı gelmedi. Netsipp API key + Aktif veya Netgsm usercode/şifreyi kontrol edin.';
        }
        if (($result['downloaded'] ?? 0) === 0 && ($result['fetched'] ?? 0) > 0 && ! empty($result['message'])) {
            $type = 'warning';
        }

        return redirect()->back()->with([
            'messege' => $msg,
            'alert-type' => $type,
        ]);
    }

    public function download(int $id, NetsantralCallRecordingService $service)
    {
        $recording = CallRecording::findOrFail($id);

        try {
            if (! $recording->hasLocalAudio()) {
                $service->downloadAudio($recording);
                $recording->refresh();
            }
        } catch (\Throwable $e) {
            return redirect()->back()->with([
                'messege' => 'Ses indirilemedi: ' . $e->getMessage(),
                'alert-type' => 'error',
            ]);
        }

        if (! $recording->hasLocalAudio()) {
            return redirect()->back()->with([
                'messege' => 'Yerel ses dosyası yok.',
                'alert-type' => 'error',
            ]);
        }

        return response()->download(public_path($recording->local_path));
    }

    public function play(int $id)
    {
        $recording = CallRecording::findOrFail($id);
        if (! $recording->hasLocalAudio()) {
            abort(404, 'Ses dosyası yok');
        }

        return response()->file(public_path($recording->local_path));
    }
}
