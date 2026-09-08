<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\MobileSlider;
use File;
use Illuminate\Http\Request;
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

        $request->validate([
            'image' => [$request->filled('id') ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'link' => ['nullable', 'string', 'max:500'],
            'product_slug' => ['nullable', 'string', 'max:255'],
            'serial' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            $slider = $request->filled('id')
                ? MobileSlider::query()->findOrFail((int) $request->input('id'))
                : new MobileSlider();

            $slider->title = trim((string) $request->input('title', ''));
            $slider->subtitle = trim((string) $request->input('subtitle', ''));
            $slider->link = trim((string) $request->input('link', ''));
            $slider->product_slug = trim((string) $request->input('product_slug', ''));
            $slider->serial = (int) ($request->input('serial') ?: 1);
            $slider->status = $request->boolean('status');

            if ($request->hasFile('image')) {
                $dir = public_path('uploads/website-images');
                if (! File::isDirectory($dir)) {
                    File::makeDirectory($dir, 0755, true);
                }

                $file = $request->file('image');
                $name = 'mobile-slider-'.date('Y-m-d-His').'-'.rand(1000, 9999).'.'.$file->getClientOriginalExtension();
                $rel = 'uploads/website-images/'.$name;
                $file->move($dir, $name);
                if ($slider->image && File::exists(public_path($slider->image))) {
                    File::delete(public_path($slider->image));
                }
                $slider->image = $rel;
            } elseif (! $slider->exists) {
                return redirect()->back()->withInput()->with([
                    'messege' => 'Görsel zorunludur',
                    'alert-type' => 'error',
                ]);
            }

            $slider->save();
        } catch (Throwable $e) {
            report($e);

            return redirect()->back()->withInput()->with([
                'messege' => 'Kayıt başarısız: '.$e->getMessage(),
                'alert-type' => 'error',
            ]);
        }

        return redirect()->route('admin.mobile-slider.index')->with([
            'messege' => $request->filled('id') ? 'Mobil slider güncellendi' : 'Mobil slider eklendi',
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
}
