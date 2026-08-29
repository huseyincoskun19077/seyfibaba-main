<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\SmsTemplate;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function sms_configuration()
    {
        $setting = Setting::first();

        return view('admin.sms_configuration', compact('setting'));
    }

    public function update_netgsm(Request $request)
    {
        $rules = [
            'netgsm_usercode' => 'required',
            'netgsm_password' => 'required',
            'netgsm_msgheader' => 'required',
        ];
        $customMessages = [
            'netgsm_usercode.required' => 'Kullanıcı kodu zorunludur',
            'netgsm_password.required' => 'Şifre zorunludur',
            'netgsm_msgheader.required' => 'Mesaj başlığı zorunludur',
        ];
        $this->validate($request, $rules, $customMessages);

        $setting = Setting::first();
        $setting->update([
            'netgsm_usercode' => $request->netgsm_usercode,
            'netgsm_password' => $request->netgsm_password,
            'netgsm_msgheader' => $request->netgsm_msgheader,
            'netgsm_enabled' => $request->has('netgsm_enabled') ? 1 : 0,
        ]);

        $notification = array('messege' => 'Netgsm ayarları güncellendi', 'alert-type' => 'success');
        return redirect()->back()->with($notification);
    }



    public function sms_template(){


        $templates = SmsTemplate::all();
        return view('admin.sms_template',compact('templates'));
    }

    public function edit_sms_template($id){
        $template = SmsTemplate::find($id);

        return view('admin.edit_sms_template',compact('template'));
    }

    public function update_sms_template(Request $request,$id){
        $rules = [
            'description'=>'required',
        ];
        $customMessages = [
            'description.required' => trans('admin_validation.Description is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $template = SmsTemplate::find($id);
        if($template){
            $template->subject = $request->subject;
            $template->description = $request->description;
            $template->save();
            $notification= trans('admin_validation.Updated Successfully');
            $notification = array('messege'=>$notification,'alert-type'=>'success');
            return redirect()->back()->with($notification);
        }else{
            $notification= trans('admin_validation.Something went wrong');
            $notification = array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->back()->with($notification);
        }
    }
}
