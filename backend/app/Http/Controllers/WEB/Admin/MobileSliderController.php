<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\MobileSlider;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class MobileSliderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(Request $request)
    {
        if (! Schema::hasTable('mobile_sliders')) {
            return redirect()->route('admin.slider.index')->with([
                'messege' => 'mobile_sliders tablosu yok. Sunucuda: php artisan migrate --force',
                'alert-type' => 'error',
            ]);
        }

        $sliders = MobileSlider::query()->orderBy('serial')->orderBy('id')->get();
        $editSlider = null;

        if ($request->filled('edit')) {
            $editSlider = MobileSlider::query()->find((int) $request->query('edit'));
        }

        return view('admin.mobile_slider', compact('sliders', 'editSlider'));
    }

    public function store(Request $request)
    {
        if (! Schema::hasTable('mobile_sliders')) {
            return redirect()->back()->with([
                'messege' => 'mobile_sliders tablosu yok. Sunucuda: php artisan migrate --force',
                'alert-type' => 'error',
            ]);
        }

        // PHP limiti aşılırsa dosya hiç gelmez; bunu net söyle
        if ($uploadError = $this->uploadErrorMessage($request, 'image')) {
            return redirect()->back()->withInput()->with([
                'messege' => $uploadError,
                'alert-type' => 'error',
            ]);
        }

        $isEdit = $request->filled('id');

        $request->validate([
            // 'image' kuralı bazı geçerli JPG/WEBP dosyalarını reddedebiliyor; uzantı yeterli
            'image' => [$isEdit ? 'nullable' : 'required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'link' => ['nullable', 'string', 'max:500'],
            'product_slug' => ['nullable', 'string', 'max:255'],
            'serial' => ['nullable', 'integer', 'min:0'],
        ], [
            'image.required' => 'Görsel seçmelisiniz.',
            'image.mimes' => 'Sadece JPG, PNG veya WEBP yükleyin.',
            'image.max' => 'Görsel en fazla 10 MB olabilir.',
        ]);

        try {
            $slider = $isEdit
                ? MobileSlider::query()->findOrFail((int) $request->input('id'))
                : new MobileSlider();

            $slider->title = trim((string) $request->input('title', '')) ?: null;
            $slider->subtitle = trim((string) $request->input('subtitle', '')) ?: null;
            $slider->link = trim((string) $request->input('link', '')) ?: null;
            $slider->product_slug = trim((string) $request->input('product_slug', '')) ?: null;
            $slider->serial = max(0, (int) ($request->input('serial') ?: 1));
            // Tek isimli checkbox + hidden karışıklığını önle
            $slider->status = (string) $request->input('is_active', '0') === '1';

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                if (! $file->isValid()) {
                    return redirect()->back()->withInput()->with([
                        'messege' => 'Görsel yüklenemedi (sunucu dosya limiti veya bozuk dosya). Daha küçük JPG deneyin.',
                        'alert-type' => 'error',
                    ]);
                }

                $dir = public_path('uploads/website-images');
                if (! File::isDirectory($dir)) {
                    File::makeDirectory($dir, 0755, true);
                }
                if (! is_writable($dir)) {
                    return redirect()->back()->withInput()->with([
                        'messege' => 'uploads/website-images yazılabilir değil. Sunucu izinlerini kontrol edin.',
                        'alert-type' => 'error',
                    ]);
                }

                $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    $ext = 'jpg';
                }
                $name = 'mobile-slider-'.date('Y-m-d-His').'-'.rand(1000, 9999).'.'.$ext;
                $rel = 'uploads/website-images/'.$name;

                if (! $file->move($dir, $name) || ! is_file($dir.DIRECTORY_SEPARATOR.$name)) {
                    return redirect()->back()->withInput()->with([
                        'messege' => 'Görsel diske yazılamadı.',
                        'alert-type' => 'error',
                    ]);
                }

                if ($slider->image && File::exists(public_path($slider->image))) {
                    File::delete(public_path($slider->image));
                }
                $slider->image = $rel;
            } elseif (! $slider->exists) {
                return redirect()->back()->withInput()->with([
                    'messege' => 'Görsel zorunludur. Dosya gelmediyse boyutu küçültüp JPG olarak tekrar deneyin.',
                    'alert-type' => 'error',
                ]);
            }

            $slider->save();
        } catch (Throwable $e) {
            Log::error('mobile slider save failed', ['error' => $e->getMessage()]);
            report($e);

            return redirect()->back()->withInput()->with([
                'messege' => 'Kayıt başarısız: '.$e->getMessage(),
                'alert-type' => 'error',
            ]);
        }

        return redirect()->route('admin.mobile-slider.index')->with([
            'messege' => $isEdit ? 'Mobil slider güncellendi' : 'Mobil slider eklendi',
            'alert-type' => 'success',
        ]);
    }

    public function destroy($id)
    {
        if (! Schema::hasTable('mobile_sliders')) {
            return redirect()->back()->with([
                'messege' => 'mobile_sliders tablosu yok.',
                'alert-type' => 'error',
            ]);
        }

        $slider = MobileSlider::query()->findOrFail((int) $id);
        if ($slider->image && File::exists(public_path($slider->image))) {
            File::delete(public_path($slider->image));
        }
        $slider->delete();

        return redirect()->route('admin.mobile-slider.index')->with([
            'messege' => 'Silindi',
            'alert-type' => 'success',
        ]);
    }

    private function uploadErrorMessage(Request $request, string $field): ?string
    {
        if (! isset($_FILES[$field])) {
            return null;
        }

        $error = (int) ($_FILES[$field]['error'] ?? UPLOAD_ERR_OK);
        if ($error === UPLOAD_ERR_OK || $error === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Görsel sunucu limitinden büyük. Daha küçük bir JPG deneyin (ör. 1–2 MB).',
            UPLOAD_ERR_PARTIAL => 'Görsel yarım yüklendi. Tekrar deneyin.',
            UPLOAD_ERR_NO_TMP_DIR => 'Sunucu geçici klasör hatası.',
            UPLOAD_ERR_CANT_WRITE => 'Sunucu diske yazamadı.',
            UPLOAD_ERR_EXTENSION => 'Yükleme bir eklenti tarafından engellendi.',
            default => 'Görsel yüklenemedi (hata kodu: '.$error.').',
        };
    }
}
