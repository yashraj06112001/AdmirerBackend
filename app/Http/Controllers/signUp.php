<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
class signUp extends Controller
{
     public function signUpHandler(Request $request)
    {
        $id=Auth::user()->id;
        $firstName=$request->firstname;
        $lastName=$request->lastname;
        $email=$request->email;
        $flat=$request->flat;
        $street=$request->street;
        $locality=$request->locality;
        $city=$request->city;
        $state=$request->state;
        $pincode=$request->pincode;
        $stateId=DB::table("state_list")->select('state_list.id')->where('state_list.state','=',$state)->get();
        $addressFlat=DB::table('user')->select("user.flat")->where('user.id','=',$id)->get()->first();
       if($addressFlat)
       {
        //Addition of this data inside user_shipping_addresses
        DB::table("user_shipping_addresses")->insert([
           "first_name"=>$firstName,
           "last_name"=>$lastName,
           "email"=>$email,
           "flat"=>$flat,
           "street"=>$street,
           "locality"=>$locality,
           "city"=>$city,
           "zipcode"=>$pincode,
           "state"=>$stateId
        ]);
       }
       else{
        DB::table("user")
        ->where('user.id','=',$id)
        ->update([
            "flat"=>$flat,
            "street"=>$street,
            "locality"=>$locality,
            "city"=>$city,
            "zipcode"=>$pincode,
            "state"=>$stateId
          ]);        

       }
    }
}
