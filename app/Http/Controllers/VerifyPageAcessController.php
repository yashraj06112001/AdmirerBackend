<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class VerifyPageAcessController extends Controller
{
    //
    public function verify(Request $request)
    {
        // Try to authenticate the user using the current token
        $user = Auth::user();

        if ($user) {
            return response()->json([
                'message' => 'Token is valid',
                'user' => $user
            ], 200);
        } else {
            return response()->json([
                'message' => 'Invalid or expired token'
            ], 401);
        }
    }
}
