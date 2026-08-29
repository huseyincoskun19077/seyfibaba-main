<?php

namespace App\Http\Controllers\WEB\CallCenter\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\BannerImage;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CallCenterLoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest:admin')->except('logout');
    }

    public function showLoginForm()
    {
        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();
            if ($admin->isCallCenterAgent()) {
                return redirect()->route('call-center.dashboard');
            }
        }

        $banner = BannerImage::find(13);
        $setting = Setting::first();

        return view('call-center.auth.login', compact('banner', 'setting'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $admin = Admin::where('email', $credentials['email'])->first();

        if (! $admin) {
            return back()
                ->withInput($request->only('email'))
                ->with(['messege' => 'Geçersiz e-posta veya şifre.', 'alert-type' => 'error']);
        }

        if ((int) $admin->admin_type !== Admin::TYPE_CALL_CENTER) {
            return back()
                ->withInput($request->only('email'))
                ->with(['messege' => 'Bu giriş yalnızca çağrı merkezi personeli içindir.', 'alert-type' => 'error']);
        }

        if ((int) $admin->status !== 1) {
            return back()
                ->withInput($request->only('email'))
                ->with(['messege' => 'Hesabınız pasif durumda.', 'alert-type' => 'error']);
        }

        if (! Hash::check($credentials['password'], $admin->password)) {
            return back()
                ->withInput($request->only('email'))
                ->with(['messege' => 'Geçersiz e-posta veya şifre.', 'alert-type' => 'error']);
        }

        Auth::guard('admin')->login($admin, $request->boolean('remember'));

        return redirect()->intended(route('call-center.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('call-center.login')
            ->with(['messege' => 'Çıkış yapıldı.', 'alert-type' => 'success']);
    }
}
