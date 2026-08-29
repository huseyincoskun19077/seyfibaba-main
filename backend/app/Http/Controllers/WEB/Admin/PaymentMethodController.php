<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BankPayment;
use App\Models\CurrencyCountry;
use App\Models\Currency;
use App\Models\MultiCurrency;
use App\Models\Setting;
use App\Models\IyzicoPayment;

class PaymentMethodController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(){
        $bank = BankPayment::first();
        $iyzico = IyzicoPayment::first();

        $countires = CurrencyCountry::orderBy('name','asc')->get();
        $setting = Setting::first();

        $currencies = MultiCurrency::where('status',1)->orderBy('currency_name','asc')->get();

        return view('admin.payment_method', compact('bank','countires','currencies','setting','iyzico'));
    }

    public function updateBank(Request $request){
        $rules = [
            'account_info' => 'required'
        ];
        $customMessages = [
            'account_info.required' => trans('admin_validation.Account information is required'),
        ];
        $this->validate($request, $rules,$customMessages);
        $bank = BankPayment::first();
        $bank->account_info = $request->account_info;
        $bank->status = $request->status ? 1 : 0;
        $bank->save();

        $notification=trans('admin_validation.Update Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);

    }

    public function updateIyzico(Request $request){
        $rules = [
            'api_key' => 'required',
            'secret_key' => 'required',
            'currency_name' => 'required',
        ];
        $customMessages = [
            'api_key.required' => trans('admin_validation.Api key is required'),
            'secret_key.required' => trans('admin_validation.Secret key is required'),
            'currency_name.required' => trans('admin_validation.Currency name is required'),
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

        $notification=trans('admin_validation.Update Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }

    public function updateCashOnDelivery(Request $request){
        $bank = BankPayment::first();
        if($bank->cash_on_delivery_status==1){
            $bank->cash_on_delivery_status=0;
            $bank->save();
            $message= trans('admin_validation.Inactive Successfully');
        }else{
            $bank->cash_on_delivery_status=1;
            $bank->save();
            $message= trans('admin_validation.Active Successfully');
        }
        return response()->json($message);
    }

}
