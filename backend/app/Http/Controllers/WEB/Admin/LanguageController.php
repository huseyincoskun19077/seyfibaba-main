<?php

namespace App\Http\Controllers\WEB\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Language;
use Auth;
class LanguageController extends Controller
{
    private function resolveLanguageCode(?string $langCode, string $fileName): string
    {
        if ($langCode && file_exists(resource_path('lang/'.$langCode.'/'.$fileName))) {
            return $langCode;
        }

        $defaultLanguage = Language::where('lang_code', 'tr')->first()
            ?? Language::whereRaw('LOWER(is_default) = ?', ['yes'])->first()
            ?? Language::first();

        $defaultCode = $defaultLanguage?->lang_code ?: 'tr';

        if (file_exists(resource_path('lang/'.$defaultCode.'/'.$fileName))) {
            return $defaultCode;
        }

        return 'en';
    }

    public function adminLnagugae(Request $request){
        $langCode = $this->resolveLanguageCode($request->lang_code, 'admin.php');
        $data = include(resource_path('lang/'.$langCode.'/admin.php'));
        $languages = Language::get();
        return view('admin.admin_language', compact('data','languages'));
    }

    public function updateAdminLanguage(Request $request){

        $dataArray = [];
        foreach($request->values as $index => $value){
            $dataArray[$index] = $value;
        }
        file_put_contents(resource_path('lang/'.$request->lang_code.'/admin.php'), "");
        $dataArray = var_export($dataArray, true);
        file_put_contents(resource_path('lang/'.$request->lang_code.'/admin.php'), "<?php\n return {$dataArray};\n ?>");

        $notification= trans('admin_validation.Update Successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }

    public function adminValidationLnagugae(Request $request){
        $langCode = $this->resolveLanguageCode($request->lang_code, 'admin_validation.php');
        $data = include(resource_path('lang/'.$langCode.'/admin_validation.php'));
        $languages = Language::get();
        return view('admin.admin_validation_language', compact('data','languages'));
    }

    public function updateAdminValidationLnagugae(Request $request){
        $dataArray = [];
        foreach($request->values as $index => $value){
            $dataArray[$index] = $value;
        }
        file_put_contents(resource_path('lang/'.$request->lang_code.'/admin_validation.php'), "");
        $dataArray = var_export($dataArray, true);
        file_put_contents(resource_path('lang/'.$request->lang_code.'/admin_validation.php'), "<?php\n return {$dataArray};\n ?>");

        $notification= trans('admin_validation.Update Successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }

    public function websiteLanguage(Request $request){
        $langCode = $this->resolveLanguageCode($request->lang_code, 'user.php');
        $data = include(resource_path('lang/'.$langCode.'/user.php'));
        $languages = Language::get();
        return view('admin.language', compact('data','languages'));

    }

    public function updateLanguage(Request $request){

        $dataArray = [];
        foreach($request->values as $index => $value){
            $dataArray[$index] = $value;
        }
        file_put_contents(resource_path('lang/'.$request->lang_code.'/user.php'), "");
        $dataArray = var_export($dataArray, true);
        file_put_contents(resource_path('lang/'.$request->lang_code.'/user.php'), "<?php\n return {$dataArray};\n ?>");

        $notification= trans('admin_validation.Update Successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }


    public function websiteValidationLanguage(Request $request){
        $langCode = $this->resolveLanguageCode($request->lang_code, 'user_validation.php');
        $data = include(resource_path('lang/'.$langCode.'/user_validation.php'));
        $languages = Language::get();
        return view('admin.website_validation_language', compact('data','languages'));
    }

    public function updateValidationLanguage(Request $request){

        $dataArray = [];
        foreach($request->values as $index => $value){
            $dataArray[$index] = $value;
        }
        file_put_contents(resource_path('lang/'.$request->lang_code.'/user_validation.php'), "");
        $dataArray = var_export($dataArray, true);
        file_put_contents(resource_path('lang/'.$request->lang_code.'/user_validation.php'), "<?php\n return {$dataArray};\n ?>");

        $notification= trans('admin_validation.Update Successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }
}
