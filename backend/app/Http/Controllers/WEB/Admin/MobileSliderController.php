<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\MobileSlider;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class MobileSliderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(Request $request)
    {
        try {
            if (! Schema::hasTable('mobile_sliders')) {
                return redirect()->route('admin.slider.index')->with([
                    'messege' => 'mobile_sliders tablosu yok. Sunucuda çalıştırın: cd /opt/seyfibaba-main/backend && php artisan migrate --force',
                    'alert-type' => 'error',
                ]);
            }

            $sliders = MobileSlider::query()->orderBy('serial')->orderBy('id')->get();
            $editSlider = null;
            if ($request->filled('edit')) {
                $editSlider = MobileSlider::query()->find((int) $request->query('edit'));
            }

            return response(view('admin.mobile_slider', compact('sliders', 'editSlider'))->render());
        } catch (\Throwable $e) {
            \Log::error('admin.mobile-slider index failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $payload = json_encode([
                'where' => 'admin.mobile-slider',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], JSON_UNESCAPED_UNICODE);

            return response(
                '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Mobile Slider 500</title></head><body>'
                .'<h1>admin/mobile-slider hata</h1><pre>'.e($e->getMessage())."\n".$e->getFile().':'.$e->getLine().'</pre>'
                .'<p>Detay F12 → Console</p>'
                .'<script>console.error("[admin.mobile-slider 500]", '.$payload.');</script>'
                .'</body></html>',
                500
            );
        }
    }

    public function store(Request $request)
    {
        if (! Schema::hasTable('mobile_sliders')) {
            return redirect()->route('admin.slider.index')->with([
                'messege' => 'mobile_sliders tablosu yok. Önce migrate çalıştırın.',
                'alert-type' => 'error',
            ]);
        }

        $request->validate([
            'image' => [$request->filled('id') ? 'nullable' : 'required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'link' => ['nullable', 'string', 'max:500'],
            'product_slug' => ['nullable', 'string', 'max:255'],
            'serial' => ['nullable', 'integer', 'min:0'],
        ]);

        $slider = $request->filled('id')
            ? MobileSlider::query()->findOrFail((int) $request->input('id'))
            : new MobileSlider();

        $slider->title = trim((string) $request->input('title', '')) ?: null;
        $slider->subtitle = trim((string) $request->input('subtitle', '')) ?: null;
        $slider->link = trim((string) $request->input('link', '')) ?: null;
        $slider->product_slug = trim((string) $request->input('product_slug', '')) ?: null;
        $slider->serial = (int) ($request->input('serial') ?: 1);
        $slider->status = $request->boolean('status');

        if ($request->hasFile('image')) {
            $dir = public_path('uploads/website-images');
            if (! File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
            $file = $request->file('image');
            $name = 'mobile-slider-'.date('Y-m-d-His').'-'.rand(1000, 9999).'.'.$file->getClientOriginalExtension();
            $file->move($dir, $name);
            if ($slider->image && File::exists(public_path($slider->image))) {
                File::delete(public_path($slider->image));
            }
            $slider->image = 'uploads/website-images/'.$name;
        }

        $slider->save();

        return redirect()->route('admin.mobile-slider.index')->with([
            'messege' => 'Kaydedildi',
            'alert-type' => 'success',
        ]);
    }

    public function destroy($id)
    {
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
}
