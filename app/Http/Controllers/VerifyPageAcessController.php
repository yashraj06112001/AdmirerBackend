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
        $user=Auth::user();
        return response()->json([
       "message"=>"this is the data",
       "user id "=>$user->id,
       "user name"=>$user->firstname,
       "status"=>"201"
        ]);
    }
}
