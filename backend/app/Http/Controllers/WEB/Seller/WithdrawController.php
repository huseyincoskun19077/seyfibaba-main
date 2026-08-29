<?php

namespace App\Http\Controllers\WEB\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WithdrawMethod;
use App\Models\SellerWithdraw;
use App\Models\OrderProduct;
use App\Models\Setting;
use App\Services\CommissionService;
use Auth;
class WithdrawController extends Controller
{
    protected $commissionService;

    public function __construct(CommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
        $this->middleware('auth:web');
    }

    public function index(){
        $user = Auth::guard('web')->user();
        $seller = $user->seller;
        $withdraws = SellerWithdraw::where('seller_id',$seller->id)->get();
        $setting = Setting::first();
        $earnings = $this->commissionService->getSellerEarningsSummary($seller->id);

        return view('seller.withdraw', compact('withdraws','setting', 'earnings'));

    }

    public function show($id){
        $seller = Auth::guard('web')->user()->seller;
        $withdraw = SellerWithdraw::where('seller_id', $seller->id)->findOrFail($id);

        $setting = Setting::first();
        return view('seller.show_withdraw', compact('withdraw','setting'));
    }

    public function create(){
        $methods = WithdrawMethod::whereStatus('1')->get();

        $setting = Setting::first();
        $seller = Auth::guard('web')->user()->seller;
        $earnings = $this->commissionService->getSellerEarningsSummary($seller->id);

        return view('seller.create_withdraw', compact('methods','setting', 'earnings'));
    }

    public function getWithDrawAccountInfo($id){
        $method = WithdrawMethod::whereId($id)->first();

        $setting = Setting::first();
        return view('seller.withdraw_account_info', compact('method','setting'));
    }

    public function store(Request $request){
        $rules = [
            'method_id' => 'required',
            'withdraw_amount' => 'required|numeric',
            'account_info' => 'required',
        ];

        $customMessages = [
            'method_id.required' => trans('admin_validation.Payment Method filed is required'),
            'withdraw_amount.required' => trans('admin_validation.Withdraw amount filed is required'),
            'withdraw_amount.numeric' => trans('admin_validation.Please provide valid numeric number'),
            'account_info.required' => trans('admin_validation.Account filed is required'),
        ];

        $this->validate($request, $rules, $customMessages);

        $user = Auth::guard('web')->user();
        $seller = $user->seller;

        $breakdown = $this->commissionService->getSellerBalanceBreakdown($seller->id);
        if (empty($breakdown['withdraw_request_allowed'])) {
            $notification = 'Kredi kartı (İyzico) ödemeleri çekim talebi ile alınamaz. Havale siparişlerinde bekleme süresi dolduktan sonra çekim talebi oluşturabilirsiniz.';
            $notification = ['messege' => $notification, 'alert-type' => 'error'];
            return redirect()->back()->with($notification);
        }

        $currentAmount = (float) $breakdown['bank_withdrawable_balance'];

        if($request->withdraw_amount > $currentAmount){
            $notification = trans('admin_validation.Sorry! Your Payment request is more then your current balance');
            return response()->json(['notification' => $notification], 400);
        }

        $method = WithdrawMethod::whereId($request->method_id)->first();
        if($request->withdraw_amount >= $method->min_amount && $request->withdraw_amount <= $method->max_amount){
            $user = Auth::guard('web')->user();
            $seller = $user->seller;
            $widthdraw = new SellerWithdraw();
            $widthdraw->seller_id = $seller->id;
            $widthdraw->method = $method->name;
            $widthdraw->total_amount = $request->withdraw_amount;
            $withdraw_request = $request->withdraw_amount;
            $withdraw_charge = ($method->withdraw_charge / 100) * $withdraw_request;
            $widthdraw->withdraw_amount = $request->withdraw_amount - $withdraw_charge;
            $widthdraw->withdraw_charge = $withdraw_charge;
            $widthdraw->account_info = $request->account_info;
            $widthdraw->save();
            $notification = trans('admin_validation.Withdraw request send successfully, please wait for admin approval');
            $notification=array('messege'=>$notification,'alert-type'=>'success');
            return redirect()->route('seller.my-withdraw.index')->with($notification);

        }else{
            $notification = trans('admin_validation.Your amount range is not available');
            $notification=array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->back()->with($notification);
        }

    }
}
