<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NimbusSmsService
{
    public function sendOtp($mobileNumber, $otp)
    {
        // Construct message
        $message = "Use the OTP {$otp} to verify your contact number and login to ADMIRER. Do not share it with anyone. -ADMIRER";
        $encodedMessage = urlencode($message);

        // Prepare request parameters
        $params = [
            'UserID'     => config('sms.nimbus.user_id'),
            'Password'   => config('sms.nimbus.password'),
            'SenderID'   => config('sms.nimbus.sender_id'),
            'Phno'       => $mobileNumber,
            'Msg'        => $encodedMessage,
            'EntityID'   => config('sms.nimbus.entity_id'),
            'TemplateID' => config('sms.nimbus.template_id'),
        ];

        // Log the request being sent
        Log::debug('Sending OTP via Nimbus API', [
            'mobile' => $mobileNumber,
            'params' => array_merge($params, ['Password' => '****']), // Mask password
            'raw_message' => $message
        ]);

        try {
            // Send request
            $response = Http::timeout(30)->get('http://nimbusit.biz/api/SmsApi/SendSingleApi', $params);

            // Detailed logging
            Log::debug('Nimbus SMS API Response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'successful' => $response->successful()
            ]);

            if ($response->successful()) {
                $status = $response->object();
                $isSuccess = isset($status->Status) && $status->Status === 'OK';
                
                if (!$isSuccess) {
                    Log::error('Nimbus API returned unsuccessful status', [
                        'response' => $status,
                        'mobile' => $mobileNumber
                    ]);
                }
                
                return $isSuccess;
            }

            Log::error('Nimbus API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'mobile' => $mobileNumber
            ]);
            
            return false;
            
        } catch (\Exception $e) {
            Log::error('Exception in Nimbus SMS Service', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'mobile' => $mobileNumber
            ]);
            
            return false;
        }
    }
}