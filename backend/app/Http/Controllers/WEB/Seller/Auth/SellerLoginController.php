<?php

namespace App\Http\Controllers\WEB\Seller\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vendor;
use App\Services\CallCenter\QuickSellerRegistrationService;
use App\Providers\RouteServiceProvider;
use App\Support\PhoneNormalizer;
use App\Support\SellerLoginUrl;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;
use Hash;

class SellerLoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest:web')->except('adminLogout');
    }

    public function sellerLoginPage(){
        $setting = Setting::first();
        return view('seller.login',compact('setting'));
    }


    public function storeLogin(Request $request){

        $login = trim((string) ($request->input('login') ?: $request->input('email', '')));

        $rules = [
            'password'=>'required|string',
        ];

        $customMessages = [
            'password.required' => trans('admin_validation.Password is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        if ($login === '') {
            return response()->json(['error' => 'E-posta veya telefon zorunludur.']);
        }

        $user = $this->findSellerUserByLogin($login);

        if($user){
            if($user->status==1){
                $vendor = Vendor::where('user_id', $user->id)->first();
                if(!$vendor){
                    $notification= trans('admin_validation.Invalid Email');
                    return response()->json(['error'=>$notification]);
                }
                if($vendor->status == 1){
                    if($user->must_change_password){
                        $otp = QuickSellerRegistrationService::findActiveFirstLoginOtp($user);

                        // Hızlı kayıt kodu giriş yapılana kadar geçerlidir (süre kontrolü yok)
                        if (! $otp) {
                            $notification= 'Tek kullanımlık giriş kodu bulunamadı.';
                            return response()->json(['error'=>$notification]);
                        }

                        if (! $otp->hasAttemptsRemaining()) {
                            $notification= 'Tek kullanımlık giriş kodu için maksimum deneme sayısına ulaşıldı.';
                            return response()->json(['error'=>$notification]);
                        }

                        if ($otp->otp_code !== trim((string) $request->password)) {
                            $otp->increment('attempts');
                            $notification= 'Tek kullanımlık giriş kodu hatalı.';
                            return response()->json(['error'=>$notification]);
                        }

                        $otp->markVerified();
                        if ((int) $user->email_verified !== 1) {
                            $user->email_verified = 1;
                            $user->save();
                        }
                        Auth::guard('web')->login($user, $request->remember);
                        session(['seller_first_login_verified' => true]);
                        $notification= 'Giriş başarılı. Devam etmek için yeni şifre oluşturun.';
                        return response()->json([
                            'success'=>$notification,
                            'redirect' => route('seller.change-password'),
                            'force_password_change' => true,
                        ]);
                    }

                    $credential = [
                        'email' => $user->email,
                        'password' => $request->password,
                    ];

                    if(Hash::check($request->password,$user->password)){
                        if(Auth::guard('web')->attempt($credential,$request->remember)){
                            if ($vendor->needsTermsAcceptance()) {
                                $notification = trans('admin_validation.Login Successfully');
                                return response()->json([
                                    'success' => $notification,
                                    'redirect' => route('seller.accept-terms'),
                                ]);
                            }

                            $notification= trans('admin_validation.Login Successfully');
                            return response()->json(['success'=>$notification]);
                        }
                    }else{
                        $notification= trans('admin_validation.Invalid Password');
                        return response()->json(['error'=>$notification]);
                    }
                }else{
                    $notification= trans('admin_validation.Inactive account');
                    return response()->json(['error'=>$notification]);
                }

            }else{
                $notification= trans('admin_validation.Inactive account');
                return response()->json(['error'=>$notification]);
            }
        }else{
            $notification= 'Geçersiz e-posta veya telefon.';
            return response()->json(['error'=>$notification]);
        }
    }

    protected function findSellerUserByLogin(string $login): ?User
    {
        $login = trim($login);

        if ($login === '') {
            return null;
        }

        if (str_contains($login, '@')) {
            return User::query()->where('email', $login)->first();
        }

        $phone = PhoneNormalizer::toE164($login);
        $phoneDigits = PhoneNormalizer::digitsOnly($phone);

        return User::query()
            ->where(function ($query) use ($phone, $phoneDigits) {
                $query->where('phone', $phone)
                    ->orWhere('phone', $phoneDigits)
                    ->orWhere('phone', '+90'.$phoneDigits)
                    ->orWhere('phone', '0'.substr($phoneDigits, -10));
            })
            ->first();
    }

    public function adminLogout(){
        Auth::guard('web')->logout();
        $notification= trans('admin_validation.Logout Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->away(SellerLoginUrl::public())->with($notification);
    }


    protected function respondWithToken($token, $admin)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'admin' => $admin
        ]);
    }
}
