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
   
    public function profileUpdate(Request $request)
    {
        $userId = Auth::id(); // Get the authenticated user's ID

        if (!$userId) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }
    
        // Define the update data (fallback to existing values if not provided)
        $updateData = [
            'firstname'     => $request->firstName ?? Auth::user()->firstname,
            'lastname'      => $request->lastName ?? Auth::user()->lastname,
            'mobile'        => $request->mobile ?? Auth::user()->mobile,
            'email'         => $request->email ?? Auth::user()->email,
            'flat'          => $request->flat ?? Auth::user()->flat,
            'street'        => $request->street ?? Auth::user()->street,
            'locality'      => $request->locality ?? Auth::user()->locality,
            'city'          => $request->city ?? Auth::user()->city,
            'zipcode'       => $request->zipcode ?? Auth::user()->zipcode,
            'state'         => $request->state ?? Auth::user()->state,
            'country'       => $request->country ?? Auth::user()->country,
            'address_type'  => $request->address_type ?? Auth::user()->address_type,
        ];
    
        // Perform the update using Query Builder
        DB::table('user') // or 'users' (match your actual table name)
            ->where('id', $userId)
            ->update($updateData);
    
        return response()->json(['success' => true, 'message' => 'Profile updated successfully']);
    

    }

    public function AllOrderStatus()
    {
        $user=Auth::user();
        $id=$user->id;
           // Subquery to get the latest status per order_id
    $latestStatusSubquery = DB::table('order_status as os1')
    ->select('os1.order_id', 'os1.tracking_status')
    ->join(DB::raw('(SELECT order_id, MAX(id) as max_id FROM order_status GROUP BY order_id) as latest'), function($join) {
        $join->on('os1.order_id', '=', 'latest.order_id')
             ->on('os1.id', '=', 'latest.max_id');
    });

$result = DB::table('order_details')
    ->leftJoinSub($latestStatusSubquery, 'latest_status', function ($join) {
        $join->on('order_details.order_id', '=', 'latest_status.order_id');
    })
    ->leftJoin('user', 'user.id', '=', 'order_details.user_id') // use 'users' if your table is plural
    ->select(
        'order_details.productname as product_name',
        'order_details.price as product_price',
        'order_details.order_id as order_id',
        'order_details.payment_status as payment',
        'order_details.productimage as image',
        'order_details.date as order_Date',
        'order_details.time as order_Time',
        'user.firstname as first_name',
        'user.lastname as last_name',
    )
    ->where('order_details.user_id', '=', $id)
    ->orderBy('order_details.date', 'desc')
    ->orderBy('order_details.time', 'desc')
    ->get();

return response()->json([
    "data" => $result,
]);
    }
   
public function orderStatus(Request $request)
{
    $user = Auth::user();
    
    // First get all distinct order_ids for the user
    $orderIds = DB::table('order_status')
        ->select('order_id')
        ->where('user_id', $user->id)
        ->groupBy('order_id')
        ->pluck('order_id');
    
    $result = [];
    
    foreach ($orderIds as $orderId) {
        // Get all tracking_status records for this order_id, ordered by creation time
        $statuses = DB::table('order_status')
            ->select('tracking_status')
            ->where('order_id', $orderId)
            ->orderBy('date', 'asc') // assuming you have a created_at column
            ->pluck('tracking_status')
            ->toArray();
        
        $result[$orderId] = $statuses;
    }
    
    return response()->json([
        "data" => $result
    ]);
}


}
