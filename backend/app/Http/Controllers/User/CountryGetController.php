<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\CountryState;
use Illuminate\Http\Request;

class CountryGetController extends Controller
{
    public function countryList(){
        $countries = Country::orderBy('name','asc')->where('status',1)->select('id','name')->get();

        return response()->json(['countries' => $countries]);
    }

    public function stateByCountry($id){
        $states = CountryState::select('id','name')->where(['status' => 1, 'country_id' => $id])->get();
        return response()->json(['states'=>$states]);
    }

    public function cityByState($id){
        $cities = City::select('id','country_state_id','name')->where(['status' => 1, 'country_state_id' => $id])->get();
        return response()->json(['cities'=>$cities]);
    }
}
