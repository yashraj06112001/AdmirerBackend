<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class DashboardController extends Controller
{
    //
    public function profileInfo(Request $request)
    {
        $user=Auth::user();
        $firstName=$user->firstname;
        $lastName=$user->lastname;
        $mobile=$user->mobile;
        $email=$user->email;
        $flat=$user->flat;
        $street=$user->street;
        $locality=$user->locality;
        $city=$user->city;
        $zipcode=$user->zipcode;
        $state=$user->state;
        $country=$user->country;
        $addr_type=$user->addr_type;
        $status=$user->status;
       // name of the state
        $state_name=DB::table("state_list")->select("state")->where("id","=",$state)->value("state");;
        return response()->json([
          "first_name"=>$firstName,
          "last_name"=>$lastName,
          "mobile"=>$mobile,
          "email"=>$email,
          "flat"=>$flat,
         "street"=>$street,
         "locality"=>$locality,
         "city"=>$city,
         "zipcode"=>$zipcode,
         "state"=>$state_name,
         "country"=>$country,
         "address_type"=>$addr_type,
         "status"=>$status
        ]);
        
    }



}
