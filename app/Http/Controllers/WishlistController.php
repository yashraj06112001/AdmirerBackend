<?php 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class WishlistController extends Controller
{
    public function toggleWishlist(Request $request)
    {
        $productId = $request->input('product_id');
        $wishlist = $request->input('wishlist'); // 1 = add, 0 = remove

        // Token Auth check
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

        if (!$productId || !in_array($wishlist, [0, 1])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid data!',
                'data' => null
            ], 400);
        }

        // Check existing entry
        $existing = DB::table('wishlist')
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        if ($wishlist == 1) {
            if ($existing) {
                if ($existing->status === 'Active') {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Product already in wishlist!'
                    ]);
                } else {
                    DB::table('wishlist')
                        ->where('user_id', $userId)
                        ->where('product_id', $productId)
                        ->update([
                            'status' => 'Active',
                            'datetime' => now()
                        ]);
                }
            } else {
                DB::table('wishlist')->insert([
                    'user_id' => $userId,
                    'product_id' => $productId,
                    'status' => 'Active',
                    'datetime' => now()
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Product added to wishlist!'
            ]);
        } else {
            if ($existing && $existing->status === 'Active') {
                DB::table('wishlist')
                    ->where('user_id', $userId)
                    ->where('product_id', $productId)
                    ->update([
                        'status' => 'Deleted',
                        'datetime' => now()
                    ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Product removed from wishlist!'
            ]);
        }
    }
}
