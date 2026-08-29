<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Language;
use Session, Config, Route;
class LangSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $default_lang = Language::where('lang_code', 'tr')->first()
            ?? Language::whereRaw('LOWER(is_default) = ?', ['yes'])->first()
            ?? Language::first();

        if(!Session::get('lang_manually_selected')){
            if ($default_lang) {
                Session::put('front_lang', $default_lang->lang_code);
                Session::put('lang_name', $default_lang->lang_name);
                Session::put('lang_dir', $default_lang->lang_direction);
            } else {
                Session::put('front_lang', 'tr');
                Session::put('lang_name', 'Türkçe');
                Session::put('lang_dir', 'left_to_right');
            }

            app()->setLocale(Session::get('front_lang', 'tr'));
        } else {
            $is_exist = Language::where('lang_code', Session::get('front_lang'))->first();

            if(!$is_exist){
                if ($default_lang) {
                    Session::put('front_lang', $default_lang->lang_code);
                    Session::put('lang_name', $default_lang->lang_name);
                    Session::put('lang_dir', $default_lang->lang_direction);
                } else {
                    Session::put('front_lang', 'tr');
                    Session::put('lang_name', 'Türkçe');
                    Session::put('lang_dir', 'left_to_right');
                }
            }

            app()->setLocale(Session::get('front_lang'));
        }

        return $next($request);
    }
}
