<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
class signUp extends Controller
{
     public function signUpHandler(Request $request)
    {   $user=Auth::user();
        $id = Auth::user()->id;

// Step 1: Validate incoming request
$validator = Validator::make($request->all(), [
    'firstname' => 'required|string|max:255',
    'lastname'  => 'required|string|max:255',
    'email'     => 'nullable|email|max:255', // <-- changed from required to nullable
    'flat'      => 'required|string|max:255',
    'street'    => 'required|string|max:255',
    'locality'  => 'required|string|max:255',
    'city'      => 'required|string|max:255',
    'state'     => 'required|string|max:255',
    'pincode'   => 'required|digits:6',
    "addressType"=> 'required', 
]);

// Step 2: Return errors if validation fails
if ($validator->fails()) {
    return response()->json(['errors' => $validator->errors()], 422);
}

// Step 3: Extract validated data
$validated = $validator->validated();

// Step 4: Get state ID
$stateId = DB::table('state_list')
            ->where('state', $validated['state'])
            ->value('id');

if (!$stateId) {
    return response()->json(['error' => 'Invalid state provided.'], 400);
}

// Step 5: Check if user's flat address exists
$addressFlat = DB::table('user')
                ->where('id', $id)
                ->value('flat');

if ($addressFlat) {
    $existingAddress = DB::table('user_shipping_addresses')
        ->where('user_id', '=',$id)
        ->where('flat', '=',$validated['flat'])
        ->where('street', '=',$validated['street'])
        ->where('locality', '=',$validated['locality'])
        ->where('city', '=',$validated['city'])
        ->where('zip_code', '=',$validated['pincode'])
        ->where('state', '=',$stateId)
        ->first();

    if ($existingAddress) {
        return response()->json(['error' => 'This address already exists.'], 409);
    }
    // Step 6: Insert into shipping addresses
    DB::table('user_shipping_addresses')->insert([
        'user_id'    => $id,
        'first_name' => $validated['firstname'],
        'last_name'  => $validated['lastname'],
        'email'      => $validated['email'] ?? null, // handle optional email
        'flat'       => $validated['flat'],
        'street'     => $validated['street'],
        'locality'   => $validated['locality'],
        'city'       => $validated['city'],
        'zip_code'    => $validated['pincode'],
        'state'      => $stateId,
        'addr_type' => $validated['addressType']
        ]);
} else {
    // Step 7: Update user's address directly
    $firstname=$user->firstname;
    $lastname=$user->lastname;
    if(!$firstname && !$lastname)
    {
        DB::table('user')
        ->where('id', $id)
        ->update([
            'firstname'=>$validated['firstname'],
            'lastname'=>$validated['lastname'],
            'flat'     => $validated['flat'],
            'street'   => $validated['street'],
            'locality' => $validated['locality'],
            'city'     => $validated['city'],
            'zipcode'  => $validated['pincode'],
            'state'    => $stateId,
            'addr_type'=>$validated['addressType']
        ]); 
    }
    else{
        DB::table('user')
        ->where('id', $id)
        ->update([
            'flat'     => $validated['flat'],
            'street'   => $validated['street'],
            'locality' => $validated['locality'],
            'city'     => $validated['city'],
            'zipcode'  => $validated['pincode'],
            'state'    => $stateId,
        ]);
    }
  
}

return response()->json(['message' => 'Address updated successfully.',
"address flat"=>$addressFlat]);

    }
}
