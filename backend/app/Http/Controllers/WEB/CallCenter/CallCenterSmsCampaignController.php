<?php

namespace App\Http\Controllers\WEB\CallCenter;

use App\Http\Controllers\WEB\Admin\SmsCampaignController;
use App\Models\SmsCampaign;
use App\Models\SmsCampaignMessage;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vendor;
use App\Services\SmsServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CallCenterSmsCampaignController extends SmsCampaignController
{
    public function index()
    {
        $admin = Auth::guard('admin')->user();
        $campaigns = SmsCampaign::where('sent_by', $admin->id)
            ->where('sent_by_type', 'call_center')
            ->orderByDesc('id')
            ->paginate(20);
        $setting = Setting::first();

        return view('call-center.sms_campaigns.index', compact('campaigns', 'setting'));
    }

    public function create()
    {
        $setting = Setting::first();
        $segments = $this->getSegments();
        $messages = SmsCampaignMessage::where('is_active', true)->orderBy('title')->get();

        return view('call-center.sms_campaigns.create', compact('setting', 'segments', 'messages'));
    }

    public function store(Request $request, SmsServiceInterface $sms)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:600',
            'segment' => 'required|string|in:all,never_logged_in,logged_in,has_products,logged_in_no_products',
            'selected_phones' => 'required|array|min:1',
            'selected_phones.*' => 'string',
        ]);

        $phones = collect($request->selected_phones)->filter();

        if ($phones->isEmpty()) {
            return redirect()->back()
                ->withInput()
                ->with(['messege' => 'Lütfen en az bir kullanıcı seçin.', 'alert-type' => 'error']);
        }

        $admin = Auth::guard('admin')->user();

        $campaign = SmsCampaign::create([
            'title' => $request->title,
            'message' => $request->message,
            'segment' => $request->segment,
            'total_recipients' => $phones->count(),
            'sent_by' => $admin->id,
            'sent_by_type' => 'call_center',
            'status' => 'sending',
        ]);

        $sentCount = 0;
        $failedCount = 0;

        foreach ($phones as $phone) {
            try {
                $result = $sms->sendTransactional($phone, $request->message);
                $result ? $sentCount++ : $failedCount++;
            } catch (\Exception $e) {
                Log::error('SMS campaign send error', [
                    'phone' => $phone,
                    'error' => $e->getMessage(),
                ]);
                $failedCount++;
            }
        }

        $campaign->update([
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'status' => 'completed',
            'sent_at' => now(),
        ]);

        return redirect()->route('call-center.sms-campaigns.index')
            ->with(['messege' => "SMS gönderildi: {$sentCount} başarılı, {$failedCount} başarısız.", 'alert-type' => 'success']);
    }

    public function show($id)
    {
        $admin = Auth::guard('admin')->user();
        $campaign = SmsCampaign::where('sent_by', $admin->id)
            ->where('sent_by_type', 'call_center')
            ->findOrFail($id);
        $setting = Setting::first();

        return view('call-center.sms_campaigns.show', compact('campaign', 'setting'));
    }

    public function usersForSegment(Request $request)
    {
        $request->validate([
            'segment' => 'required|string|in:all,never_logged_in,logged_in,has_products,logged_in_no_products',
        ]);

        $admin = Auth::guard('admin')->user();

        $myVendorUserIds = Vendor::where('registered_by_admin_id', $admin->id)
            ->pluck('user_id');

        $query = User::whereIn('id', $myVendorUserIds)
            ->whereNotNull('phone')
            ->where('phone', '!=', '');

        switch ($request->segment) {
            case 'never_logged_in':
                $query->whereNull('last_login_at');
                break;
            case 'logged_in':
                $query->whereNotNull('last_login_at');
                break;
            case 'has_products':
                $query->whereHas('seller', function ($q) {
                    $q->whereHas('products');
                });
                break;
            case 'logged_in_no_products':
                $query->whereNotNull('last_login_at')
                    ->where(function ($q) {
                        $q->whereDoesntHave('seller')
                          ->orWhereHas('seller', function ($sq) {
                              $sq->whereDoesntHave('products');
                          });
                    });
                break;
        }

        $users = $query->select('id', 'name', 'phone')->orderBy('name')->get();

        return response()->json([
            'count' => $users->count(),
            'segment_label' => $this->getSegments()[$request->segment] ?? $request->segment,
            'users' => $users,
        ]);
    }

    protected function getSegments(): array
    {
        return [
            'all' => 'Tüm Kayıtlarım',
            'never_logged_in' => 'Giriş yapmayanlar',
            'logged_in' => 'Giriş yapanlar',
            'has_products' => 'Ürün yükleyenler',
            'logged_in_no_products' => 'Giriş yapıp ürün yüklemeyenler',
        ];
    }
}
