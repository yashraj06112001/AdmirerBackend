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
        $razorpayKey=config('razorpay.razorpay_key_id');
        $razorpaySecretKey=config('razorpay.razorpay_secret_key');
        $api = new Api($razorpayKey, $razorpaySecretKey);

        if (!empty($input['razorpay_payment_id'])) {
            try {
                $payment = $api->payment->fetch($input['razorpay_payment_id']);

                // Capture the payment with the amount passed from frontend or use payment['amount']
                $response = $payment->capture([
                    'amount' => $input['amount'] ?? $payment['amount'] // fallback if not passed
                ]);

                Session::put('success', 'Payment successful');
                return redirect()->back();
            } catch (Exception $e) {
                Session::put('error', $e->getMessage());
                return redirect()->back();
            }
        }

        Session::put('error', 'Invalid payment request');
        return redirect()->back();
    }

    // Handle creating a new Razorpay order
    public function createOrder(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);
        $razorpayKey=config('razorpay.razorpay_key_id');
        $razorpaySecretKey=config('razorpay.razorpay_secret_key');
        $api = new Api($razorpayKey,$razorpaySecretKey);

        $orderData = [
            'receipt'         => uniqid(),
            'amount'          => $validated['amount'] * 100, // converting to paise
            'currency'        => 'INR',
            'payment_capture' => 0
        ];

        try {
            $razorpayOrder = $api->order->create($orderData);

            return response()->json([
                'order_id' => $razorpayOrder['id'],
                'amount' => $orderData['amount'],
                'currency' => $orderData['currency']
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
