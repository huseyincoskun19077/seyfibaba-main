<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\RecommendationSetting;
use Illuminate\Http\Request;

class RecommendationSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function edit()
    {
        $settings = RecommendationSetting::current();

        return view('admin.recommendation_settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'weight_segment' => 'required|integer|min:0|max:100',
            'weight_browse_history' => 'required|integer|min:0|max:100',
            'weight_business_type' => 'required|integer|min:0|max:100',
            'weight_popularity' => 'required|integer|min:0|max:100',
            'history_days' => 'required|integer|min:1|max:365',
            'min_views_for_signal' => 'required|integer|min:1|max:20',
            'vendor_diversity' => 'required|integer|min:1|max:20',
            'exclude_viewed_product' => 'nullable|boolean',
        ]);

        $settings = RecommendationSetting::current();
        $settings->fill($data);
        $settings->exclude_viewed_product = $request->boolean('exclude_viewed_product');
        $settings->save();

        return back()->with(['messege' => 'Öneri ayarları kaydedildi.', 'alert-type' => 'success']);
    }
}
