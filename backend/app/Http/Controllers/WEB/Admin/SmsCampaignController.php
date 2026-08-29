<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsCampaign;
use App\Models\SmsCampaignMessage;
use App\Models\User;
use App\Models\Setting;
use App\Services\SmsServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SmsCampaignController extends Controller
{
    public function index()
    {
        $campaigns = SmsCampaign::orderByDesc('id')->paginate(20);
        $setting = Setting::first();

        return view('admin.sms_campaigns.index', compact('campaigns', 'setting'));
    }

    public function create()
    {
        $setting = Setting::first();
        $segments = $this->getSegments();
        $messages = SmsCampaignMessage::where('is_active', true)->orderBy('title')->get();

        return view('admin.sms_campaigns.create', compact('setting', 'segments', 'messages'));
    }

    public function store(Request $request, SmsServiceInterface $sms)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:600',
            'segment' => 'required|string|in:all,never_logged_in,logged_in,has_products,logged_in_no_products',
        ]);

        $phones = $this->getPhonesForSegment($request->segment);

        if ($phones->isEmpty()) {
            return redirect()->back()
                ->withInput()
                ->with(['messege' => 'Bu segmentte telefon numarası bulunamadı.', 'alert-type' => 'error']);
        }

        $admin = Auth::guard('admin')->user();

        $campaign = SmsCampaign::create([
            'title' => $request->title,
            'message' => $request->message,
            'segment' => $request->segment,
            'total_recipients' => $phones->count(),
            'sent_by' => $admin->id,
            'sent_by_type' => 'admin',
            'status' => 'sending',
        ]);

        $sentCount = 0;
        $failedCount = 0;

        foreach ($phones as $phone) {
            try {
                $result = $sms->sendTransactional($phone, $request->message);
                $result ? $sentCount++ : $failedCount++;
            } catch (\Exception $e) {
                Log::error('SMS campaign send error', ['phone' => $phone, 'error' => $e->getMessage()]);
                $failedCount++;
            }
        }

        $campaign->update([
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'status' => 'completed',
            'sent_at' => now(),
        ]);

        return redirect()->route('admin.sms-campaigns.index')
            ->with(['messege' => "SMS gönderildi: {$sentCount} başarılı, {$failedCount} başarısız.", 'alert-type' => 'success']);
    }

    public function show($id)
    {
        $campaign = SmsCampaign::findOrFail($id);
        $setting = Setting::first();

        return view('admin.sms_campaigns.show', compact('campaign', 'setting'));
    }

    public function preview(Request $request)
    {
        $request->validate([
            'segment' => 'required|string|in:all,never_logged_in,logged_in,has_products,logged_in_no_products',
        ]);

        $phones = $this->getPhonesForSegment($request->segment);

        return response()->json([
            'count' => $phones->count(),
            'segment_label' => $this->getSegments()[$request->segment] ?? $request->segment,
        ]);
    }

    // --- Mesaj Şablonu Yönetimi ---

    public function messages()
    {
        $messages = SmsCampaignMessage::orderByDesc('id')->paginate(20);
        $setting = Setting::first();

        return view('admin.sms_campaigns.messages', compact('messages', 'setting'));
    }

    public function createMessage()
    {
        $setting = Setting::first();

        return view('admin.sms_campaigns.create_message', compact('setting'));
    }

    public function storeMessage(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:600',
        ]);

        SmsCampaignMessage::create([
            'title' => $request->title,
            'message' => $request->message,
            'char_count' => mb_strlen($request->message),
            'is_active' => true,
        ]);

        return redirect()->route('admin.sms-campaigns.messages')
            ->with(['messege' => 'Mesaj şablonu oluşturuldu.', 'alert-type' => 'success']);
    }

    public function editMessage($id)
    {
        $msg = SmsCampaignMessage::findOrFail($id);
        $setting = Setting::first();

        return view('admin.sms_campaigns.edit_message', compact('msg', 'setting'));
    }

    public function updateMessage(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:600',
        ]);

        $msg = SmsCampaignMessage::findOrFail($id);
        $msg->update([
            'title' => $request->title,
            'message' => $request->message,
            'char_count' => mb_strlen($request->message),
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.sms-campaigns.messages')
            ->with(['messege' => 'Mesaj şablonu güncellendi.', 'alert-type' => 'success']);
    }

    public function deleteMessage($id)
    {
        SmsCampaignMessage::findOrFail($id)->delete();

        return redirect()->route('admin.sms-campaigns.messages')
            ->with(['messege' => 'Mesaj şablonu silindi.', 'alert-type' => 'success']);
    }

    // --- Yardımcı Metodlar ---

    protected function getSegments(): array
    {
        return [
            'all' => 'Tüm Kullanıcılar',
            'never_logged_in' => 'SMS gidip giriş yapmayanlar',
            'logged_in' => 'Giriş yapanlar',
            'has_products' => 'Ürün yükleyenler',
            'logged_in_no_products' => 'Giriş yapıp ürün yüklemeyenler',
        ];
    }

    protected function getSegmentQuery(string $segment)
    {
        $query = User::whereNotNull('phone')->where('phone', '!=', '');

        switch ($segment) {
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

        return $query;
    }

    protected function getPhonesForSegment(string $segment)
    {
        return $this->getSegmentQuery($segment)->pluck('phone')->filter();
    }

    public function usersForSegment(Request $request)
    {
        $request->validate([
            'segment' => 'required|string|in:all,never_logged_in,logged_in,has_products,logged_in_no_products',
        ]);

        $users = $this->getSegmentQuery($request->segment)
            ->select('id', 'name', 'phone')
            ->orderBy('name')
            ->get();

        return response()->json([
            'count' => $users->count(),
            'segment_label' => $this->getSegments()[$request->segment] ?? $request->segment,
            'users' => $users,
        ]);
    }
}
