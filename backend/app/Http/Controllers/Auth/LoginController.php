<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use App\Models\BannerImage;
use App\Models\BreadcrumbImage;
use App\Models\GoogleRecaptcha;
use App\Models\User;
use App\Models\Vendor;
use App\Rules\Captcha;
use Auth;
use Hash;
use App\Mail\UserForgetPassword;
use App\Helpers\MailHelper;
use App\Models\EmailTemplate;
use App\Models\OtpVerification;
use App\Services\SellerSsoTicketService;
use App\Models\SocialLoginInformation;
use App\Models\TwilioSms;
use App\Models\SmsTemplate;
use App\Models\BiztechSms;
use App\Services\CallCenter\QuickSellerRegistrationService;
use App\Services\SmsServiceInterface;
use App\Support\OtpMessageBuilder;
use App\Support\PhoneNormalizer;
use Mail;
use Str;
use Validator,Redirect,Response,File;
use Socialite;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Twilio\Rest\Client;
use Exception;

class LoginController extends Controller
{

    use AuthenticatesUsers;
    protected $redirectTo = '/user/dashboard';

    public function __construct()
    {
        $this->middleware('guest:api')->except('userLogout');
    }

    public function loginPage(){
        $banner = BreadcrumbImage::where(['id' => 5])->first();
        $background = BannerImage::whereId('13')->first();
        $recaptchaSetting = GoogleRecaptcha::first();
        $socialLogin = SocialLoginInformation::first();
        return view('login', compact('banner','background','recaptchaSetting','socialLogin'));
    }

    public function storeLogin(Request $request){
        $rules = [
            'email'=>'required',
            'password'=>'required',
            'g-recaptcha-response'=>new Captcha()
        ];
        $customMessages = [
            'email.required' => trans('user_validation.Email is required'),
            'password.required' => trans('user_validation.Password is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $login_by = 'email';
        $input = trim($request->email);
        $normalizedPhone = null;

        if(filter_var($input, FILTER_VALIDATE_EMAIL)){
            $login_by = 'email';
            $user = User::where('email', $input)->first();
        }else{
            // Telefon numarası olarak dene — normalize et
            $login_by = 'phone';
            $digits = preg_replace('/[^0-9]/', '', $input);
            if (str_starts_with($digits, '90') && strlen($digits) === 12) {
                $normalizedPhone = '+' . $digits;
            } elseif (strlen($digits) === 10 && str_starts_with($digits, '5')) {
                $normalizedPhone = '+90' . $digits;
            } else {
                $normalizedPhone = str_starts_with($input, '+') ? '+' . $digits : $digits;
            }
            $user = $this->findUserByPhone($normalizedPhone, $digits);
        }

        if (! $user && $login_by === 'phone') {
            return response()->json([
                'message' => 'Bu telefon numarası ile kayıtlı hesap bulunamadı. Kayıt olabilirsiniz.',
                'error_code' => 'account_not_found',
            ], 404);
        }

        if($user){
            $seller = Vendor::where('user_id', $user->id)->first();
            $isPendingEmail = QuickSellerRegistrationService::isPendingEmail($user->email);

            // Placeholder e-posta / telefon ile giriş / satıcı ilk giriş: e-posta OTP zorunlu değil
            if ((int) $user->email_verified === 0) {
                $allowWithoutEmailVerify = $login_by === 'phone'
                    || $isPendingEmail
                    || ($seller && (bool) $user->must_change_password);

                if (! $allowWithoutEmailVerify) {
                    return response()->json([
                        'notification' => 'Hesabınızı doğrulamanız gerekiyor. E-postanıza gelen kodu girin veya yeniden gönderin.',
                        'error_code' => 'email_verification_required',
                    ], 402);
                }
            }

            if($user->status==1){
                if ($seller && $user->must_change_password) {
                    $otp = QuickSellerRegistrationService::findActiveFirstLoginOtp($user);

                    if (! $otp) {
                        return response()->json([
                            'notification' => 'Tek kullanımlık giriş kodu bulunamadı. Lütfen çağrı merkezi ile yeniden hızlı kayıt açın.',
                        ], 402);
                    }

                    if (! $otp->hasAttemptsRemaining()) {
                        return response()->json([
                            'notification' => 'Tek kullanımlık giriş kodu için maksimum deneme sayısına ulaşıldı.',
                        ], 402);
                    }

                    if ($otp->otp_code === trim((string) $request->password)) {
                        $otp->markVerified();
                        $this->markPhoneVerifiedIfNeeded($user);
                        $token = Auth::guard('api')->login($user);

                        return response()->json([
                            'notification' => 'Giriş başarılı. Yeni şifrenizi oluşturun.',
                            'force_password_change' => true,
                            'redirect_url' => app(SellerSsoTicketService::class)->redirectUrl((int) $user->id, 'change-password'),
                        ], 200);
                    }

                    $otp->increment('attempts');
                    return response()->json([
                        'notification' => 'Tek kullanımlık giriş kodu hatalı.',
                    ], 402);
                }

                if(Hash::check($request->password,$user->password)){

                    if($login_by == 'email'){
                        $credential=[
                            'email'=> $input,
                            'password'=> $request->password
                        ];
                        // Token süresi jwt.php ttl (JWT_TTL) ile yönetilir.
                        if (! $token = Auth::guard('api')->attempt($credential)) {
                            return response()->json(['error' => 'Unauthorized'], 401);
                        }
                    }else{
                        // Telefon ile doğrulandıysa attempt phone alanı uyumsuzluğu yaşamamak için login kullan
                        $token = Auth::guard('api')->login($user);
                    }

                    if ($login_by === 'phone' || $isPendingEmail) {
                        $this->markPhoneVerifiedIfNeeded($user);
                    }

                    if($login_by == 'email'){
                        $user = User::where('email', $input)->select('id','name','email','phone','image','status')->first();
                    }else{
                        $user = User::where('id', $user->id)->select('id','name','email','phone','image','status')->first();
                    }


                    $isVendor = Vendor::where('user_id',$user->id)->first();
                    if($isVendor) {
                        return $this->respondWithToken($token,1,$user);
                    }else {
                        return $this->respondWithToken($token,0,$user);
                    }


                } else {
                    $message = $login_by === 'phone'
                        ? 'Telefon numarası veya şifre hatalı.'
                        : 'E-posta veya şifre hatalı.';

                    return response()->json(['message' => $message], 422);
                }

            }else{
                return response()->json([
                    'message' => 'Hesabınız devre dışı bırakılmış. Destek ile iletişime geçin.',
                ], 403);
            }
        }else{
            return response()->json([
                'message' => 'Bu e-posta adresi ile kayıtlı hesap bulunamadı.',
                'error_code' => 'account_not_found',
            ], 404);
        }
    }

    protected function findUserByPhone(string $normalizedPhone, string $digits): ?User
    {
        $last10 = strlen($digits) >= 10 ? substr($digits, -10) : $digits;
        $variants = array_values(array_unique(array_filter([
            $normalizedPhone,
            $digits,
            $last10,
            '0'.$last10,
            '+90'.$last10,
            '90'.$last10,
        ])));

        return User::query()->whereIn('phone', $variants)->first();
    }

    protected function markPhoneVerifiedIfNeeded(User $user): void
    {
        if ((int) $user->email_verified === 1) {
            return;
        }

        $user->email_verified = 1;
        $user->save();
    }


    protected function respondWithToken($token, $vendor,$user)
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'last_login_at')) {
                $user->update(['last_login_at' => now()]);
            }
        } catch (\Throwable $e) {
            // Giriş token'ı last_login kolonuna bağlı olmasın
        }

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'is_vendor' => $vendor,
            'user' => $user
        ]);
    }


    public function forgetPage(){
        $banner = BreadcrumbImage::where(['id' => 5])->first();
        $recaptchaSetting = GoogleRecaptcha::first();
        return view('forget_password', compact('banner','recaptchaSetting'));
    }

    public function sendForgetPassword(Request $request){
        $rules = [
            'email' => 'nullable|email|required_without:phone',
            'phone' => 'nullable|string|max:20|required_without:email',
        ];
        $customMessages = [
            'email.required_without' => trans('user_validation.Email is required'),
            'phone.required_without' => 'Phone is required',
        ];
        $this->validate($request, $rules, $customMessages);

        if ($request->filled('phone')) {
            return $this->sendPasswordResetOtp($request);
        }

        return $this->sendLegacyEmailPasswordReset($request);
    }


    public function resetPasswordPage($token){
        $user = User::where('forget_password_token', $token)->first();
        $banner = BreadcrumbImage::where(['id' => 5])->first();
        $recaptchaSetting = GoogleRecaptcha::first();

        return response()->json(['user' => $user, 'banner' => $banner, 'recaptchaSetting' => $recaptchaSetting],200);

        return view('reset_password', compact('banner','recaptchaSetting','user','token'));
    }

    public function storeResetPasswordPage(Request $request, $token){
        if ($request->filled('phone')) {
            return $this->storeOtpResetPassword($request, $token);
        }

        $rules = [
            'email'=>'required',
            'password'=>'required|min:8|regex:/^(?=.*[a-zA-Z])(?=.*\d).+$/|confirmed',
            'g-recaptcha-response'=>new Captcha()
        ];
        $customMessages = [
            'email.required' => trans('user_validation.Email is required'),
            'password.required' => trans('user_validation.Password is required'),
            'password.min' => 'Şifre en az 8 karakter olmalıdır.',
            'password.regex' => 'Şifre en az bir harf ve bir rakam içermelidir.',
            'password.confirmed' => trans('user_validation.Confirm password does not match'),
        ];
        $this->validate($request, $rules,$customMessages);

        $user = User::where(['email' => $request->email, 'forget_password_token' => $token])->first();
        if($user){
            $user->password=Hash::make($request->password);
            $user->forget_password_token=null;
            $user->save();

            $notification = trans('user_validation.Password Reset successfully');
            return response()->json(['notification' => $notification],200);
        }else{
            $notification = trans('user_validation.Email or token does not exist');
            return response()->json(['notification' => $notification],402);
        }
    }

    protected function sendPasswordResetOtp(Request $request)
    {
        $phone = PhoneNormalizer::toE164((string) $request->phone);
        $phoneDigits = PhoneNormalizer::digitsOnly($phone);
        $notification = 'Doğrulama kodu gönderildi.';
        $user = User::query()
            ->where(function ($query) use ($phone, $phoneDigits) {
                $query->where('phone', $phone)
                    ->orWhere('phone', $phoneDigits)
                    ->orWhere('phone', '+90'.$phoneDigits)
                    ->orWhere('phone', '0'.substr($phoneDigits, -10));
            })
            ->first();

        if (!$user) {
            return response()->json(['notification' => $notification], 200);
        }

        $latestOtp = OtpVerification::where('phone', $phone)
            ->where('purpose', 'password_reset')
            ->latest('id')
            ->first();

        $cooldownSeconds = (int) config('sms.otp.cooldown_seconds', 60);
        if ($latestOtp && $latestOtp->created_at->diffInSeconds(now()) < $cooldownSeconds) {
            return response()->json([
                'notification' => 'Please wait before requesting a new code.',
                'retry_after' => $cooldownSeconds - $latestOtp->created_at->diffInSeconds(now()),
            ], 429);
        }

        OtpVerification::where('phone', $phone)
            ->where('purpose', 'password_reset')
            ->whereNull('verified_at')
            ->delete();

        $otp = OtpVerification::create([
            'phone' => $phone,
            'otp_code' => $this->generateOtpCode(),
            'purpose' => 'password_reset',
            'attempts' => 0,
            'max_attempts' => (int) config('sms.otp.max_attempts', 3),
            'expires_at' => Carbon::now()->addMinutes((int) config('sms.otp.expire_minutes', 5)),
            'ip_address' => $request->ip(),
        ]);

        $message = OtpMessageBuilder::build($otp->otp_code);

        $smsSent = app(SmsServiceInterface::class)->send($phone, $message);

        if (!$smsSent) {
            return response()->json([
                'notification' => 'Doğrulama kodu gönderilemedi. Lütfen daha sonra tekrar deneyin.',
            ], 500);
        }

        $response = [
            'notification' => $notification,
            'expires_in' => (int) config('sms.otp.expire_minutes', 5) * 60,
        ];

        // In local/testing, include OTP in response so developers can test without real SMS
        if (app()->environment('local', 'testing')) {
            $response['otp_code'] = $otp->otp_code;
            $response['notification'] = "OTP: {$otp->otp_code} (geliştirici modu — SMS gönderilmedi)";
        }

        return response()->json($response, 200);
    }

    protected function sendLegacyEmailPasswordReset(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        if($user){
            $user->forget_password_token = random_int(100000, 999999);
            $user->save();

            MailHelper::setMailConfig();
            $template = EmailTemplate::where('id',1)->first();
            $subject = $template->subject;
            $message = $template->description;
            $message = str_replace('{{name}}',$user->name,$message);
            Mail::to($user->email)->send(new UserForgetPassword($message,$subject,$user));

            $template=SmsTemplate::where('id',2)->first();
            $message=$template->description;
            $message = str_replace('{{name}}',$user->name,$message);
            $message = str_replace('{{otp_code}}', $user->forget_password_token ,$message);

            $twilio = TwilioSms::first();
            if($twilio->enable_reset_pass_sms == 1){
                if($user->phone){
                    try{
                        $account_sid = $twilio->account_sid;
                        $auth_token = $twilio->auth_token;
                        $twilio_number = $twilio->twilio_phone_number;
                        $recipients = $user->phone;
                        $client = new Client($account_sid, $auth_token);
                        $client->messages->create($recipients,
                                ['from' => $twilio_number, 'body' => $message] );
                    }catch(Exception $ex){

                    }
                }
            }

            $biztech = BiztechSms::first();
            if($biztech->enable_reset_pass_sms == 1){
                if($user->phone){
                    try{
                        $apikey = $biztech->api_key;
                        $clientid = $biztech->client_id;
                        $senderid = $biztech->sender_id;
                        $senderid = urlencode($senderid);
                        $message = $message;
                        $msg_type = true;
                        $message  = urlencode($message);
                        $mobilenumbers = $user->phone;
                        $url = "https://api.smsq.global/api/v2/SendSMS?ApiKey=$apikey&ClientId=$clientid&SenderId=$senderid&Message=$message&MobileNumbers=$mobilenumbers&Is_Unicode=$msg_type";
                        $ch = curl_init();
                        curl_setopt ($ch, CURLOPT_URL, $url);
                        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
                        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
                        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 5);
                        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_NOBODY, false);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        $response = curl_exec($ch);
                        $response = json_decode($response);
                    }catch(Exception $ex){}
                }
            }

            $notification = trans('user_validation.Reset password link send to your email.');
            return response()->json(['notification' => $notification],200);

        }else{
            $notification = trans('user_validation.Email does not exist');
            return response()->json(['notification' => $notification],402);
        }
    }

    protected function storeOtpResetPassword(Request $request, $token)
    {
        $rules = [
            'phone' => 'required|string|max:20',
            'password' => 'required|min:8|regex:/^(?=.*[a-zA-Z])(?=.*\d).+$/|confirmed',
            'otp_verified_token' => 'nullable|string',
        ];
        $customMessages = [
            'phone.required' => 'Telefon numarası zorunludur.',
            'password.required' => trans('user_validation.Password is required'),
            'password.min' => 'Şifre en az 8 karakter olmalıdır.',
            'password.regex' => 'Şifre en az bir harf ve bir rakam içermelidir.',
            'password.confirmed' => trans('user_validation.Confirm password does not match'),
        ];
        $this->validate($request, $rules, $customMessages);

        $verifiedToken = $request->otp_verified_token ?: $token;
        $payload = Cache::get('otp_verified_token:' . $verifiedToken);

        $phone = PhoneNormalizer::toE164((string) $request->phone);

        if (!$payload || ($payload['purpose'] ?? null) !== 'password_reset' || ($payload['phone'] ?? null) !== $phone) {
            return response()->json([
                'notification' => 'Şifre sıfırlama oturumu geçersiz veya süresi dolmuş.',
            ], 422);
        }

        $user = User::query()
            ->where(function ($query) use ($phone) {
                $phoneDigits = PhoneNormalizer::digitsOnly($phone);
                $query->where('phone', $phone)
                    ->orWhere('phone', $phoneDigits)
                    ->orWhere('phone', '+90'.$phoneDigits)
                    ->orWhere('phone', '0'.substr($phoneDigits, -10));
            })
            ->first();
        if (!$user) {
            return response()->json([
                'notification' => 'Şifre sıfırlama oturumu geçersiz veya süresi dolmuş.',
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->forget_password_token = null;
        $user->save();

        Cache::forget('otp_verified_token:' . $verifiedToken);

        return response()->json([
            'notification' => trans('user_validation.Password Reset successfully'),
        ], 200);
    }

    protected function generateOtpCode(): string
    {
        $length = (int) config('sms.otp.length', 6);
        $min = (int) str_pad('1', $length, '0');
        $max = (int) str_repeat('9', $length);

        return (string) random_int($min, $max);
    }

    public function userLogout(){
        Auth::guard('api')->logout();
        $notification= trans('user_validation.Logout Successfully');
        return response()->json(['notification' => $notification],200);
    }

    public function redirectToGoogle(){
        $googleInfo = SocialLoginInformation::first();
        if (!$googleInfo || (int) $googleInfo->is_gmail !== 1) {
            return response()->json(['message' => 'Google girişi kapalı.'], 403);
        }
        if (!$googleInfo->gmail_client_id || !$googleInfo->gmail_secret_id || !$googleInfo->gmail_redirect_url) {
            return response()->json(['message' => 'Google giriş ayarları eksik.'], 422);
        }

        \Config::set('services.google.client_id', $googleInfo->gmail_client_id);
        \Config::set('services.google.client_secret', $googleInfo->gmail_secret_id);
        \Config::set('services.google.redirect', $googleInfo->gmail_redirect_url);

        return response()->json([
            'url' => Socialite::driver('google')->stateless()->redirect()->getTargetUrl(),
        ]);
    }

    public function googleCallBack(Request $request){
        $googleInfo = SocialLoginInformation::first();
        if (!$googleInfo || (int) $googleInfo->is_gmail !== 1) {
            return response()->json(['message' => 'Google girişi kapalı.'], 403);
        }

        \Config::set('services.google.client_id', $googleInfo->gmail_client_id);
        \Config::set('services.google.client_secret', $googleInfo->gmail_secret_id);
        \Config::set('services.google.redirect', $googleInfo->gmail_redirect_url);

        try {
            $socialiteUser = Socialite::driver('google')->stateless()->user();
        } catch (Exception $e) {
            return response()->json(['error' => 'Invalid credentials provided.'], 422);
        }

        $user = User::where('email', $socialiteUser->getEmail())->first();
        if (!$user) {
            $user = User::create([
                'name'     => $socialiteUser->getName(),
                'email'    => $socialiteUser->getEmail(),
                'provider' => 'google',
                'provider_id' => $socialiteUser->getId(),
                'provider_avatar' => $socialiteUser->getAvatar(),
                'status' => 1,
                'email_verified' => 1,
            ]);
        }

        $token = Auth::guard('api')->login($user);

        $isVendor = Vendor::where('user_id',$user->id)->first();
        if($isVendor) {
            return $this->respondWithToken($token,1,$user);
        }else {
            return $this->respondWithToken($token,0,$user);
        }
    }
    
    
    public function callback_mobileapp(Request $request){
        $email = trim((string) $request->input('email', ''));
        $provider = trim((string) $request->input('provider', ''));
        $providerId = trim((string) $request->input('provider_id', ''));
        $name = trim((string) $request->input('name', ''));

        $user = null;
        if ($email !== '') {
            $user = User::where('email', $email)->first();
        }
        if (!$user && $provider !== '' && $providerId !== '') {
            $user = User::where('provider', $provider)
                ->where('provider_id', $providerId)
                ->first();
        }

        if (!$user) {
            if ($email === '') {
                return response()->json([
                    'message' => 'E-posta alınamadı. Lütfen tekrar deneyin veya e-posta ile kayıt olun.',
                ], 422);
            }

            $user = User::create([
                'name'     => $name !== '' ? $name : 'Kullanıcı',
                'email'    => $email,
                'provider' => $provider !== '' ? $provider : null,
                'provider_id' => $providerId !== '' ? $providerId : null,
                'provider_avatar' => $request->avatar,
                'status' => 1,
                'email_verified' => 1,
            ]);
        } else {
            $dirty = false;
            if ($provider !== '' && empty($user->provider)) {
                $user->provider = $provider;
                $dirty = true;
            }
            if ($providerId !== '' && empty($user->provider_id)) {
                $user->provider_id = $providerId;
                $dirty = true;
            }
            if ($dirty) {
                $user->save();
            }
        }

        $token = Auth::guard('api')->login($user);

        $isVendor = Vendor::where('user_id',$user->id)->first();
        
        $user = User::where('id',$user->id)->select('id','name','email','phone','image','status')->first();
        
        if($isVendor) {
            return $this->respondWithToken($token,1,$user);
        }else {
            return $this->respondWithToken($token,0,$user);
        }
    }

    public function redirectToFacebook(){

        $facebookInfo = SocialLoginInformation::first();
        if($facebookInfo){
            \Config::set('services.facebook.client_id', $facebookInfo->facebook_client_id);
            \Config::set('services.facebook.client_secret', $facebookInfo->facebook_secret_id);
            \Config::set('services.facebook.redirect', $facebookInfo->facebook_redirect_url);
        }

        return response()->json([
            'url' => Socialite::driver('facebook')->stateless()->redirect()->getTargetUrl(),
        ]);

        SocialLoginInformation::setFacebookLoginInfo();
        return Socialite::driver('facebook')->redirect();
    }

    public function facebookCallBack(){

        $facebookInfo = SocialLoginInformation::first();
        if($facebookInfo){
            \Config::set('services.facebook.client_id', $facebookInfo->facebook_client_id);
            \Config::set('services.facebook.client_secret', $facebookInfo->facebook_secret_id);
            \Config::set('services.facebook.redirect', $facebookInfo->facebook_redirect_url);
        }


         try{    /** @var SocialiteUser $socialiteUser */
            $socialiteUser = Socialite::driver('facebook')->stateless()->user();
        } catch (Exception $e) {
            return response()->json(['error' => 'Invalid credentials provided.'], 422);
        }


        $user = User::where('email', $socialiteUser->getEmail())->first();
        if (!$user) {
            $user = User::create([
                'name'     => $socialiteUser->getName(),
                'email'    => $socialiteUser->getEmail(),
                'provider' => 'facebook',
                'provider_id' => $socialiteUser->getId(),
                'provider_avatar' => $socialiteUser->getAvatar(),
                'status' => 1,
                'email_verified' => 1,
            ]);
        }


        $token = Auth::guard('api')->login($user);


        $isVendor = Vendor::where('user_id',$user->id)->first();
        if($isVendor) {
            return $this->respondWithToken($token,1,$user);
        }else {
            return $this->respondWithToken($token,0,$user);
        }


    }



    function createUser($getInfo,$provider){
        $user = User::where('provider_id', $getInfo->id)->first();
        if (!$user) {
            $user = User::create([
                'name'     => $getInfo->name,
                'email'    => $getInfo->email,
                'provider' => $provider,
                'provider_id' => $getInfo->id,
                'provider_avatar' => $getInfo->avatar,
                'status' => 1,
                'email_verified' => 1,
            ]);
        }
        return $user;
    }
}
