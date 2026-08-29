<?php

namespace App\Http\Controllers\WEB\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Country;
use App\Models\CountryState;
use App\Models\City;
use App\Models\Vendor;
use App\Models\VendorSocialLink;
use App\Models\SellerWithdraw;
use App\Models\SellerMailLog;
use App\Models\OrderProduct;
use App\Models\Setting;
use App\Models\BannerImage;
use App\Models\LegalDocument;
use App\Services\LegalConsentService;
use App\Services\SellerIyzicoOnboardingService;
use Auth;
use Image;
use File;
use Str;
use Hash;
class SellerProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web');
    }

    public function index(){
        $user = Auth::guard('web')->user();

        $seller = Vendor::with('user','socialLinks','products')->where('user_id', $user->id)->first();
        $countries = Country::orderBy('name','asc')->where('status',1)->get();
        $states = CountryState::orderBy('name','asc')->where(['status' => 1, 'country_id' => $user->country_id])->get();
        $cities = City::orderBy('name','asc')->where(['status' => 1, 'country_state_id' => $user->state_id])->get();
        $totalWithdraw = SellerWithdraw::where('seller_id',$seller->id)->where('status',1)->sum('total_amount');
        $totalPendingWithdraw = SellerWithdraw::where('seller_id',$seller->id)->where('status',0)->sum('withdraw_amount');

        $totalAmount = 0;
        $totalSoldProduct = 0;
        $orderProducts = OrderProduct::with('order')->where('seller_id', $seller->id)->get();
        foreach($orderProducts as $orderProduct){
            if($orderProduct->order->payment_status == 1 && $orderProduct->order->order_status == 3){
                $price = $orderProduct->seller_net_amount > 0 ? $orderProduct->seller_net_amount : ($orderProduct->unit_price * $orderProduct->qty);
                $totalAmount = $totalAmount + $price;
                $totalSoldProduct = $totalSoldProduct + $orderProduct->qty;
            }
        }

        $defaultProfile = BannerImage::whereId('15')->first();
        $setting = Setting::first();

        return view('seller.seller_profile', compact('user','countries','states','cities','seller','totalWithdraw','totalAmount','totalPendingWithdraw','totalSoldProduct','setting','defaultProfile'));
    }

    public function changePassword(){
        $user = Auth::guard('web')->user();
        $setting = Setting::first();
        return view('seller.change_password', compact('user','setting'));
    }

    public function stateByCountry($id){
        $states = CountryState::where(['status' => 1, 'country_id' => $id])->get();
        return response()->json(['states'=>$states]);
    }

    public function cityByState($id){
        $cities = City::where(['status' => 1, 'country_state_id' => $id])->get();
        return response()->json(['cities'=>$cities]);
    }

    public function updateSellerProfile(Request $request){
        $user = Auth::guard('web')->user();
        $onboarding = app(SellerIyzicoOnboardingService::class);
        $hasRealEmail = $onboarding->hasValidContactEmail($user->email);

        $rules = [
            'name'=>'required',
            'phone'=>'required',
            'address'=>'required',
        ];
        $customMessages = [
            'name.required' => trans('admin_validation.Name is required'),
            'email.required' => trans('admin_validation.Email is required'),
            'email.unique' => trans('admin_validation.Email already exist'),
            'phone.required' => trans('admin_validation.Phone is required'),
            'country.required' => trans('admin_validation.Country is required'),
            'zip_code.required' => trans('admin_validation.Zip code is required'),
            'address.required' => trans('admin_validation.Address is required'),
        ];
        if (! $hasRealEmail) {
            $rules['email'] = 'required|email|unique:users,email,'.$user->id;
        }
        $this->validate($request, $rules,$customMessages);

        $user->name = $request->name;
        if (! $hasRealEmail) {
            $user->email = strtolower(trim((string) $request->email));
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'email_verified')) {
                $user->email_verified = 1;
            }
        }
        $user->phone = $request->phone;
        $user->address = $request->address;
        $user->save();

        $seller = Vendor::where('user_id', $user->id)->first();
        if ($seller) {
            $seller->email = $user->email;
            $seller->save();
        }

        if($request->file('image')){
            $old_image=$user->image;
            $user_image=$request->image;
            $extention=$user_image->getClientOriginalExtension();
            $image_name= Str::slug($request->name).date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $image_name='uploads/custom-images/'.$image_name;

            Image::make($user_image)
                ->save(public_path().'/'.$image_name);

            $user->image=$image_name;
            $user->save();
            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }

        $notification= trans('admin_validation.Update Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }

    public function updatePassword(Request $request){
        $user = Auth::guard('web')->user();
        $rules = [
            'password'=>'required|min:8|regex:/^(?=.*[a-zA-Z])(?=.*\d).+$/|confirmed',
        ];

        $customMessages = [
            'password.required' => trans('admin_validation.Password is required'),
            'password.min' => 'Şifre en az 8 karakter olmalıdır.',
            'password.regex' => 'Şifre en az bir harf ve bir rakam içermelidir.',
            'password.confirmed' => trans('admin_validation.Confirm password does not match'),
        ];
        $this->validate($request, $rules,$customMessages);

        $user->password = Hash::make($request->password);
        $user->must_change_password = 0;
        if ((int) $user->email_verified !== 1) {
            $user->email_verified = 1;
        }
        $user->save();
        $request->session()->forget('seller_first_login_verified');

        $seller = Vendor::query()->where('user_id', $user->id)->first();
        if ($seller && $seller->needsTermsAcceptance()) {
            $notification = 'Şifreniz güncellendi. Devam etmek için satıcı sözleşmesini onaylayın.';
            $notification = ['messege' => $notification, 'alert-type' => 'success'];

            return redirect()->route('seller.accept-terms')->with($notification);
        }

        $notification= trans('admin_validation.Password Change Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('seller.dashboard')->with($notification);
    }

    public function acceptTerms()
    {
        $user = Auth::guard('web')->user();
        $seller = Vendor::query()->where('user_id', $user->id)->first();

        if (! $seller) {
            abort(403);
        }

        if (! $seller->needsTermsAcceptance()) {
            return redirect()->route('seller.dashboard');
        }

        $setting = Setting::first();
        $sellerTermsDocument = LegalDocument::query()
            ->where('slug', 'seller-terms')
            ->where('is_published', true)
            ->first();

        return view('seller.accept_terms', compact('user', 'setting', 'seller', 'sellerTermsDocument'));
    }

    public function storeTermsAcceptance(Request $request)
    {
        $user = Auth::guard('web')->user();
        $seller = Vendor::query()->where('user_id', $user->id)->first();

        if (! $seller) {
            abort(403);
        }

        if (! $seller->needsTermsAcceptance()) {
            return redirect()->route('seller.dashboard');
        }

        $request->validate([
            'agree_terms_condition' => 'required|accepted',
        ], [
            'agree_terms_condition.required' => 'Satıcı sözleşmesini onaylamanız gerekir.',
            'agree_terms_condition.accepted' => 'Satıcı sözleşmesini onaylamanız gerekir.',
        ]);

        $seller->seller_terms_accepted_at = now();
        $seller->seller_terms_accepted_ip = $request->ip();
        $seller->save();

        app(LegalConsentService::class)->recordMany(
            $request,
            [['slug' => 'seller-terms', 'status' => true]],
            [
                'user_id' => $user->id,
                'context' => 'seller_panel_terms',
            ]
        );

        return redirect()
            ->route('seller.dashboard')
            ->with([
                'messege' => 'Satıcı sözleşmesi onaylandı. Hoş geldiniz.',
                'alert-type' => 'success',
            ]);
    }

    public function myShop(){
        $user = Auth::guard('web')->user();
        $seller = Vendor::with('socialLinks')->where('user_id',$user->id)->first();

        return view('seller.shop_profile', compact('user','seller'));
    }

    public function updateSellerSop(Request $request){

        $user = Auth::guard('web')->user();
        $seller = Vendor::where('user_id',$user->id)->first();
        $onboarding = app(SellerIyzicoOnboardingService::class);
        $hasRealEmail = $onboarding->hasValidContactEmail($user->email);

        $rules = [
            'shop_name'=>'required|unique:vendors,email,'.$seller->id,
            'phone'=>'required',
            'opens_at'=>'required',
            'closed_at'=>'required',
            'address'=>'required',
            'greeting_msg'=>'required',
            'iban'=>'required|regex:/^TR\d{24}$/i',
        ];
        if (! $hasRealEmail) {
            $rules['email'] = 'required|email|unique:vendors,email,'.$seller->id;
        }
        $customMessages = [
            'shop_name.required' => trans('admin_validation.Shop name is required'),
            'shop_name.unique' => trans('admin_validation.Shop anme is required'),
            'email.required' => trans('admin_validation.Email is required'),
            'email.unique' => trans('admin_validation.Email already exist'),
            'phone.required' => trans('admin_validation.Phone is required'),
            'greeting_msg.required' => trans('admin_validation.Greeting Messsage is required'),
            'opens_at.required' => trans('admin_validation.Opens at is required'),
            'closed_at.required' => trans('admin_validation.Close at is required'),
            'address.required' => trans('admin_validation.Address is required'),
            'iban.required' => 'IBAN numarası zorunludur.',
            'iban.regex' => 'Geçerli bir Türkiye IBAN\'ı giriniz (TR ile başlayan 26 karakter).',
        ];
        $this->validate($request, $rules,$customMessages);

        if ($hasRealEmail) {
            $email = strtolower(trim((string) $user->email));
        } else {
            $email = strtolower(trim((string) $request->email));
            if (User::query()->where('email', $email)->where('id', '!=', $user->id)->exists()) {
                return redirect()->back()->withErrors(['email' => 'Bu e-posta adresi zaten kayıtlı.'])->withInput();
            }
        }

        $seller->email = $email;
        $seller->phone = $request->phone;
        $seller->tc_identity = $request->tc_identity;
        $seller->tax_number = $request->tax_number;
        $seller->tax_office = $request->tax_office;
        $seller->iban = $request->iban;
        $seller->open_at = $request->opens_at;
        $seller->closed_at = $request->closed_at;
        $seller->address = $request->address;
        $seller->greeting_msg = $request->greeting_msg;
        $seller->seo_title = $request->seo_title ? $request->seo_title : $request->shop_name;
        $seller->seo_description = $request->seo_description ? $request->seo_description : $request->shop_name;
        $seller->save();

        if (! $hasRealEmail) {
            $user->email = $email;
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'email_verified')) {
                $user->email_verified = 1;
            }
            $user->save();
        }

        if($request->logo){
            $exist_banner = $seller->logo;
            $extention = $request->logo->getClientOriginalExtension();
            $banner_name = 'seller-banner'.date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $banner_name = 'uploads/custom-images/'.$banner_name;
            Image::make($request->logo)
                ->save(public_path().'/'.$banner_name);
            $seller->logo = $banner_name;
            $seller->save();
            if($exist_banner){
                if(File::exists(public_path().'/'.$exist_banner))unlink(public_path().'/'.$exist_banner);
            }
        }


        if($request->banner_image){
            $exist_banner = $seller->banner_image;
            $extention = $request->banner_image->getClientOriginalExtension();
            $banner_name = 'seller-banner'.date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $banner_name = 'uploads/custom-images/'.$banner_name;
            Image::make($request->banner_image)
                ->save(public_path().'/'.$banner_name);
            $seller->banner_image = $banner_name;
            $seller->save();
            if($exist_banner){
                if(File::exists(public_path().'/'.$exist_banner))unlink(public_path().'/'.$exist_banner);
            }
        }

        if(count($request->links) > 0){
            $socialLinks = $seller->socialLinks;
            foreach($socialLinks as $link){
                $link->delete();
            }
            foreach($request->links as $index=> $link){
                if($request->links[$index] != null && $request->icons[$index] != null){
                    $socialLink = new VendorSocialLink();
                    $socialLink->vendor_id = $seller->id;
                    $socialLink->icon=$request->icons[$index];
                    $socialLink->link=$request->links[$index];
                    $socialLink->save();
                }
            }
        }

        $notification= trans('admin_validation.Update Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }

    public function removeSellerSocialLink($id){
        $socialLink = VendorSocialLink::find($id);
        $socialLink->delete();
        return response()->json(['success' => trans('admin_validation.Delete Successfully')]);
    }

    public function emailHistory(){
        $user = Auth::guard('web')->user();
        $seller = $user->seller;
        $emails = SellerMailLog::where('seller_id',$seller->id)->orderBy('id','desc')->get();

        return response()->json(['emails' => $emails, 'user' => $user]);

    }

    public function updateLocation(Request $request){
        $rules = [
            'latitude'=>'required',
            'longitude'=>'required',
        ];
        $customMessages = [
            'latitude.required' => trans('admin_validation.Latitude is required'),
            'longitude.required' => trans('admin_validation.Longitude is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $this->validate($request, $rules);
        $user=Auth::guard('web')->user();
        $user->latitude=$request->latitude;
        $user->longitude=$request->longitude;
        $user->save();

        $notification= trans('admin_validation.Update Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);


    }
}
