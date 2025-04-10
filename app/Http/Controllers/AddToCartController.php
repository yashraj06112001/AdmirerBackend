<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class AddToCartController extends Controller
{
    public function addToCart(Request $request)
    {
        $pid = $request->input('product_id');
        $cart = $request->input('cart'); // 1 = add, 0 = remove

        $token = $request->bearerToken();
        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized user!',
                'data' => null
            ], 401);
        }

        $userId = $accessToken->tokenable_id;

        if (!$pid || !in_array($cart, [0, 1])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid data!',
                'data' => null
            ], 400);
        }

        $existing = DB::table('add_cart')
            ->where('user_id', $userId)
            ->where('pid', $pid)
            ->first();

        if ($cart == 1) {
            if ($existing && $existing->status === 'Active') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Product already in cart!'
                ]);
            } elseif ($existing && $existing->status === 'Deleted') {
                DB::table('add_cart')
                    ->where('user_id', $userId)
                    ->where('pid', $pid)
                    ->update([
                        'status' => 'Active',
                        'datetime' => now()
                    ]);
            } else {
                DB::table('add_cart')->insert([
                    'user_id' => $userId,
                    'pid' => $pid,
                    'quantity' => 1,
                    'color_id' => null,
                    'groupby' => null,
                    'datetime' => now(),
                    'status' => 'Active',
                    'checked' => 0
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Product added to cart!'
            ]);
        } else {
            if ($existing && $existing->status === 'Active') {
                DB::table('add_cart')
                    ->where('user_id', $userId)
                    ->where('pid', $pid)
                    ->update([
                        'status' => 'Deleted',
                        'datetime' => now()
                    ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Product removed from cart!'
            ]);
        }
    }

    public function buyNow(Request $request)
    {
        $pid = $request->input('product_id');

        $token = $request->bearerToken();
        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized user!',
                'redirect' => 'no'
            ], 401);
        }

        $userId = $accessToken->tokenable_id;

        if (!$pid) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid data!',
                'redirect' => 'no'
            ], 400);
        }

        $existing = DB::table('add_cart')
            ->where('user_id', $userId)
            ->where('pid', $pid)
            ->first();

        if (!$existing) {
            DB::table('add_cart')->insert([
                'user_id' => $userId,
                'pid' => $pid,
                'quantity' => 1,
                'color_id' => null,
                'groupby' => null,
                'datetime' => now(),
                'status' => 'Active',
                'checked' => 1
            ]);
        } elseif ($existing->status === 'Deleted') {
            DB::table('add_cart')
                ->where('user_id', $userId)
                ->where('pid', $pid)
                ->update([
                    'status' => 'Active',
                    'datetime' => now()
                ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Product added to cart for buy now!',
            'redirect' => 'yes'
        ]);
    }
}
