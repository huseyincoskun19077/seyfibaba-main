<?php

namespace App\Http\Controllers\Admin;

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
        $this->middleware('auth:admin-api');
    }

    public function index(){
        $withdraws = SellerWithdraw::with('seller')->orderBy('id','desc')->get();
        $setting = Setting::first();

        return response()->json(['withdraws' => $withdraws, 'setting' => $setting], 200);
    }

    public function pendingSellerWithdraw(){
        $withdraws = SellerWithdraw::with('seller')->orderBy('id','desc')->where('status',0)->get();
        $setting = Setting::first();

        return response()->json(['withdraws' => $withdraws, 'setting' => $setting], 200);
    }

    public function show($id){
        $setting = Setting::first();
        $withdraw = SellerWithdraw::with('seller')->find($id);
        return response()->json(['withdraw' => $withdraw, 'setting' => $setting], 200);
    }

    public function destroy($id){
        $withdraw = SellerWithdraw::find($id);
        $withdraw->delete();
        $notification = trans('Delete Successfully');
        return response()->json(['notification' => $notification], 200);
    }

    public function approvedWithdraw($id){
        $withdraw = SellerWithdraw::with(['seller.user'])->find($id);
        if (! $withdraw) {
            return response()->json(['message' => trans('admin_validation.Record not found')], 404);
        }
        if ((int) $withdraw->status !== 0) {
            return response()->json(['message' => trans('admin_validation.This request is already processed')], 422);
        }
        if (! $withdraw->seller || ! $withdraw->seller->user) {
            return response()->json(['message' => trans('admin_validation.Seller or user record missing')], 422);
        }
        if (! $this->commissionService->canApproveSellerWithdraw($withdraw)) {
            return response()->json(['message' => trans('admin_validation.Withdraw approval blocked insufficient balance')], 422);
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

        return response()->json(['notification' => trans('admin_validation.Withdraw request approval successfully')], 200);
    }
}
