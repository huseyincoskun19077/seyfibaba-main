<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BankPayment;
use App\Models\IyzicoPayment;
use App\Models\CurrencyCountry;
use App\Models\Currency;
use App\Models\Setting;

class PaymentMethodController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin-api');
    }

    public function index(){
        $bank = BankPayment::first();
        $iyzico = IyzicoPayment::first();

        $countires = CurrencyCountry::orderBy('name','asc')->get();
        $currencies = Currency::orderBy('name','asc')->get();
        $setting = Setting::first();

        return response()->json(['bank' => $bank, 'iyzico' => $iyzico, 'countires' => $countires, 'currencies' => $currencies, 'setting' => $setting], 200);
    }

    public function updateBank(Request $request){
        $rules = [
            'account_info' => 'required'
        ];
        $customMessages = [
            'account_info.required' => trans('Account information is required'),
        ];
        $this->validate($request, $rules,$customMessages);
        $bank = BankPayment::first();
        $bank->account_info = $request->account_info;
        $bank->status = $request->status ? 1 : 0;
        $bank->save();

        $notification=trans('Update Successfully');
        return response()->json(['notification' => $notification], 200);

    }

    public function updateIyzico(Request $request){
        $rules = [
            'api_key' => 'required',
            'secret_key' => 'required',
        ];
        $customMessages = [
            'api_key.required' => trans('Iyzico API Key is required'),
            'secret_key.required' => trans('Iyzico Secret Key is required'),
        ];
        $this->validate($request, $rules, $customMessages);

        $iyzico = IyzicoPayment::firstOrCreate([]);
        $iyzico->api_key = $request->api_key;
        $iyzico->secret_key = $request->secret_key;
        $iyzico->currency_id = $request->currency_name;
        $iyzico->is_test_mode = $request->is_test_mode ? 1 : 0;
        $iyzico->marketplace_mode = $request->marketplace_mode ? 1 : 0;
        $iyzico->sub_merchant_key = $request->sub_merchant_key;
        $iyzico->store_sub_merchant_keys = $request->store_sub_merchant_keys;
        $iyzico->status = $request->status ? 1 : 0;
        $iyzico->save();

        $notification = trans('Updated Successfully');
        return response()->json(['notification' => $notification], 200);
    }

    public function updateCashOnDelivery(Request $request){
        $bank = BankPayment::first();
        if($bank->cash_on_delivery_status==1){
            $bank->cash_on_delivery_status=0;
            $bank->save();
            $message= trans('Inactive Successfully');
        }else{
            $bank->cash_on_delivery_status=1;
            $bank->save();
            $message= trans('Active Successfully');
        }
        return response()->json($message);
    }

}
