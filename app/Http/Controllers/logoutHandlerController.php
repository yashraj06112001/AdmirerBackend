<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class logoutHandlerController extends Controller
{
    //
    public function logout(Request $request)
{
    $request->user()->currentAccessToken()->delete();
    
    return response()->json([
        'message' => 'Successfully logged out from this device'
    ]);
}

public function logoutAll(Request $request)
{
    $request->user()->tokens()->delete();
    
    return response()->json([
        'message' => 'Successfully logged out from all devices'
    ]);
}
}
