<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use App\Models\Country;
use App\Models\CountryState;
use App\Models\City;
use App\Models\ProductReview;
use App\Models\Vendor;
use App\Models\Order;
use App\Models\Setting;
use App\Helpers\MailHelper;
use Mail;
use App\Models\Product;
use App\Models\OrderProduct;
use App\Models\Wishlist;
use App\Models\ProductReport;
use App\Models\GoogleRecaptcha;
use App\Models\BannerImage;
use App\Models\User;
use App\Models\CompareProduct;
use App\Rules\Captcha;
use Illuminate\Support\Facades\Schema;
use Image;
use File;
use Str;
use Hash;
use Slug;
use App\Models\CargoShipment;

use App\Events\SellerToUser;

use App\Models\OrderAddress;
use App\Models\OrderProductVariant;
use App\Models\Address;

use App\Models\ShoppingCart;
use App\Models\ShoppingCartVariant;

class UserProfileController extends Controller
{


    public function __construct()
    {
        // Sadece giriş gerektiren metodlar için auth middleware
        $this->middleware('auth:api')->only([
            'dashboard', 'remove_account', 'order', 'pendingOrder', 
            'completeOrder', 'declinedOrder', 'orderShow', 'wishlist', 
            'myProfile', 'updateProfile', 'updateDeviceToken', 'address', 'updatePassword',
            'compareProducts', 'addCompareProducts',
            'deleteCompareProduct', 'confirmDelivery', 'confirmOrderProductDelivery'
        ]);
    }

    public function remove_account(){
        $user = Auth::guard('api')->user();
        $id = $user->id;
        $orders = Order::where('user_id', $user->id)->get();
        foreach($orders as $order){
            OrderAddress::where(['order_id' => $order->id])->delete();
            $orderProducts = OrderProduct::where('order_id',$id)->get();
            foreach($orderProducts as $orderProduct){
                OrderProductVariant::where('order_product_id',$orderProduct->id)->delete();
                $orderProduct->delete();
            }
            $order->delete();
        }

        ProductReport::where('user_id',$id)->delete();
        ProductReview::where('user_id',$id)->delete();
        Address::where('user_id',$id)->delete();
        Wishlist::where('user_id',$id)->delete();
        CompareProduct::where('user_id',$id)->delete();

        $cart_items = ShoppingCart::where(['user_id' => $user->id])->get();
        foreach($cart_items as $cart_item){
            ShoppingCartVariant::where(['shopping_cart_id' => $cart_item->id])->delete();
            $cart_item->delete();
        }

        $user = User::find($id);
        $user_image = $user->image;
        if($user_image){
            if(File::exists(public_path().'/'.$user_image))unlink(public_path().'/'.$user_image);
        }
        $user->delete();

        return response()->json(['message' => trans('Your account has been successfully removed')]);
    }

    public function dashboard(){
        $user = Auth::guard('api')->user();
        $orders = Order::where('user_id',$user->id)->get();
        $totalOrder = $orders->count();
        $completeOrder = $orders->where('order_status',3)->count();
        $pendingOrder = $orders->where('order_status',0)->count();
        $declinedOrder = $orders->where('order_status',4)->count();

        $personInfo = User::select('id','name','phone','email','image','country_id','state_id','city_id','zip_code','address')->find($user->id);
        $sellerInfo = Vendor::select('id','user_id','banner_image','phone','email','shop_name','slug','open_at','closed_at','address')->where('user_id', $personInfo->id)->first();
        $is_seller = $sellerInfo ? true : false;

        // Satıcı KYC'de girilen adres users tablosunda yoksa vendor adresini kullan
        if ($is_seller && $sellerInfo && empty($personInfo->address) && !empty($sellerInfo->address)) {
            $personInfo->address = $sellerInfo->address;
        }

        return response()->json([
            'personInfo' => $personInfo,
            'is_seller' => $is_seller,
            'sellerInfo' => $sellerInfo,
            'totalOrder' => $totalOrder,
            'completeOrder' => $completeOrder,
            'pendingOrder' => $pendingOrder,
            'declinedOrder' => $declinedOrder,
        ]);
    }


    public function order(){
        $user = Auth::guard('api')->user();
        $orders = Order::with($this->userOrderListRelations())
            ->orderBy('id', 'desc')
            ->where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('is_draft')
                    ->orWhere('is_draft', '!=', 'yes');
            })
            ->paginate(10);

        $this->hydrateOrderList($orders);

        return response()->json(['orders' => $orders]);
    }

    public function pendingOrder(){
        $user = Auth::guard('api')->user();
        $orders = Order::with($this->userOrderListRelations())
            ->orderBy('id','desc')
            ->where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('is_draft')
                    ->orWhere('is_draft', '!=', 'yes');
            })
            ->where('order_status',0)
            ->paginate(10);

        $this->hydrateOrderList($orders);

        return response()->json(['orders' => $orders]);
    }

    public function completeOrder(){
        $user = Auth::guard('api')->user();
        $orders = Order::with($this->userOrderListRelations())
            ->orderBy('id','desc')
            ->where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('is_draft')
                    ->orWhere('is_draft', '!=', 'yes');
            })
            ->where('order_status',3)
            ->paginate(10);

        $this->hydrateOrderList($orders);

        return response()->json(['orders' => $orders]);
    }

    public function declinedOrder(){
        $user = Auth::guard('api')->user();
        $orders = Order::with($this->userOrderListRelations())
            ->orderBy('id','desc')
            ->where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('is_draft')
                    ->orWhere('is_draft', '!=', 'yes');
            })
            ->where('order_status',4)
            ->paginate(10);
        $setting = Setting::first();

        $this->hydrateOrderList($orders);

        return response()->json(['orders' => $orders]);
    }

    public function orderShow($orderId){
        try {
            $user = Auth::guard('api')->user();
            if (! $user) {
                return response()->json(['message' => 'UnAuthenticated'], 401);
            }

            $order = Order::with('orderProducts.orderProductVariants', 'orderAddress', 'deliveryman')
                ->where('user_id', $user->id)
                ->where(function ($query) use ($orderId) {
                    $query->where('order_id', $orderId)
                        ->orWhere('id', $orderId);
                })
                ->first();

            if (! $order) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            $userReviews = ProductReview::where('user_id', $user->id)->get();
            $reviewedKeys = [];
            foreach ($userReviews as $review) {
                $reviewedKeys[$review->order_id . '_' . $review->product_id] = true;
            }

            $order->orderProducts->each(function ($orderProduct) use ($reviewedKeys, $order) {
                $key = $order->order_id . '_' . $orderProduct->product_id;
                $orderProduct->user_has_reviewed = isset($reviewedKeys[$key]);
            });

            $this->attachCargoToOrderProducts($order);
            $this->attachThumbImagesToOrderProducts($order->orderProducts);

            return response()->json(['order' => $order]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('orderShow failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['message' => 'Order details could not be loaded'], 500);
        }
    }


    public function wishlist(){
        $user = Auth::guard('api')->user();
        $wishlists = Wishlist::with('product')->where(['user_id' => $user->id])->get();

        return response()->json(['wishlists' => $wishlists]);
    }

    public function myProfile(){
        $user = Auth::guard('api')->user();
        $personInfo = User::select('id','name','email','phone','image','country_id','state_id','city_id','zip_code','address','tc_identity','tax_number')->find($user->id);
        $countries = Country::orderBy('name','asc')->where('status',1)->get();
        $states = CountryState::orderBy('name','asc')->where(['status' => 1, 'country_id' => $user->country_id])->get();
        $cities = City::orderBy('name','asc')->where(['status' => 1, 'country_state_id' => $user->state_id])->get();
        $defaultProfile = BannerImage::select('id','image')->whereId('15')->first();

        // Satıcı KYC'de girilen veriler users tablosunda yoksa vendor'dan merge et
        $vendor = Vendor::where('user_id', $user->id)->first();
        if ($vendor) {
            if (empty($personInfo->tc_identity) && !empty($vendor->tc_identity)) {
                $personInfo->tc_identity = $vendor->tc_identity;
            }
            if (empty($personInfo->tax_number) && !empty($vendor->tax_number)) {
                $personInfo->tax_number = $vendor->tax_number;
            }
            if (empty($personInfo->address) && !empty($vendor->address)) {
                $personInfo->address = $vendor->address;
            }
        }

        return response()->json([
            'personInfo' => $personInfo,
            'countries' => $countries,
            'states' => $states,
            'cities' => $cities,
            'defaultProfile' => $defaultProfile
        ]);
    }

    public function updateProfile(Request $request){
        $user = Auth::guard('api')->user();
        $rules = [
            'name'=>'required',
            'email'=>'required|unique:users,email,'.$user->id,
            'phone'=>'required',
            'country'=>'required',
            'address'=>'required',
        ];
        $customMessages = [
            'name.required' => trans('user_validation.Name is required'),
            'email.required' => trans('user_validation.Email is required'),
            'email.unique' => trans('user_validation.Email already exist'),
            'phone.required' => trans('user_validation.Phone is required'),
            'country.required' => trans('user_validation.Country is required'),
            'address.required' => trans('user_validation.Address is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $user->name = $request->name;
        $user->phone = $request->phone;
        $user->tc_identity = $request->tc_identity;
        $user->tax_number = $request->tax_number;
        $user->country_id = $request->country;
        $user->state_id = $request->state;
        $user->city_id = $request->city;
        $user->address = $request->address;
        $user->save();

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

        $notification = trans('user_validation.Update Successfully');
        return response()->json(['notification' => $notification]);
    }

    public function updateDeviceToken(Request $request)
    {
        $user = Auth::guard('api')->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $deviceToken = $request->input('device_token');
        if ($deviceToken === null) {
            $deviceToken = $request->query('device_token');
        }

        $deviceToken = is_string($deviceToken) ? trim($deviceToken) : '';
        $user->fcm_token = $deviceToken !== '' ? $deviceToken : null;
        $user->save();

        return response()->json([
            'message' => 'Device token updated',
            'success' => true,
        ]);
    }


    public function updatePassword(Request $request){
        $rules = [
            'current_password'=>'required',
            'password'=>'required|min:8|regex:/^(?=.*[a-zA-Z])(?=.*\d).+$/|confirmed',
        ];
        $customMessages = [
            'current_password.required' => trans('user_validation.Current password is required'),
            'password.required' => trans('user_validation.Password is required'),
            'password.min' => 'Şifre en az 8 karakter olmalıdır.',
            'password.regex' => 'Şifre en az bir harf ve bir rakam içermelidir.',
            'password.confirmed' => trans('user_validation.Confirm password does not match'),
        ];
        $this->validate($request, $rules,$customMessages);

        $user = Auth::guard('api')->user();
        if(Hash::check($request->current_password, $user->password)){
            $user->password = Hash::make($request->password);
            $user->save();
            $notification = 'Password change successfully';
            return response()->json(['notification' => $notification]);
        }else{
            $notification = trans('user_validation.Current password does not match');
            return response()->json(['notification' => $notification],403);
        }
    }

    public function stateByCountry($id){
        $states = CountryState::select('id','name')->where(['status' => 1, 'country_id' => $id])->get();
        return response()->json(['states'=>$states]);
    }

    public function cityByState($id){
        $cities = City::select('id','country_state_id','name')->where(['status' => 1, 'country_state_id' => $id])->get();
        return response()->json(['cities'=>$cities]);
    }

    public function sellerRegistration(){
        $setting = Setting::first();
        return response()->json(['setting' => $setting]);
    }

    public function sellerRequest(Request $request){
        $setting = Setting::first();
        $user = Auth::guard('api')->user();
        $seller = Vendor::where('user_id',$user->id)->first();
        if($seller){
            $notification = 'Request Already exist';
            return response()->json(['notification' => $notification],400);
        }

        $rules = [
            // 'banner_image'=>'required',
            // 'logo'=>'required',
            'shop_name'=>'required|unique:vendors',
            'email'=>'required|unique:vendors',
            'phone'=>'required',
            'address'=>'required',
            'open_at'=>'required',
            'closed_at'=>'required',
            'agree_terms_condition' => 'required'
        ];

        $customMessages = [
            'logo.required' => trans('user_validation.Logo is required'),
            'banner_image.required' => trans('user_validation.Banner image is required'),
            'shop_name.required' => trans('user_validation.Shop name is required'),
            'shop_name.unique' => trans('user_validation.Shop name already exist'),
            'email.required' => trans('user_validation.Email is required'),
            'email.unique' => trans('user_validation.Email already exist'),
            'phone.required' => trans('user_validation.Phone is required'),
            'address.required' => trans('user_validation.Address is required'),
            'open_at.required' => trans('user_validation.Open at is required'),
            'closed_at.required' => trans('user_validation.Close at is required'),
            'agree_terms_condition.required' => trans('user_validation.Agree field is required'),
        ];
        if($setting->map_status == 1){
            $rules = [
                'latitude'=>'required',
                'longitude'=>'required',
            ];
            $customMessages = [
                'latitude.required' => trans('admin_validation.Latitude is required'),
                'longitude.required' => trans('admin_validation.Longitude is required'),
            ];
        }
        $this->validate($request, $rules,$customMessages);

        $user = Auth::guard('api')->user();
        $user->latitude = $request->latitude;
        $user->longitude = $request->longitude;
        $user->save();

        $seller = new Vendor();
        $seller->shop_name = $request->shop_name;
        $seller->slug = Str::slug($request->shop_name);
        $seller->email = $request->email;
        $seller->phone = $request->phone;
        $seller->address = $request->address;
        $seller->greeting_msg = trans('user_validation.Welcome to'). ' '. $request->shop_name;
        $seller->open_at = $request->open_at;
        $seller->closed_at = $request->closed_at;
        $seller->user_id = $user->id;
        $seller->seo_title = $request->shop_name;
        $seller->seo_description = $request->shop_name;

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

        if($request->logo){
            $extention = $request->logo->getClientOriginalExtension();
            $banner_name = 'seller-logo'.date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $banner_name = 'uploads/custom-images/'.$banner_name;
            Image::make($request->logo)
                ->save(public_path().'/'.$banner_name);
            $seller->logo = $banner_name;
            $seller->save();

        }

        $seller->save();

        if (Schema::hasColumn('vendors', 'seller_terms_accepted_at')) {
            $seller->seller_terms_accepted_at = now();
            $seller->seller_terms_accepted_ip = $request->ip();
            $seller->save();
        }

        $notification = trans('user_validation.Request sumited successfully');
        return response()->json(['notification' => $notification],200);

    }

    public function addToWishlist($id){
        $user = Auth::guard('api')->user();
        $product = Product::find($id);
        $isExist = Wishlist::where(['user_id' => $user->id, 'product_id' => $product->id])->count();
        if($isExist == 0){
            $wishlist = new Wishlist();
            $wishlist->product_id = $id;
            $wishlist->user_id = $user->id;
            $wishlist->save();
            $wishlist = Wishlist::with('product')->where(['id' => $wishlist->id])->first();
             
            $message = trans('user_validation.Wishlist added successfully');
            return response()->json(['message' => $message, 'wishlist' => $wishlist]);
        }else{
            $message = trans('user_validation.Product Already added');
            return response()->json(['message' => $message],403);
        }
    }

    public function removeWishlist($id){
        $user = Auth::guard('api')->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $wishlist = Wishlist::where('id', $id)->where('user_id', $user->id)->first();
        if (!$wishlist) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $wishlist->delete();
        $notification = trans('user_validation.Removed successfully');
        return response()->json(['notification' => $notification]);
    }

    public function clearWishlist(){
        $user = Auth::guard('api')->user();
        Wishlist::where(['user_id' => $user->id])->delete();

        $notification = trans('user_validation.Clear successfully');
        return response()->json(['notification' => $notification]);
    }



    public function storeProductReport(Request $request){

        $rules = [
            'subject'=>'required',
            'description'=>'required',
            'product_id'=>'required',
        ];

        $customMessages = [
            'subject.required' => trans('user_validation.Subject filed is required'),
            'description.required' => trans('user_validation.Description filed is required'),
            'product_id.required' => trans('user_validation.Product is required')
        ];
        $this->validate($request, $rules,$customMessages);

        $product = Product::find($request->product_id);
        $user = Auth::guard('api')->user();
        $report = new ProductReport();
        $report->user_id = $user->id;
        $report->seller_id = $product->vendor_id;
        $report->product_id = $request->product_id;
        $report->subject = $request->subject;
        $report->description = $request->description;
        $report->save();

        $message = trans('user_validation.Report Submited successfully');
        return response()->json(['message' => $message]);

    }

    public function review(){
        $user = Auth::guard('api')->user();
        $reviews = ProductReview::with('product')->orderBy('id','desc')->where(['user_id' => $user->id])->paginate(10);

        return response()->json(['reviews' => $reviews]);
    }

    public function showReview($id){
        $user = Auth::guard('api')->user();
        $review = ProductReview::with('product')->where(['user_id' => $user->id, 'status' => 1, 'id' => $id])->first();

        return response()->json(['review' => $review]);
    }

    public function storeProductReview(Request $request){
        $rules = [
            'rating'=>'required',
            'review'=>'required',
            'product_id'=>'required',
            'order_id'=>'required',
            'g-recaptcha-response'=>new Captcha()
        ];
        $customMessages = [
            'rating.required' => trans('user_validation.Rating is required'),
            'review.required' => trans('user_validation.Review is required'),
            'product_id.required' => trans('user_validation.Product is required'),
            'order_id.required' => 'Sipariş bilgisi gereklidir',
        ];
        $this->validate($request, $rules,$customMessages);

        $user = Auth::guard('api')->user();
        // Ürün bazlı teslim/onar kontrolü (pazaryeri uyumu)
        $order = Order::query()
            ->where('user_id', $user->id)
            ->where('order_id', $request->order_id)
            ->first();

        if (! $order) {
            return response()->json(['message' => 'Sipariş bulunamadı'], 404);
        }

        $orderProductQuery = OrderProduct::query()
            ->where('order_id', $order->id)
            ->where('product_id', $request->product_id);

        // Eğer frontend satır id'sini gönderebiliyorsa daha net eşleştir.
        if ($request->filled('order_product_id')) {
            $orderProductQuery->where('id', $request->order_product_id);
        }

        $orderProduct = $orderProductQuery->first();
        $isDeliveredOrder = $orderProduct
            && (
                !empty($orderProduct->delivered_at)
                || !empty($orderProduct->customer_confirmed_at)
                || !empty($orderProduct->auto_confirmed_at)
            );

        if($isDeliveredOrder){
            // 1 sipariş = 1 yorum kontrolü (order_id + product_id + user_id)
            $isReview = ProductReview::where([
                'product_id' => $request->product_id, 
                'user_id' => $user->id,
                'order_id' => $request->order_id
            ])->count();
            if($isReview > 0){
                $message = 'Bu sipariş için zaten yorum yaptınız.';
                return response()->json(['message' => $message],403);
            }

            $product = Product::find($request->product_id);
            $review = new ProductReview();
            $review->user_id = $user->id;
            $review->order_id = $request->order_id;
            $review->rating = $request->rating;
            $review->review = $request->review;
            $review->product_vendor_id = $product->vendor_id;
            $review->product_id = $request->product_id;

            if ($request->hasFile('image')) {
                $ext = $request->image->getClientOriginalExtension();
                $name = 'review-' . date('Y-m-d-h-i-s-') . rand(999, 9999) . '.' . $ext;
                $path = 'uploads/custom-images/' . $name;
                \Image::make($request->image)->save(public_path() . '/' . $path);
                $review->image = $path;
            }

            $review->save();
            $message = trans('user_validation.Review Submited successfully');
            return response()->json(['message' => $message]);
        }else{
            $message = 'Yorum yapabilmek için ürünün teslim edilmiş olması gerekmektedir.';
            return response()->json(['message' => $message],403);
        }

    }

    /**
     * Müşteri ürün bazlı "teslim aldım" onayı verir.
     * Tüm satırlar onaylanınca sipariş tamamlanır ve komisyon settle edilir.
     */
    public function confirmOrderProductDelivery(Request $request, $orderProductId)
    {
        $user = Auth::guard('api')->user();

        $orderProduct = OrderProduct::query()->find($orderProductId);
        if (! $orderProduct) {
            return response()->json(['message' => 'Sipariş ürünü bulunamadı'], 404);
        }

        $order = Order::query()
            ->where('id', $orderProduct->order_id)
            ->where('user_id', $user->id)
            ->first();

        if (! $order) {
            return response()->json(['message' => 'Sipariş bulunamadı'], 404);
        }

        // Teslim edilmeden onay verilemez (satır bazlı)
        if (! $orderProduct->delivered_at) {
            return response()->json(['message' => 'Bu ürün henüz teslim edilmemiş görünüyor'], 400);
        }

        if ($orderProduct->customer_confirmed_at) {
            return response()->json(['message' => 'Bu ürün zaten onaylanmış'], 400);
        }

        $orderProduct->customer_confirmed_at = now();
        if (! $orderProduct->payout_eligible_at) {
            $payoutSettings = app(\App\Services\PayoutSettingsService::class);
            $orderProduct->payout_eligible_at = now()->addDays($payoutSettings->payoutHoldDays());
        }
        $orderProduct->save();

        // Tüm ürünler onaylandı/auto-confirm olduysa siparişi tamamla
        $allConfirmed = OrderProduct::query()
            ->where('order_id', $order->id)
            ->whereNotNull('delivered_at')
            ->whereNull('customer_confirmed_at')
            ->whereNull('auto_confirmed_at')
            ->count() === 0;

        if ($allConfirmed && ! $order->customer_confirmed_at) {
            $order->customer_confirmed_at = now();
            $order->order_status = 3; // Tamamlandı
            $order->order_completed_date = date('Y-m-d');
            $order->save();

            $commissionService = app(\App\Services\CommissionService::class);
            $commissionService->settleCommissions($order);
            app(\App\Services\SellerPayoutService::class)->schedulePayoutEligibility($order);

            \Log::info('Müşteri satır bazlı onayladı, sipariş tamamlandı', [
                'order_id' => $order->id,
                'order_number' => $order->order_id,
            ]);
        }

        return response()->json([
            'message' => 'Ürün teslim onayınız alındı.',
            'order_product' => $orderProduct
        ]);
    }

    public function compareProducts(){
        $user = Auth::guard('api')->user();
        if (! $user) {
            return response()->json(['message' => 'UnAuthenticated'], 401);
        }

        $compareProducts = CompareProduct::where('user_id', $user->id)->get();

        $product_arr = [];
        foreach($compareProducts as $compareProduct){
            $product_arr[] = $compareProduct->product_id;
        }

        $products = Product::whereIn('id', $product_arr)->with('specifications.key','activeVariants.activeVariantItems')->where(['status' => 1])->select('id','name', 'short_name', 'slug', 'thumb_image','qty','sold_qty', 'price', 'offer_price')->get();


        return response()->json(['products' => $products]);
    }

    public function addCompareProducts($id){
        $user = Auth::guard('api')->user();
        if (! $user) {
            return response()->json(['message' => 'UnAuthenticated'], 401);
        }

        $total =CompareProduct::where(['user_id' => $user->id])->count();

        if(3 <= $total){
            $notification = trans('user_validation.Already 3 items added');
            return response()->json(['notification' => $notification],403);
        }

        $isExist = CompareProduct::where(['user_id' => $user->id, 'product_id' => $id])->count();

        if($isExist == 0){
            $compare = new CompareProduct();
            $compare->user_id = $user->id;
            $compare->product_id = $id;
            $compare->save();

            $product = Product::where('id', $id)->with('specifications.key','activeVariants.activeVariantItems')->where(['status' => 1])->select('id','name', 'short_name', 'slug', 'thumb_image','qty','sold_qty', 'price', 'offer_price')->first();

            $notification = trans('user_validation.Item added successfully');
            return response()->json(['notification' => $notification, 'product' => $product]);
        }else{
            $notification = trans('user_validation.Already added this item');
            return response()->json(['notification' => $notification],403);
        }

        return response()->json(['compareProducts' => $compareProducts]);
    }


    public function deleteCompareProduct($id){
        $user = Auth::guard('api')->user();
        CompareProduct::where(['user_id' => $user->id, 'product_id' => $id])->delete();

        $notification = trans('user_validation.Item remmoved successfully');
        return response()->json(['notification' => $notification]);
    }

    /**
     * Müşteri siparişi teslim aldığını onaylar
     * Bu onaydan 3 gün sonra satıcıya otomatik ödeme yapılır
     */
    public function confirmDelivery(Request $request, $orderId)
    {
        $user = Auth::guard('api')->user();

        $order = Order::where('user_id', $user->id)
            ->where('order_id', $orderId)
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Sipariş bulunamadı'], 404);
        }

        // Sadece teslim edilmiş (order_status = 2) siparişleri onaylayabilir
        if ($order->order_status != 2) {
            return response()->json([
                'message' => 'Sadece teslim edilmiş siparişleri onaylayabilirsiniz'
            ], 400);
        }

        // Zaten onaylanmışsa
        if ($order->customer_confirmed_at) {
            return response()->json([
                'message' => 'Bu sipariş zaten onaylanmış'
            ], 400);
        }

        // Teslim onayını kaydet
        $order->customer_confirmed_at = now();
        $order->order_status = 3; // Tamamlandı
        $order->order_completed_date = date('Y-m-d');
        $order->save();

        // Komisyonu settle et
        $commissionService = app(\App\Services\CommissionService::class);
        $commissionService->settleCommissions($order);
        app(\App\Services\SellerPayoutService::class)->schedulePayoutEligibility($order);

        \Log::info('Müşteri siparişi onayladı - payout bekleme süresi başladı', [
            'order_id' => $order->id,
            'order_number' => $order->order_id,
            'customer_confirmed_at' => $order->customer_confirmed_at
        ]);

        // Müşteriye teslim onay e-postası gönder
        $this->sendDeliveryConfirmedMail($user, $order);

return response()->json([
            'message' => 'Siparişiniz onaylandı. Teşekkürler!',
            'order' => $order
        ]);
    }

    /**
     * Sipariş listesi için hafif ilişkiler (mevcut response yapısını korur).
     */
    private function userOrderListRelations(): array
    {
        return [
            'deliveryman',
            'orderProducts' => function ($query) {
                $query->select('id', 'order_id', 'product_id', 'product_name', 'qty');
            },
        ];
    }

    /**
     * Liste endpoint'lerinde order_products satırlarına thumb_image ekler.
     */
    private function hydrateOrderList($orders): void
    {
        foreach ($orders as $order) {
            if ($order->relationLoaded('orderProducts')) {
                $this->attachThumbImagesToOrderProducts($order->orderProducts);
            }
        }
    }

    /**
     * order_products JSON'ına düz thumb_image alanı ekler (cargo gibi).
     * İlişki objesi eklemez; mevcut mobil/web yapısı bozulmaz.
     */
    private function attachThumbImagesToOrderProducts($orderProducts): void
    {
        if ($orderProducts->isEmpty()) {
            return;
        }

        $productIds = $orderProducts->pluck('product_id')->filter()->unique()->values();
        if ($productIds->isEmpty()) {
            return;
        }

        $thumbs = Product::query()
            ->whereIn('id', $productIds)
            ->pluck('thumb_image', 'id');

        $orderProducts->each(function ($orderProduct) use ($thumbs) {
            $orderProduct->thumb_image = $thumbs[$orderProduct->product_id] ?? '';
        });
    }

    private function attachCargoToOrderProducts(Order $order): void
    {
        if (! Schema::hasTable('cargo_shipments')) {
            $order->orderProducts->each(function ($orderProduct) {
                $orderProduct->cargo = null;
            });

            return;
        }

        $shipments = CargoShipment::query()
            ->where('order_id', $order->id)
            ->whereNotIn('status', ['cancelled'])
            ->orderByDesc('id')
            ->get();

        $shipmentBySeller = [];
        foreach ($shipments as $shipment) {
            $sellerId = (int) ($shipment->seller_id ?? 0);
            if (! isset($shipmentBySeller[$sellerId])) {
                $shipmentBySeller[$sellerId] = $shipment;
            }
        }

        $order->orderProducts->each(function ($orderProduct) use ($shipmentBySeller) {
            $sellerId = (int) ($orderProduct->seller_id ?? 0);
            $shipment = $shipmentBySeller[$sellerId] ?? null;
            $orderProduct->cargo = $shipment ? [
                'carrier_name' => $shipment->carrier_name,
                'tracking_number' => $shipment->tracking_number,
                'tracking_url' => $shipment->tracking_url,
                'status' => $shipment->status,
            ] : null;
        });
    }

    /**
     * Müşteriye teslim onay e-postası gönder
     */
    private function sendDeliveryConfirmedMail($user, $order)
    {
        try {
            MailHelper::setMailConfig();
            $mailData = [
                'user_name' => $user->name . ' ' . $user->surname,
                'order_number' => $order->order_id,
                'order_date' => $order->order_approval_date,
                'completed_date' => $order->order_completed_date,
                'total_price' => $order->total_price ?? 0,
            ];

            \Mail::send('emails.order_delivered', $mailData, function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Siparişiniz Tamamlandı - ' . config('app.name'));
            });
        } catch (\Exception $e) {
            \Log::error('Teslim onay e-postası gönderilemedi: ' . $e->getMessage());
        }
    }
}
