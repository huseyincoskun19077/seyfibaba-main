<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsCampaign;
use App\Models\SmsCampaignMessage;
use App\Models\User;
use App\Models\Setting;
use App\Services\CallCenter\QuickSellerRegistrationService;
use App\Services\SmsServiceInterface;
use App\Support\OtpMessageBuilder;
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
        $msgheader = $this->resolveMsgHeader($setting);

        return view('admin.sms_campaigns.create', compact('setting', 'segments', 'messages', 'msgheader'));
    }

    public function store(Request $request, SmsServiceInterface $sms, ?QuickSellerRegistrationService $quickSeller = null)
    {
        $quickSeller = $quickSeller ?? app(QuickSellerRegistrationService::class);
        $includeOtp = $request->boolean('include_first_login_otp');

        $allowedSegments = array_keys($this->getSegments());
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'nullable|string|max:600',
            'segment' => 'required|string|in:'.implode(',', $allowedSegments),
            'selected_user_ids' => 'nullable|array|min:1',
            'selected_user_ids.*' => 'integer|exists:users,id',
        ]);

        $intro = trim((string) $request->message);

        if (! $includeOtp && $intro === '') {
            return redirect()->back()
                ->withInput()
                ->with(['messege' => 'Mesaj metni zorunludur.', 'alert-type' => 'error']);
        }

        $selectedIds = collect($request->input('selected_user_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($request->segment === 'custom' && $selectedIds->isEmpty()) {
            return redirect()->back()
                ->withInput()
                ->with(['messege' => 'Özel seçimde en az bir kullanıcı seçin.', 'alert-type' => 'error']);
        }

        if ($includeOtp && $selectedIds->isEmpty() && ! in_array($request->segment, [
            'sellers_awaiting_first_login',
            'never_logged_in',
            'sellers_never_logged_in',
            'custom',
        ], true)) {
            return redirect()->back()
                ->withInput()
                ->with([
                    'messege' => 'Tek kullanımlık şifre için uygun segment veya özel seçim kullanın.',
                    'alert-type' => 'error',
                ]);
        }

        if ($selectedIds->isNotEmpty()) {
            $users = User::query()
                ->whereIn('id', $selectedIds)
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->select('id', 'name', 'phone', 'email', 'must_change_password')
                ->get();

            if ($includeOtp) {
                $users = $users->filter(fn ($u) => (bool) $u->must_change_password)->values();
            }
        } else {
            $users = $this->getUsersForSegment($request->segment, $includeOtp);
        }

        if ($users->isEmpty()) {
            return redirect()->back()
                ->withInput()
                ->with(['messege' => 'Gönderilecek alıcı bulunamadı.', 'alert-type' => 'error']);
        }

        $admin = Auth::guard('admin')->user();
        $storedMessage = $includeOtp
            ? "[Tek kullanımlık şifre + tanıtım]\n".$intro
            : $intro;
        $segmentLabel = $request->segment === 'custom' || $selectedIds->isNotEmpty()
            ? 'custom:'.$users->count()
            : $request->segment;

        $campaign = SmsCampaign::create([
            'title' => $request->title,
            'message' => $storedMessage,
            'segment' => $segmentLabel,
            'total_recipients' => $users->count(),
            'sent_by' => $admin->id,
            'sent_by_type' => 'admin',
            'status' => 'sending',
        ]);

        $sentCount = 0;
        $failedCount = 0;

        foreach ($users as $user) {
            $phone = trim((string) $user->phone);
            if ($phone === '') {
                $failedCount++;
                continue;
            }

            try {
                $body = $intro;

                if ($includeOtp) {
                    $payload = $quickSeller->ensureFirstLoginOtpPayload($user);
                    if (! $payload) {
                        $failedCount++;
                        continue;
                    }

                    $otpBlock = OtpMessageBuilder::buildCallCenterWelcome(
                        $payload['login_username'],
                        $payload['otp']
                    );
                    $body = $intro !== ''
                        ? $intro."\n\n".$otpBlock
                        : $otpBlock;
                }

                $result = $sms->sendTransactional($phone, $body);
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
        $includeOtp = $request->boolean('include_first_login_otp');
        $allowedSegments = array_keys($this->getSegments());
        $request->validate([
            'segment' => 'required|string|in:'.implode(',', $allowedSegments),
        ]);

        if ($request->segment === 'custom') {
            return response()->json([
                'count' => 0,
                'segment_label' => $this->getSegments()['custom'],
            ]);
        }

        $users = $this->getUsersForSegment($request->segment, $includeOtp);

        return response()->json([
            'count' => $users->count(),
            'segment_label' => $this->getSegments()[$request->segment] ?? $request->segment,
        ]);
    }

    public function searchUsers(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['users' => []]);
        }

        $digits = preg_replace('/\D+/', '', $q) ?? '';
        $like = '%'.$q.'%';

        $users = User::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->where(function ($query) use ($like, $digits) {
                $query->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('shop_name', 'like', $like)
                    ->orWhereHas('seller', function ($seller) use ($like) {
                        $seller->where('shop_name', 'like', $like);
                    });

                if ($digits !== '' && strlen($digits) >= 3) {
                    $query->orWhere('phone', 'like', '%'.$digits.'%');
                }
            })
            ->with(['seller:id,user_id,shop_name'])
            ->select('id', 'name', 'phone', 'shop_name', 'must_change_password')
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->map(fn ($u) => $this->mapUserRow($u))
            ->values();

        return response()->json(['users' => $users]);
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
            'custom' => 'Özel seçim (dükkan / ad / telefon ara)',
            'sellers_awaiting_first_login' => 'Satıcılar: henüz giriş yapmamış (tek kullanımlık şifre)',
            'sellers_never_logged_in' => 'Satıcılar: hiç giriş kaydı yok',
            'never_logged_in' => 'Tüm kullanıcılar: SMS gidip giriş yapmayanlar',
            'all' => 'Tüm Kullanıcılar',
            'logged_in' => 'Giriş yapanlar',
            'has_products' => 'Ürün yükleyenler',
            'logged_in_no_products' => 'Giriş yapıp ürün yüklemeyenler',
        ];
    }

    protected function getSegmentQuery(string $segment)
    {
        $query = User::whereNotNull('phone')->where('phone', '!=', '');

        switch ($segment) {
            case 'custom':
                // Arama ile doldurulur
                $query->whereRaw('1 = 0');
                break;
            case 'sellers_awaiting_first_login':
                $query->whereHas('seller')
                    ->where('must_change_password', true);
                break;
            case 'sellers_never_logged_in':
                $query->whereHas('seller')
                    ->whereNull('last_login_at');
                break;
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

    protected function getUsersForSegment(string $segment, bool $includeOtp = false)
    {
        $query = $this->getSegmentQuery($segment);

        if ($includeOtp) {
            $query->whereHas('seller')
                ->where('must_change_password', true);
        }

        return $query
            ->with(['seller:id,user_id,shop_name'])
            ->select('id', 'name', 'phone', 'email', 'shop_name', 'must_change_password')
            ->orderBy('name')
            ->get();
    }

    protected function mapUserRow(User $u): array
    {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'phone' => $u->phone,
            'shop_name' => $u->seller->shop_name ?? $u->shop_name,
            'must_change_password' => (bool) $u->must_change_password,
        ];
    }

    protected function resolveMsgHeader(?Setting $setting): string
    {
        $fromSetting = trim((string) ($setting->netgsm_msgheader ?? ''));
        if ($fromSetting !== '') {
            return $fromSetting;
        }

        $fromConfig = trim((string) config('sms.providers.netgsm.msgheader', ''));

        return $fromConfig !== '' ? $fromConfig : 'KUAFÖR TEDARİK';
    }

    public function usersForSegment(Request $request)
    {
        $includeOtp = $request->boolean('include_first_login_otp');
        $allowedSegments = array_keys($this->getSegments());
        $request->validate([
            'segment' => 'required|string|in:'.implode(',', $allowedSegments),
        ]);

        if ($request->segment === 'custom') {
            return response()->json([
                'count' => 0,
                'segment_label' => $this->getSegments()['custom'],
                'users' => [],
            ]);
        }

        $users = $this->getUsersForSegment($request->segment, $includeOtp)
            ->map(fn ($u) => $this->mapUserRow($u))
            ->values();

        return response()->json([
            'count' => $users->count(),
            'segment_label' => $this->getSegments()[$request->segment] ?? $request->segment,
            'users' => $users,
        ]);
    }
}
