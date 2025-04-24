<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

use Exception;

class razorPayController extends Controller
{
    //
    public function store(Request $request)
    {
        $input = $request->all();
        Log::info('Razorpay payment input received', ['input' => $input]);
    
        $razorpayKey = config('razorpay.razorpay_key_id');
        $razorpaySecretKey = config('razorpay.razorpay_secret_key');
        $api = new Api($razorpayKey, $razorpaySecretKey);
        Log::info('Initialized Razorpay API');
    
        if (!empty($input['razorpay_payment_id'])) {
            try {
                // Step 1: Verify Signature
                $attributes = [
                    'razorpay_order_id'   => $input['razorpay_order_id'],
                    'razorpay_payment_id' => $input['razorpay_payment_id'],
                    'razorpay_signature'  => $input['razorpay_signature']
                ];
                Log::info('Verifying payment signature', ['attributes' => $attributes]);
    
                $api->utility->verifyPaymentSignature($attributes);
                Log::info('Signature verified successfully');
    
                // Step 2: Fetch the payment
                $payment = $api->payment->fetch($input['razorpay_payment_id']);
                Log::info('Fetched payment details from Razorpay', ['payment' => $payment->toArray()]);
    
                // Step 3: Check if already captured
                if ($payment['status'] !== 'captured') {
                    $captureAmount = $payment['amount'];
                    $payment->capture(['amount' => $captureAmount]);
                    Log::info('Captured payment', ['amount' => $captureAmount]);
                } else {
                    Log::info('Payment already captured');
                }
    
                Session::put('success', 'Payment verified and successful.');
                Log::info('Payment process completed successfully');
                return redirect()->back();
    
            } catch (\Razorpay\Api\Errors\SignatureVerificationError $e) {
                Log::error('Signature verification failed', ['exception' => $e->getMessage()]);
                Session::put('error', 'Payment signature verification failed.');
                return redirect()->back();
            } catch (\Exception $e) {
                Log::error('Payment processing failed', ['exception' => $e->getMessage()]);
                Session::put('error', $e->getMessage());
                return redirect()->back();
            }
        }
    
        Log::warning('Invalid payment request - razorpay_payment_id not found');
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
