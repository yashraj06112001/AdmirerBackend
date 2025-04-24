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
       $order_id=$request->id;
      $address=DB::table('order_details as od')
    ->leftJoin('shiping_address as sa','od.order_id','=','sa.order_id')
    ->leftJoin('state_list','state_list.id','=','sa.state')
    ->leftJoin('countries','countries.id','=','sa.country')
     ->select('sa.first_name','sa.last_name','sa.flat','sa.street','sa.city','sa.locality','state_list.state','countries.country_name','sa.zip_code','sa.addr_type','sa.phone')
     ->where('sa.order_id','=',$order_id)
     ->first();
  
    return response()->json([
        "data"=>$address,
        "order_id"=>$order_id
    ]);

    }
}
