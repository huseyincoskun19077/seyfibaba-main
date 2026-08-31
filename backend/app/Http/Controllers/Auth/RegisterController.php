<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Rules\Captcha;
use Illuminate\Support\Facades\Cache;
use Auth;
use App\Mail\UserRegistration;
use App\Helpers\MailHelper;
use App\Models\EmailTemplate;
use App\Models\Setting;
use App\Support\PasswordRules;
use Mail;
use Str;
use Exception;
use App\Services\CallCenter\QuickSellerRegistrationService;
use App\Services\LegalConsentService;

class RegisterController extends Controller
{

    use RegistersUsers;


    protected $redirectTo = RouteServiceProvider::HOME;


    public function __construct()
    {
        $this->middleware('guest:api');
    }

    public function storeRegister(Request $request){
        $setting = Setting::first();
        $enable_phone_required = $setting->phone_number_required;

        $emailRule = $enable_phone_required == 1
            ? 'nullable|email|max:255|unique:users'
            : 'required|email|max:255|unique:users';

        $rules = array_merge([
            'name'=>'required',
            'agree'=>'required',
            'email'=> $emailRule,
            'phone'=> $enable_phone_required == 1 ? 'required|unique:users' : '',
            'g-recaptcha-response'=>new Captcha()
        ], PasswordRules::registerRules());
        $customMessages = array_merge([
            'name.required' => 'Ad soyad gereklidir.',
            'email.required' => trans('user_validation.Email is required'),
            'email.unique' => trans('user_validation.Email already exist'),
            'phone.required' => 'Telefon numarası gereklidir.',
            'phone.unique' => 'Bu telefon numarası ile zaten kayıt var.',
            'agree.required' => 'Devam etmek için gizlilik politikasını kabul etmelisiniz.',
        ], PasswordRules::messages());

        // OTP verification required when phone is required
        if ($enable_phone_required == 1) {
            $rules['otp_verified_token'] = 'required|string';
        }

        $this->validate($request, $rules, $customMessages);

        // Verify OTP token if phone is required
        if ($enable_phone_required == 1 && $request->phone && $request->otp_verified_token) {
            $cached = Cache::get('otp_verified_token:' . $request->otp_verified_token);

            // Normalize phone for comparison (strip non-digits, ensure +90 prefix)
            $normalizedPhone = preg_replace('/[^0-9]/', '', $request->phone);
            if (str_starts_with($normalizedPhone, '90') && strlen($normalizedPhone) === 12) {
                $normalizedPhone = '+' . $normalizedPhone;
            } elseif (strlen($normalizedPhone) === 10 && str_starts_with($normalizedPhone, '5')) {
                $normalizedPhone = '+90' . $normalizedPhone;
            } else {
                $normalizedPhone = str_starts_with($request->phone, '+') ? '+' . $normalizedPhone : $normalizedPhone;
            }

            if (!$cached || $cached['phone'] !== $normalizedPhone || $cached['purpose'] !== 'register') {
                return response()->json([
                    'message' => trans('user_validation.Phone number not verified'),
                ], 422);
            }

            // Consume the token so it can't be reused
            Cache::forget('otp_verified_token:' . $request->otp_verified_token);
        }

        $email = trim((string) $request->email);
        if ($email === '' && $enable_phone_required == 1) {
            $phoneDigits = preg_replace('/[^0-9]/', '', (string) ($normalizedPhone ?? $request->phone));
            $email = $this->placeholderEmailForPhone($phoneDigits);
        }

        $user = new User();
        $user->name = $request->name;
        $user->email = $email;
        $user->phone = $request->phone ? $normalizedPhone ?? $request->phone : '';
        $user->agree_policy = $request->agree ? 1 : 0;
        $user->password = Hash::make($request->password);
        $user->verify_token = random_int(100000, 999999);
        // If phone verified via OTP, auto-verify the user
        if ($enable_phone_required == 1 && $request->otp_verified_token) {
            $user->status = 1;
            $user->email_verified = 1;
        }
        $user->save();

        if ($request->filled('legal_consents') && is_array($request->legal_consents)) {
            app(LegalConsentService::class)->recordMany(
                $request,
                $request->legal_consents,
                ['user_id' => $user->id, 'context' => 'signup']
            );
        }

        // OTP ile doğrulanmış kullanıcıya email doğrulama maili göndermeye gerek yok
        if (!($enable_phone_required == 1 && $request->otp_verified_token)) {
            MailHelper::setMailConfig();
            $template = EmailTemplate::where('id', 4)->first();
            $subject = $template->subject;
            $message = $template->description;
            $message = str_replace('{{user_name}}', $request->name, $message);
            Mail::to($user->email)->send(new UserRegistration($message, $subject, $user));
        }

        $notification = ($enable_phone_required == 1 && $request->otp_verified_token)
            ? 'Kayıt başarılı! Giriş yapabilirsiniz.'
            : trans('user_validation.Register Successfully. Please Verify your email');
        return response()->json(['notification' => $notification]);
    }

    public function resendRegisterCode(Request $request){
        $rules = [
            'email'=>'required',
        ];
        $customMessages = [
            'email.required' => trans('user_validation.Email is required'),
        ];
        $this->validate($request, $rules, $customMessages);

        $user = User::where('email', $request->email)->first();
        if ($user) {
            if ($user->email_verified == 0) {
                MailHelper::setMailConfig();

                $template = EmailTemplate::where('id', 4)->first();
                $subject = $template->subject;
                $message = $template->description;
                $message = str_replace('{{user_name}}', $user->name, $message);
                Mail::to($user->email)->send(new UserRegistration($message, $subject, $user));

                $notification = trans('user_validation.Register Successfully. Please Verify your email');
                return response()->json(['notification' => $notification]);
            } else {
                $notification = trans('user_validation.Already verfied your account');
                return response()->json(['notification' => $notification], 402);
            }
        } else {
            $notification = trans('user_validation.Email does not exist');
            return response()->json(['notification' => $notification], 402);
        }
    }


    public function userVerification($token){
        $user = User::where('verify_token',$token)->first();
        if($user){
            $user->verify_token = null;
            $user->status = 1;
            $user->email_verified = 1;
            $user->save();
            $notification = trans('user_validation.Verification Successfully');
            return response()->json(['notification' => $notification],200);
        }else{
            $notification = trans('user_validation.Invalid token');
            return response()->json(['notification' => $notification],400);
        }
    }


    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }


    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    private function placeholderEmailForPhone(string $phoneDigits): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phoneDigits) ?: Str::random(8);
        $domain = QuickSellerRegistrationService::PENDING_EMAIL_DOMAIN;
        $candidate = $digits.'@'.$domain;

        while (User::where('email', $candidate)->exists()) {
            $candidate = $digits.'_'.Str::lower(Str::random(4)).'@'.$domain;
        }

        return $candidate;
    }
}
