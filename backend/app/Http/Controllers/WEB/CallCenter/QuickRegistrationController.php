<?php

namespace App\Http\Controllers\WEB\CallCenter;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\CountryState;
use App\Models\Setting;
use App\Models\Vendor;
use App\Services\CallCenter\CallCenterSellerReminderService;
use App\Services\CallCenter\QuickSellerOnboardingStatus;
use App\Services\CallCenter\QuickSellerRegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class QuickRegistrationController extends Controller
{
    public function __construct(
        protected QuickSellerRegistrationService $registrationService,
        protected CallCenterSellerReminderService $reminderService
    ) {
        $this->middleware(['auth:admin', 'call-center']);
    }

    public function create()
    {
        $setting = Setting::first();
        $categories = Category::query()->where('status', 1)->orderBy('name')->get(['id', 'name']);
        $country = Country::query()->where('slug', 'turkiye')->first();
        $states = $country
            ? CountryState::query()->where('country_id', $country->id)->where('status', 1)->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('call-center.registrations.create', compact('setting', 'categories', 'states', 'country'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shop_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'login_channel' => 'required|in:sms,email,both',
            'phone' => 'required_if:login_channel,sms,both|nullable|string|max:20',
            'email' => 'required_if:login_channel,email,both|nullable|email|max:255',
            'state_id' => 'nullable|integer|exists:country_states,id',
            'city_id' => 'nullable|integer|exists:cities,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'note' => 'nullable|string|max:1000',
        ]);

        try {
            $result = $this->registrationService->register(
                Auth::guard('admin')->user(),
                $validated
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->with(['messege' => $exception->getMessage(), 'alert-type' => 'error']);
        }

        $message = 'Satıcı kaydı oluşturuldu.';
        $alertType = 'success';
        $loginChannel = $validated['login_channel'];
        $hasRealEmail = $result->user->email
            && ! \App\Services\CallCenter\QuickSellerRegistrationService::isPendingEmail($result->user->email);

        if ($loginChannel === 'both' && $result->smsSent && $result->emailSent) {
            $message .= ' Giriş bilgileri SMS ve e-posta ile gönderildi.';
        } elseif ($loginChannel === 'both' && $result->smsSent && ! $result->emailSent) {
            $message .= ' SMS gönderildi. E-posta gönderilemedi'.($result->emailError ? ': '.$result->emailError : '.');
            $alertType = 'warning';
        } elseif ($loginChannel === 'both' && ! $result->smsSent && $result->emailSent) {
            $message .= ' E-posta gönderildi. SMS gönderilemedi'.($result->smsError ? ': '.$result->smsError : '.');
            $alertType = 'warning';
        } elseif ($loginChannel === 'both') {
            $message .= ' SMS ve e-posta gönderilemedi.';
            if ($result->smsError) {
                $message .= ' SMS: '.$result->smsError;
            }
            if ($result->emailError) {
                $message .= ' E-posta: '.$result->emailError;
            }
            $alertType = 'error';
        } elseif ($loginChannel === 'email' && $result->emailSent) {
            $message .= ' Giriş bilgileri e-posta ile gönderildi.';
        } elseif ($loginChannel === 'email') {
            $message .= ' Kayıt oluşturuldu ancak e-posta gönderilemedi'.($result->emailError ? ': '.$result->emailError : '.');
            $alertType = 'error';
        } elseif ($result->smsSent) {
            $message .= ' Giriş bilgileri SMS ile gönderildi.';
        } else {
            $message .= ' SMS gönderilemedi'.($result->smsError ? ': '.$result->smsError : '.');
            $alertType = 'error';
        }

        return redirect()
            ->route('call-center.registrations.show', $result->vendor->id)
            ->with([
                'messege' => $message,
                'alert-type' => $alertType,
                'registration_result' => [
                    'shop_name' => $result->vendor->shop_name,
                    'email' => $hasRealEmail ? $result->user->email : null,
                    'phone' => $result->user->phone,
                    'login_channel' => $loginChannel,
                    'sms_sent' => $result->smsSent,
                    'email_sent' => $result->emailSent,
                    'sms_error' => $result->smsError,
                    'email_error' => $result->emailError,
                    'email_skipped' => ! $hasRealEmail,
                    'was_existing_user' => $result->wasExistingUser,
                ],
            ]);
    }

    public function index()
    {
        $agent = Auth::guard('admin')->user();

        $registrations = Vendor::query()
            ->with(['user', 'registeredByAdmin'])
            ->withCount('products')
            ->where('registration_source', 'call_center')
            ->where('registered_by_admin_id', $agent->id)
            ->latest('id')
            ->paginate(20);

        $registrations->getCollection()->transform(function (Vendor $vendor) {
            $vendor->setAttribute('onboarding', QuickSellerOnboardingStatus::for($vendor));

            return $vendor;
        });

        return view('call-center.registrations.index', compact('registrations'));
    }

    public function show(int $id)
    {
        $agent = Auth::guard('admin')->user();

        $vendor = Vendor::query()
            ->with(['user', 'registeredByAdmin'])
            ->withCount('products')
            ->where('registration_source', 'call_center')
            ->where('registered_by_admin_id', $agent->id)
            ->findOrFail($id);

        $registrationResult = session('registration_result');
        $onboarding = QuickSellerOnboardingStatus::for($vendor);
        $reminderOptions = $this->reminderService->availableReminders($vendor);

        return view('call-center.registrations.show', compact('vendor', 'registrationResult', 'onboarding', 'reminderOptions'));
    }

    public function resendSms(int $id)
    {
        $agent = Auth::guard('admin')->user();

        $vendor = Vendor::query()
            ->with('user')
            ->where('registration_source', 'call_center')
            ->where('registered_by_admin_id', $agent->id)
            ->findOrFail($id);

        try {
            $this->registrationService->resendFirstLoginSms($vendor);
        } catch (RuntimeException $exception) {
            return back()->with(['messege' => $exception->getMessage(), 'alert-type' => 'error']);
        }

        return back()->with([
            'messege' => 'Hoş geldin SMS\'i aynı giriş şifresiyle yeniden gönderildi.',
            'alert-type' => 'success',
        ]);
    }

    public function resendEmail(int $id)
    {
        $agent = Auth::guard('admin')->user();

        $vendor = Vendor::query()
            ->with('user')
            ->where('registration_source', 'call_center')
            ->where('registered_by_admin_id', $agent->id)
            ->findOrFail($id);

        try {
            $this->registrationService->resendFirstLoginEmail($vendor);
        } catch (RuntimeException $exception) {
            return back()->with(['messege' => $exception->getMessage(), 'alert-type' => 'error']);
        }

        return back()->with([
            'messege' => 'Hoş geldin e-postası aynı giriş şifresiyle yeniden gönderildi.',
            'alert-type' => 'success',
        ]);
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'shop_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'send_sms' => 'nullable|boolean',
        ]);

        $agent = Auth::guard('admin')->user();

        $vendor = Vendor::query()
            ->with('user')
            ->where('registration_source', 'call_center')
            ->where('registered_by_admin_id', $agent->id)
            ->findOrFail($id);

        $sendSms = $request->has('send_sms') ? $request->boolean('send_sms') : false;

        try {
            $this->registrationService->updateRegistration($vendor, [
                'shop_name' => $validated['shop_name'],
                'contact_name' => $validated['contact_name'],
                'phone' => $validated['phone'] ?? null,
                'send_sms' => $sendSms,
            ]);
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->with(['messege' => $exception->getMessage(), 'alert-type' => 'error']);
        }

        $message = 'Kayıt bilgileri güncellendi.';
        if ($sendSms) {
            $message .= ' Hoş geldin SMS\'i gönderildi.';
        }

        return back()->with([
            'messege' => $message,
            'alert-type' => 'success',
        ]);
    }

    public function updatePhone(Request $request, int $id)
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'send_sms' => 'nullable|boolean',
        ]);

        $agent = Auth::guard('admin')->user();

        $vendor = Vendor::query()
            ->with('user')
            ->where('registration_source', 'call_center')
            ->where('registered_by_admin_id', $agent->id)
            ->findOrFail($id);

        try {
            $this->registrationService->updateRegistration($vendor, [
                'shop_name' => (string) $vendor->shop_name,
                'contact_name' => (string) ($vendor->user?->name ?? ''),
                'phone' => $validated['phone'],
                'send_sms' => $request->has('send_sms') ? $request->boolean('send_sms') : true,
            ]);
        } catch (RuntimeException $exception) {
            return back()->with(['messege' => $exception->getMessage(), 'alert-type' => 'error']);
        }

        return back()->with([
            'messege' => 'Telefon numarası güncellendi.'
                .(($request->has('send_sms') ? $request->boolean('send_sms') : true) ? ' Hoş geldin SMS\'i yeni numaraya gönderildi.' : ''),
            'alert-type' => 'success',
        ]);
    }

    public function sendReminder(Request $request, int $id)
    {
        $validated = $request->validate([
            'template_slug' => 'required|string|max:64',
        ]);

        $agent = Auth::guard('admin')->user();

        $vendor = Vendor::query()
            ->with('user')
            ->withCount('products')
            ->where('registration_source', 'call_center')
            ->where('registered_by_admin_id', $agent->id)
            ->findOrFail($id);

        try {
            $this->reminderService->send($vendor, $validated['template_slug']);
        } catch (RuntimeException $exception) {
            return back()->with(['messege' => $exception->getMessage(), 'alert-type' => 'error']);
        }

        return back()->with([
            'messege' => 'Hatırlatma SMS\'i gönderildi.',
            'alert-type' => 'success',
        ]);
    }

    public function cities(int $stateId)
    {
        $cities = City::query()
            ->where('country_state_id', $stateId)
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['cities' => $cities]);
    }
}
