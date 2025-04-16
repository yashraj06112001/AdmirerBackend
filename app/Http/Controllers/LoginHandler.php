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

    $mobileNumber = $request->mobileNumber;
    $mobileNumberVerified = filter_var($request->mobileNumberVerified, FILTER_VALIDATE_BOOLEAN);

    $existingUser = User::where('mobile', '=', $mobileNumber)->first();

    if ($existingUser) {
        if ($mobileNumberVerified) {
            $token = $existingUser->createToken('api-token', ['*'], now()->addMinutes(300))->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => $existingUser,
                'mobileNumber' => $mobileNumber
            ]);
        } else {
            return response()->json(['message' => 'User exists but OTP is wrong'], 400);
        }
    } else {
        return response()->json(['message' => 'User not found'], 404);
    }
}
}
