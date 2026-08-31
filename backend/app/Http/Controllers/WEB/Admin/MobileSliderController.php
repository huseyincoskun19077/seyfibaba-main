<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\MobileSlider;
use App\Models\Product;
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

        $products = Product::where(['status' => 1])->select('id', 'name', 'slug')->orderBy('name')->get();

        return view('admin.mobile_slider', compact('sliders', 'editSlider', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => [$request->filled('id') ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'link' => ['nullable', 'string', 'max:500'],
            'product_slug' => ['nullable', 'string', 'max:255'],
            'serial' => ['nullable', 'integer', 'min:0'],
        ]);

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
            $file = $request->file('image');
            $name = 'mobile-slider-'.date('Y-m-d-His').'-'.rand(1000, 9999).'.'.$file->getClientOriginalExtension();
            $rel = 'uploads/website-images/'.$name;
            $file->move(public_path('uploads/website-images'), $name);
            if ($slider->image && File::exists(public_path($slider->image))) {
                File::delete(public_path($slider->image));
            }
            $slider->image = $rel;
        } elseif (! $slider->exists) {
            return redirect()->back()->with([
                'messege' => 'Görsel zorunludur',
                'alert-type' => 'error',
            ]);
        }

        $slider->save();

        return redirect()->route('admin.mobile-slider.index')->with([
            'messege' => trans('admin_validation.Update Successfully'),
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
