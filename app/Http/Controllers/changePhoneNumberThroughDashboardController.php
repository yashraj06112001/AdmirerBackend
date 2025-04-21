<?php

namespace App\Http\Controllers;
use App\Services\NimbusSmsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class changePhoneNumberThroughDashboardController extends Controller
{
    //
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|digits:10'
        ]);

        // Generate OTP
        $otp = rand(100000, 999999); // 6-digit OTP
        
        // Store OTP for verification (5 minutes expiry)
        Cache::put('otp_'.$request->phone, $otp, 300);

        // Send OTP via SMS
        $smsService = new NimbusSmsService();
        $success = $smsService->sendOtp($request->phone, $otp);

        return $success 
            ? response()->json(['message' => 'OTP sent!']) 
            : response()->json(['message' => 'Failed to send OTP'], 500);
    }
    public function verifyOtp(Request $request)
    {
        $id=Auth::user()->id;
        $request->validate([
            'phone' => 'required|digits:10',
            'otp' => 'required|digits:6'
        ]);

        // Verify OTP
        $storedOtp = Cache::get('otp_'.$request->phone);
        
        if ($request->otp == $storedOtp) {
            Cache::forget('otp_'.$request->phone);
            DB::table('user')
            ->where('id', $id)
            ->update(['mobile' => $request->phone]);

        return response()->json(['message' => 'Phone number updated successfully']);             
        }

        return response()->json(['message' => 'Invalid OTP'], 401);
    }
}
