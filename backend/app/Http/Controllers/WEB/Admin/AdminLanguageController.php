<?php

namespace App\Http\Controllers\WEB\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Language;
use DB;
use File;

class AdminLanguageController extends Controller
{
    private function getPreferredDefaultLanguage(): ?Language
    {
        return Language::where('lang_code', 'tr')->first()
            ?? Language::whereRaw('LOWER(is_default) = ?', ['yes'])->first()
            ?? Language::first();
    }

    private function isProtectedLanguage(Language $language): bool
    {
        return $language->lang_code === 'tr';
    }

    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function languages(){
        $languages = Language::get();
        return view('admin.languages')->with([
            'languages' => $languages,
        ]);
    }

    public function create(){
        return view('admin.create_language');
    }

    public function store(Request $request){

        $rules = [
            'lang_name'=>'required|unique:languages',
            'lang_code'=>'required|unique:languages'
        ];
        $customMessages = [
            'lang_name.required' => trans('admin_validation.Name is required'),
            'lang_name.unique' => trans('admin_validation.Name already exist'),
            'lang_code.required' => trans('admin_validation.Code is required'),
            'lang_code.unique' => trans('admin_validation.Code already exist'),
        ];
        $this->validate($request, $rules,$customMessages);

        $language = new Language();

        if($request->is_default == 'Yes'){
            DB::table('languages')->update(['is_default' => 'No']);
        }

        $language->lang_name = $request->lang_name;
        $language->lang_code = $request->lang_code;
        $language->is_default = $request->is_default;
        $language->lang_direction = $request->lang_direction;
        $language->status = $request->status;
        $language->save();


        $path = base_path().'/resources/'.'lang/'.$request->lang_code;


        if (! File::exists($path)) {
            File::makeDirectory($path);


            $sourcePath = base_path().'/resources/'.'lang/en';
            $destinationPath = $path;

            // Get all files from the source folder
            $files = File::allFiles($sourcePath);

            foreach ($files as $file) {
                $destinationFile = $destinationPath . '/' . $file->getRelativePathname();

                // Copy the file to the destination folder
                File::copy($file->getRealPath(), $destinationFile);
            }

        }

        $notification=trans('admin_validation.Created Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.languages')->with($notification);
    }

    public function edit($id){
        $language = Language::findOrFail($id);
        return view('admin.edit_language')->with([
            'language' => $language,
        ]);
    }

    public function update(Request $request, $id){
        $language = Language::findOrFail($id);
        $isProtectedLanguage = $this->isProtectedLanguage($language);

        $rules = [
            'lang_name'=> $isProtectedLanguage ? '' : 'required|unique:languages,id,'.$id,
            'lang_code'=> $isProtectedLanguage ? '' : 'required|unique:languages,id,'.$id,
        ];
        $customMessages = [
            'lang_name.required' => trans('admin_validation.Name is required'),
            'lang_name.unique' => trans('admin_validation.Name already exist'),
            'lang_code.required' => trans('admin_validation.Code is required'),
            'lang_code.unique' => trans('admin_validation.Code already exist'),
        ];

        $this->validate($request, $rules,$customMessages);

        if (!$isProtectedLanguage) {
            $old_path = base_path().'/resources'.'/lang'.'/'.$language->lang_code;
            $update_path = base_path().'/resources'.'/lang'.'/'.$request->lang_code;

            if (File::exists($old_path) && $old_path != $update_path) {
                File::move($old_path, $update_path);
            }
        }

        if($request->is_default == 'Yes'){
            DB::table('languages')->where('id', '!=', $language->id)->update(['is_default' => 'No']);
        }

        if($language->is_default == 'Yes' && $request->is_default == 'No'){
            $fallbackLanguage = $this->getPreferredDefaultLanguage();
            if ($fallbackLanguage) {
                DB::table('languages')
                    ->where('id', $fallbackLanguage->id)
                    ->update(['is_default' => 'Yes']);
            }
        }

        if (!$isProtectedLanguage) {
            $language->lang_name = $request->lang_name;
        }

        if (!$isProtectedLanguage) {
            $language->lang_code = $request->lang_code;
        }


        $language->is_default = $request->is_default;

        $language->lang_direction = $request->lang_direction;

        if (!$isProtectedLanguage) {
            $language->status = $request->status;
        }

        $language->save();


        $notification=trans('admin_validation.Updated Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.languages')->with($notification);
    }

    public function destroy($id)
    {
        $language = Language::findOrFail($id);
        $path = base_path().'/resources'.'/lang'.'/'.$language->lang_code;

        if (File::exists($path)) {
            File::deleteDirectory($path);
        }
        $language->delete();

        $notification = trans('admin_validation.Delete Successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.languages')->with($notification);
    }
}
