<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
class signUp extends Controller
{
     public function signUpHandler(Request $request)
    {
        // Step 1: Validate incoming request
        $validator = Validator::make($request->all(), [
            'firstname' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'mobile' => 'nullable|string|max:255',
            'mobileverified' => 'nullable|in:Yes,No', // assuming only Yes/No
            'email' => 'nullable|email|max:255',
            'emailverified' => 'nullable|in:Yes,No', // assuming only Yes/No
            'gender' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }
        // Step 2: Check if mobile number already exists
        if ($request->filled('mobile')) {
            $existingUser = User::where('mobile', $request->input('mobile'))->first();

            if ($existingUser) {
                return response()->json([
                    'status' => 'already_registered',
                    'message' => 'Mobile number already registered.',
                    'data' => $existingUser
                ], 200);
            }
        }
        // Step 2: Create new user
        $user = new User();


        // Fill the columns from request or set to NULL if missing
        $user->firstname = $request->input('firstname', null);
        $user->lastname = $request->input('lastname', null);
        $user->mobile = $request->input('mobile', null);
        $user->mobileverified = $request->input('mobileverified', 'No'); // Default "No"
        $user->email = $request->input('email', null);
        $user->emailverified = $request->input('emailverified', 'No'); // Default "No"
        $user->gender = $request->input('gender', null);

        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'User registered successfully!',
            'data' => $user
        ], 201);
    }
}
