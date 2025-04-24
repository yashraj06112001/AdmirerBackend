<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class placeOrderFlowController extends Controller
{
    // here we will handle all the functions for placing order after candidate place his order
    private $nimbusToken = null;
    private $tokenExpiryTime = null;
    const NIMBUS_TOKEN_EXPIRY = 3600; // Token expiry in seconds (1 hour)
    public function getProductsOfOrderId($userId)
    {
       $products=DB::table('add_cart as ac')
       ->leftJoin('products as p','p.id','=','ac.pid')
       ->leftJoin('image as img', 'img.p_id','=','p.id')
       ->select('p.id as id','p.product_name','ac.quantity as quantity','img.image as img','p.discount as discount')
       ->where('ac.user_id','=',$userId)
       ->where('ac.status','=','Active')
       ->get()
       ->toArray();
       return $products;
    }
    public function createGST(int $number)
    {
        $gst = $number * 0.18;
        return $gst;
    }
    protected function getNimbusToken()
{
    if ($this->nimbusToken && time() < $this->tokenExpiryTime) {
        return $this->nimbusToken;
    }
    
    try {
        $response = Http::post("https://api.nimbuspost.com/v1/users/login", [
            'email' => config('nimbusDelievery.email'),
            'password' => config('nimbusDelievery.password')
        ]);
        
        if ($response->successful()) {
            $data = $response->json();
            if ($data['status'] && !empty($data['data'])) {
                $this->nimbusToken = $data['data'];
                $this->tokenExpiryTime = time() + self::NIMBUS_TOKEN_EXPIRY;
                return $this->nimbusToken;
            }
        }
        
        Log::error('Failed to get NimbusPost token', ['response' => $response->body()]);
        return null;
        
    } catch (\Exception $e) {
        Log::error('NimbusPost token request exception', ['error' => $e->getMessage()]);
        return null;
    }
}
    public function createOrder(Request $request)
    {
    $id=Auth::user()->id;
    $phone=Auth::user()->mobile;
    $fname=$request->firstName;
    $lname=$request->lastName;
    $flat=$request->flat;
    $street=$request->street;
    $locality=$request->locality;
    $state=$request->state;
    $city=$request->city;
    $orderID= $request->get('orderID');

    $country=99;
    $paymentType=$request->paymentType;
    $products=$this->getProductsOfOrderId($id);
    $amount=$request->amount;
    $pincode=$request->pincode;
    $gst=$this->createGST($amount);
    DB::beginTransaction();
    foreach($products as $product)
    {   

        //Added data inside table order_Details
        $addDataInTableOrderDetails=DB::table("order_details")->insert([
            "user_id"=>$id,
            "order_id"=>$orderID,
            "productid"=>$product->id,
            "gst"=>$this->createGST($product->discount),
            "price"=>$product->discount,
            "quantity"=>$product->quantity,
            "payment_type"=>$paymentType,
            "time"=>now()->toTimeString(),
            "date"=> now()->toDateString(),
            "timestamp"=>  now(),
            "productname"=>$product->product_name,
            "productImage"=>$product->img
           ]);
           // update stock value
           $updatingStockQuantity = DB::table('stock')
           ->where('p_id', '=', $product->id) // 👍 put WHERE first
           ->update([
               'stock' => DB::raw("stock - {$product->quantity}")
           ]);
    }
        // Added data inside table order_tbl
        $addDataInTableOrderTbl=DB::table('order_tbl')->insert([
            'userid'=>$id,
           'orderprice'=>$amount,
           'payment_type'=>$paymentType,
           'order_id'=>$orderID,
           'date'=>now()->toDateString(),
           'time'=>now()->toTimeString(),
           'gst' => $gst,
           'payment_mode'=>$paymentType
           ]);
           $stateId = DB::table('state_list')
          ->where('state', $state)
          ->value('id');

           //Added data inside shipping_Address
           $addDataInTableShippingAddress=DB::table('shiping_address')->insert([
            'order_id'=>$orderID,
            'user_id'=>$id,
            'first_name'=>$fname,
            'last_name'=>$lname,
            'flat'=>$flat,
            'street'=>$street,
            'locality'=>$locality,
            'country'=>$country, 
            'state'=>$stateId,
            'city'=>$city,
           ]);
           
          //NIMBUS API INTEGRATION
          $consignee = [
            'name' => $fname . ' ' . $lname,
            'address' => $flat . ', ' . $street,
            'address_2' => $locality,
            'city' => $city,
            'state' => $state,
            'pincode' => $pincode,
            'phone' => $phone,
        ];
    
        $pickup = [
            'warehouse_name' => config('nimbusDelievery.pickup.warehouse_name'),
            'name' => config('nimbusDelievery.pickup.name'),
            'address' => config('nimbusDelievery.pickup.address'),
            'address_2' => config('nimbusDelievery.pickup.address_2'),
            'city' => config('nimbusDelievery.pickup.city'),
            'state' => config('nimbusDelievery.pickup.state'),
            'pincode' => config('nimbusDelievery.pickup.pincode'),
            'phone' => config('nimbusDelievery.pickup.phone'),
        ];
    
        $orderItems = [];
        foreach ($products as $product) {
            $orderItems[] = [
                'name' => $product->product_name,
                'qty' => $product->quantity,
            ];
        }
    
        $payload = [
            'order_number' => $orderID,
            'payment_type' => $paymentType,
            'order_amount' => $amount,
            'consignee' => $consignee,
            'pickup' => $pickup,
            'order_items' => $orderItems,
            'request_auto_pickup' => 'yes',
            'is_insurance' => '0', // Default no insurance
            'tags' => 'online_order' // Default tag
        ];
        $token = $this->getNimbusToken();
if (!$token) {
    throw new \Exception('Failed to authenticate with NimbusPost');
}
Log::debug('NimbusPost Request Payload:', $payload);
Log::debug('NimbusPost Auth Token:', [$token]);
        // Send API request
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
           'Authorization' => 'Bearer ' . $token,
        ])->post('https://api.nimbuspost.com/v1/shipments', $payload);
    
        // Handle response
        if ($response->successful()) {
            DB::commit();
            // Success handling
            Log::info('NimbusPost API Success:', $response->json());
            logger()->info('NimbusPost API Success:', $response->json());
        } else {
            DB::rollBack();
            // Error handling
            Log::error('NimbusPost API Error:', [
                'status_code' => $response->status(),
                'response' => $response->json(),
                'order_id' => $orderID,
                'user_id' => $id
            ]);
            logger()->error('NimbusPost API Error:', $response->json());
        }
       }
    }