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
   

    // public function AllOrderStatus()
    // {
    //   $user=Auth::user();
    //   $id=$user->id;      
    //   $results = DB::table('order_details')
    // ->leftJoin('products', 'order_details.productid', '=', 'products.id')
    // ->leftJoin('order_status', 'order_details.order_id', '=', 'order_status.order_id')
    // ->select(
    //     'order_details.order_id',
    //     'order_details.id as id',
    //     'order_details.price as order_price',
    //     'order_details.quantity as number_of_products',
    //     'products.product_name as product_name',
    //     'products.discount as product_price',
    //     'order_status.tracking_status',
    //     'order_status.date as date',
    //     'order_status.time as time'
    // ) ->where("order_details.user_id",'=',$id)
    // ->get();
    //  return response()->json([
    //     "data"=>$results,
    //  ]);
     

    // }

    public function AllOrderStatus()
    {
        $user=Auth::user();
        $id=$user->id;
        $result = DB::table('order_details')
        ->leftJoin('order_status', 'order_details.order_id', '=', 'order_status.order_id')
        ->leftJoin('user', 'user.id', '=', 'order_details.user_id') // use 'users' if your table is plural
        ->select(
            'order_details.productname as Product name',
            'order_details.price as Product Price',
            'order_details.order_id as order ID',
            'order_details.payment_status as Payment',
            'order_details.productimage as image',
            'order_details.date as Order Date',
            'order_details.time as Order Time',
            'user.firstname as first name',
            'user.lastname as last name',
            'order_status.tracking_status as status'
        )
        ->where('order_details.user_id', '=', $id)
        ->orderBy('order_details.date', 'desc')
        ->orderBy('order_details.time', 'desc')
        ->get();

        return response()->json([
            "data"=>$result,
        ]);
    }

}
