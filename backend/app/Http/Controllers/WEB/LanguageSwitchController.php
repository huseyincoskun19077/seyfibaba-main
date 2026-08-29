<?php

namespace App\Http\Controllers\WEB;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Language;
use Session;

class LanguageSwitchController extends Controller
{

    public function language_switcher(Request $request){

        $request_lang = Language::where('lang_code', $request->lang_code)->first();

       if($request->lang_code == 'ar'){
        Session::put('lang_dir', 'right_to_left');
       }else{
        Session::put('lang_dir', 'left_to_right');
       }

       Session::put('front_lang', $request->lang_code);
       Session::put('lang_name', $request_lang->lang_name);
       Session::put('lang_manually_selected', true);

        $notification= trans('admin_validation.Language switched successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);

    }
}
