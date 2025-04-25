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

        $state=DB::table('state_list')->select('state_list.id')->where('state_list.state','=',$request->state)->first();
    
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
            'state'         => $state->id ?? Auth::user()->state,
            'country'       => $request->country ?? Auth::user()->country,
            'addr_type'  => $request->address_type ?? Auth::user()->addr_type,
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
        'order_details.quantity as quantity',
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
            ->where('order_id', '=',$orderId)
            ->orderBy('date', 'asc') // assuming you have a created_at column
            ->pluck('tracking_status')
            ->toArray();
        
        $result[$orderId] = $statuses;
    }
    
    return response()->json([
        "data" => $result
    ]);
}

public function recentOrder(Request $request)
{
    $user = Auth::user();
    
    // First get all distinct order IDs for the user
    $orderIds = DB::table('order_details')
        ->select('order_id')
        ->where('user_id', $user->id)
        ->groupBy('order_id')
        ->pluck('order_id');
    
    // Then get complete order details for each order ID
    $orders = [];
    
    foreach ($orderIds as $orderId) {
        $orderDetails = DB::table('order_details as od')
            ->leftJoin('products as p', 'od.productid', '=', 'p.id')
            ->leftJoin('description as d','p.id','=','d.p_id')
            ->leftJoin(DB::raw('(SELECT i1.* FROM image i1 
                              WHERE i1.id = (SELECT MIN(i2.id) FROM image i2 WHERE i2.p_id = i1.p_id)
                             ) as img'), 
                function($join) {
                    $join->on('p.product_code', '=', 'img.p_id');
                })
            ->where('od.order_id', '=',$orderId)
            ->select([
                'od.*', // Select all columns from order_details
                'p.product_name as product_name', // Assuming 'products' has 'name' column
                'd.description as product_description', // Add other product fields if needed
                'img.image as product_image' 
            ])
            ->get()
            ->map(function ($item) {
                // Format the data as needed
                return [
                    'product_id' => $item->productid,
                    'product_name' => $item->product_name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'order_status' => $item->order_status,
                    'payment_status' => $item->payment_status,
                    'date' => $item->date,
                    'time' => $item->time,
                    'product_image' => $item->product_image,
                    
                    // Add any other fields you need
                ];
            });
        
        $orders[$orderId] = $orderDetails;
    }
    
    return response()->json([
        'status' => 'success',
        'orders' => $orders,
    ]);
}

public function orderDetail(Request $request)
{
   $id=$request->id;
   $result=DB::table('order_details as od')
   ->leftJoin('products as p','od.productid','=','p.id')
   ->leftJoin('description as des','p.id','=','des.p_id')
   ->leftJoin(DB::raw('(SELECT MIN(id) as min_id, p_id FROM image GROUP BY p_id) as first_img'), 
   function($join) {
       $join->on('first_img.p_id', '=', 'p.product_code');
   })
->leftJoin('image as img', function($join) {
   $join->on('img.id', '=', 'first_img.min_id')
        ->on('img.p_id', '=', 'first_img.p_id');
})
   ->select('od.price','od.order_id','od.quantity','od.payment_type','od.date','od.time','p.product_name','des.description','img.image')
   ->where('od.order_id','=',$id)
   ->get();

    // Clean up HTML and unwanted characters
    $cleaned = $result->map(function ($item) {
        $desc = strip_tags($item->description); // Remove HTML tags
        $desc = html_entity_decode($desc); // Decode HTML entities like &nbsp;
        $desc = preg_replace('/[\r\n]+/', ' ', $desc); // Remove newlines
        $desc = trim($desc); // Clean start/end whitespace
        $item->description = $desc;
        return $item;
    });
    $trackingStatus = DB::table('order_status')
    ->where('order_id', $id)
    ->value('tracking_status'); // gets first value directly

    return response()->json([
        "data" => $cleaned,
        "tracking_status"=>$trackingStatus
    ]);
}

}
