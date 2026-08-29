<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WithdrawMethod;
use App\Models\SellerWithdraw;
use App\Models\Setting;
use App\Models\EmailTemplate;
use App\Helpers\MailHelper;
use App\Mail\SellerWithdrawApproval;
use App\Services\CommissionService;
use Mail;
use Auth;

class SellerWithdrawController extends Controller
{
    public function __construct(private CommissionService $commissionService)
    {
        $this->middleware('auth:admin');
    }

    public function index(){
        $withdraws = SellerWithdraw::with('seller')->orderBy('id','desc')->get();
        $setting = Setting::first();

        return view('admin.seller_withdraw', compact('withdraws','setting'));
    }

    public function pendingSellerWithdraw(){
        $withdraws = SellerWithdraw::with('seller')->orderBy('id','desc')->where('status',0)->get();
        $setting = Setting::first();

        return view('admin.seller_withdraw', compact('withdraws','setting'));
    }

    public function show($id){
        $setting = Setting::first();
        $withdraw = SellerWithdraw::with('seller')->find($id);
        return view('admin.show_seller_withdraw', compact('withdraw','setting'));
    }

    public function destroy($id){
        $withdraw = SellerWithdraw::find($id);
        $withdraw->delete();
        $notification = trans('admin_validation.Delete Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.seller-withdraw')->with($notification);
    }

    public function approvedWithdraw($id){
        $withdraw = SellerWithdraw::with(['seller.user'])->find($id);
        if (! $withdraw) {
            $notification = ['messege' => trans('admin_validation.Record not found'), 'alert-type' => 'error'];

            return redirect()->route('admin.seller-withdraw')->with($notification);
        }
        if ((int) $withdraw->status !== 0) {
            $notification = ['messege' => trans('admin_validation.This request is already processed'), 'alert-type' => 'warning'];

            return redirect()->route('admin.show-seller-withdraw', $withdraw->id)->with($notification);
        }
        if (! $withdraw->seller || ! $withdraw->seller->user) {
            $notification = ['messege' => trans('admin_validation.Seller or user record missing'), 'alert-type' => 'error'];

            return redirect()->route('admin.seller-withdraw')->with($notification);
        }
        if (! $this->commissionService->canApproveSellerWithdraw($withdraw)) {
            $notification = ['messege' => trans('admin_validation.Withdraw approval blocked insufficient balance'), 'alert-type' => 'error'];

            return redirect()->route('admin.show-seller-withdraw', $withdraw->id)->with($notification);
        }

        $withdraw->status = 1;
        $withdraw->approved_date = date('Y-m-d');
        $withdraw->save();

        $user = $withdraw->seller->user;
        $template = EmailTemplate::where('id', 5)->first();
        if ($template) {
            $message = $template->description;
            $subject = $template->subject;
            $message = str_replace('{{seller_name}}', $user->name, $message);
            $message = str_replace('{{withdraw_method}}', $withdraw->method, $message);
            $message = str_replace('{{total_amount}}', (string) $withdraw->total_amount, $message);
            $message = str_replace('{{withdraw_charge}}', (string) $withdraw->withdraw_charge, $message);
            $message = str_replace('{{withdraw_amount}}', (string) $withdraw->withdraw_amount, $message);
            $message = str_replace('{{approval_date}}', (string) $withdraw->approved_date, $message);
            MailHelper::setMailConfig();
            Mail::to($user->email)->send(new SellerWithdrawApproval($subject, $message));
        }

        $notification = trans('admin_validation.Withdraw request approval successfully');
        $notification = ['messege' => $notification, 'alert-type' => 'success'];

        return redirect()->route('admin.seller-withdraw')->with($notification);
    }
}
