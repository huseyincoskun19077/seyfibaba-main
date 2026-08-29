<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BreadcrumbImage;
use Auth;
use App\Models\Country;
use App\Models\CountryState;
use App\Models\City;
use App\Models\Address;
use App\Models\Vendor;
use App\Models\Setting;
use App\Models\Wishlist;
use App\Models\BankPayment;
use App\Models\ShoppingCart;
use App\Models\Coupon;
use App\Models\Shipping;
use App\Models\IyzicoPayment;
use Cart;
use Session;
class CheckoutController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Radius of the Earth in kilometers

        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $latDifference = $lat2 - $lat1;
        $lonDifference = $lon2 - $lon1;

        $a = sin($latDifference / 2) * sin($latDifference / 2) +
            cos($lat1) * cos($lat2) *
            sin($lonDifference / 2) * sin($lonDifference / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earthRadius * $c; // Distance in kilometers

        return $distance;
    }


    public function checkout(Request $request){
        $user = Auth::guard('api')->user();
        $cartProducts = ShoppingCart::with('product','variants.variantItem')->where('user_id', $user->id)->select('id','product_id','qty')->get();
        $bankPaymentInfo = BankPayment::first();
        $iyzico = IyzicoPayment::select('status','marketplace_mode','is_test_mode')->first();

        if($cartProducts->count() == 0){
            $addresses = Address::with('country', 'countryState', 'city')->where(['user_id' => $user->id])->get();
            $shippings = Shipping::all();
            return response()->json([
                'cartProducts' => $cartProducts,
                'addresses' => $addresses,
                'shippings' => $shippings,
                'couponOffer' => '',
                'bankPaymentInfo' => $bankPaymentInfo,
                'iyzico' => $iyzico,
                'message' => trans('user_validation.Your shopping cart is empty'),
            ],200);
        }


        $setting = Setting::first();

        if ($setting->map_status == 1) {
            $vendorIds = $cartProducts->pluck('product.vendor_id')->unique();
            $vendor_id = $vendorIds[0];

            if ($vendor_id == 0) {
                $vendor_lat_long = Admin::where('id', 1)->select('latitude', 'longitude')->first();
            } else {
                $find_user = Vendor::where('id', $vendor_id)->select('user_id')->first();
                $vendor_lat_long = User::where('id', $find_user->user_id)->select('id', 'latitude', 'longitude')->first();
            }

            $addresses = Address::with('country', 'countryState', 'city')->where(['user_id' => $user->id])->get();

            $addresses_with_distance = $addresses->map(function ($address) use ($vendor_lat_long, $setting) {
                $distance = $this->calculateDistance(
                    $vendor_lat_long['latitude'],
                    $vendor_lat_long['longitude'],
                    $address->latitude,
                    $address->longitude
                );

                $address->distance_in_km = $distance;
                $address->per_km_price_range = $setting->per_km_price_range;

                return $address;
            });

        } else {
            $addresses = Address::with('country', 'countryState', 'city')->where(['user_id' => $user->id])->get();
        }


        $shippings = Shipping::all();

        $couponOffer = '';
        if($request->coupon){
            $coupon = Coupon::where(['code' => $request->coupon, 'status' => 1])->first();
            if($coupon){
                if($coupon->expired_date >= date('Y-m-d')){
                    if($coupon->apply_qty <  $coupon->max_quantity ){
                        $couponOffer = $coupon;
                    }
                }
            }
        }

        return response()->json([
            'cartProducts' => $cartProducts,
            'addresses' => $addresses,
            'shippings' => $shippings,
            'couponOffer' => $couponOffer,
            'bankPaymentInfo' => $bankPaymentInfo,
            'iyzico' => $iyzico,
        ],200);

    }



}
