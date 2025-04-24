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
        Log::info('Incoming Razorpay payment request', ['input' => $request->all()]);
    
        $validated = $request->validate([
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id'   => 'required|string',
            'razorpay_signature'  => 'required|string',
        ]);
    
        try {
            $api = new Api(
                config('razorpay.razorpay_key_id'),
                config('razorpay.razorpay_secret_key')
            );
            Log::info('Razorpay API initialized');
    
            $api->utility->verifyPaymentSignature($validated);
            Log::info('Payment signature verified', ['validated' => $validated]);
    
            $payment = $api->payment->fetch($validated['razorpay_payment_id']);
            Log::info('Payment fetched from Razorpay', ['payment' => $payment->toArray()]);
    
            if ($payment['status'] !== 'captured') {
                $payment->capture(['amount' => $payment['amount']]);
                Log::info('Payment captured successfully', ['amount' => $payment['amount']]);
            } else {
                Log::info('Payment already captured');
            }
    
            Log::info('Payment process completed successfully');
    
            return response()->json([
                'status' => 'success',
                'message' => 'Payment verified and completed successfully.',
                'payment_id' => $payment['id'],
                'order_id' => $validated['razorpay_order_id'],
            ]);
    
        } catch (\Razorpay\Api\Errors\SignatureVerificationError $e) {
            Log::error('Signature verification failed', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Payment signature verification failed.'
            ], 422);
        } catch (\Exception $e) {
            Log::error('Unexpected error during payment processing', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Payment failed. Please try again.',
            ], 500);
        }
    }    // Handle creating a new Razorpay order
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
