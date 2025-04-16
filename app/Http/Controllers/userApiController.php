<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class userApiController extends Controller
{
    //
    public function getAddress(Request $request)
    {
       $userID=Auth::user()->id;
       $Address=DB::table('user as u')
       ->leftJoin('user_shipping_addresses as sa','sa.user_id','=','u.id')
       ->leftJoin('state_list as state','state.id','=','u.state')
       ->leftJoin("countries as coun",'coun.id','=','state.country_id')
       ->select('u.firstname','u.lastname','sa.flat','sa.street','sa.locality','sa.city','sa.zip_code','coun.country_name','state.state')
       ->where('u.id','=',$userID)
       ->get();
       return response()->json([
        "data"=>$Address
       ]);


    }
}
