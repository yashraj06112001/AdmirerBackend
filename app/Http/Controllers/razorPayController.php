<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\Session;

use Exception;

class razorPayController extends Controller
{
    //
    public function store(Request $request)
    {
        $input = $request->all();
  
        $api = new Api("PLAESE_PASTE_YOUR_PRIVATE_KEY_HERE", "PASTE_YOUR_SECRET_KEY_HERE");
  
        $payment = $api->payment->fetch($input['razorpay_payment_id']);
  
        if(count($input)  && !empty($input['razorpay_payment_id'])) {
            try {
                $response = $api->payment->fetch($input['razorpay_payment_id'])->capture(array('amount'=>$payment['amount'])); 
  
            } catch (Exception $e) {
                Session::put('error', $e->getMessage());
                return redirect()->back();
            }
        }
          
        Session::put('success', 'Payment successful');
        return redirect()->back();
    }
    public function createOrder(Request $request)
{
    $api = new Api("PLAESE_PASTE_YOUR_PRIVATE_KEY_HERE", "PASTE_YOUR_SECRET_KEY_HERE");

    $orderData = [
        'receipt'         => uniqid(),
        'amount'          => 50000, // Amount in paise (i.e. ₹500.00)
        'currency'        => 'INR',
        'payment_capture' => 0 // 0 = manual capture, 1 = auto
    ];

    $razorpayOrder = $api->order->create($orderData);

    return response()->json([
        'order_id' => $razorpayOrder['id'],
        'amount' => $orderData['amount'],
        'currency' => $orderData['currency']
    ]);
}

}
