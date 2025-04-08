<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;

class LoginHandler extends Controller
{
    //
    public function loginHandling(Request $request)
    {
        // Validate incoming request
    $validated = $request->validate([
        'mobileNumber' => 'required|digits:10',   // Example: 10 digits mobile
        'mobileNumberVerified' => 'required|boolean',
    ]);
       $mobileNumber=$request->mobileNumber;
       $mobileNumberVerified=$request->mobileNumberVerified;
       $existingUser = User::where('mobile',$mobileNumber)->first();
       if($existingUser)
       {
        if($mobileNumberVerified)
        {
            $token = $existingUser->createToken('api-token',['*'], now()->addMinutes(60))->plainTextToken;
            return response()->json(['token' => $token]);
        }
        else{
            return response()->json(['message' => 'User Exists but OTP is Wrong'], 404);
        }
       }
       else {
        // Optional: handle case when user not found
        return response()->json(['message' => 'User not found'], 404);
    }
}
}
