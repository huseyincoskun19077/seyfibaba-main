<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\MobileSlider;
use File;
use Illuminate\Http\Request;

class MobileSliderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(Request $request)
    {
        $sliders = MobileSlider::query()->orderBy('serial')->orderBy('id')->get();
        $editSlider = null;
        if ($request->filled('edit')) {
            $editSlider = MobileSlider::query()->find((int) $request->query('edit'));
        }

        return view('admin.mobile_slider', compact('sliders', 'editSlider'));
    }

    public function store(Request $request)
    {
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
