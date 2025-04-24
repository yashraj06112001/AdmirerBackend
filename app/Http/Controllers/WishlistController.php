<?php 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
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

    public function getUserWishlist(Request $request)
    {
        $userId = Auth::id();
    
        $wishlistProducts = DB::table('products as p')
        ->join('wishlist as wl', 'p.id', '=', 'wl.product_id')
        ->leftJoin('description as d', 'p.id', '=', 'd.p_id') 
        ->where('p.status', 'Active')
        ->where('p.trash', 'No')
        ->where('wl.status', 'Active')
        ->where('wl.user_id', $userId)
        ->select(
            'p.id',
            'p.product_name',
            'p.minimum',
            'p.maximum',
            'p.class0',
            'p.price',
            'p.discount',
            'p.product_code',
            'p.stock',
            'p.in_stock',
            'wl.id as wishlist_id',
            DB::raw("(SELECT i.image FROM image i WHERE i.p_id = p.product_code AND i.status = 'Active' ORDER BY i.set_seq ASC LIMIT 1) as image"),
            DB::raw("SUBSTRING_INDEX(d.description, ' ', 10) as short_description")
        )
        ->get();    
    
        // Add discount percentage and full image URL to each product
        $wishlistProducts = $wishlistProducts->map(function ($product) {
            $price = (float) $product->price;
            $actualPrice = (float) $product->discount;
    
            $discountPercent = 0;
            if ($price > 0 && $actualPrice > 0 && $actualPrice < $price) {
                $discountPercent = round((($price - $actualPrice) / $price) * 100);
            }
    
            $product->discount_percent = $discountPercent;
            $product->image_url = 'https://admirer.in/asset/image/product/' . $product->image;
          //  $product->image_url = asset('image/product/' . $product->image); // ✅ base URL added
    
            return $product;
        });
    
        return response()->json([
            'status' => 'success',
            'message' => 'Wishlist products fetched successfully!',
            'data' => $wishlistProducts
        ]);
    }
    
    public function removeFromWishlist(Request $request)
    {
        $userId = Auth::id();

        $wishlistId = $request->wishlist_id;

        if (!$wishlistId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Wishlist ID is required'
            ], 400);
        }

        $wishlist = DB::table('wishlist')
            ->where('id', $wishlistId)
            ->where('user_id', $userId)
            ->first();

        if (!$wishlist) {
            return response()->json([
                'status' => 'error',
                'message' => 'Wishlist item not found!'
            ], 404);
        }

        DB::table('wishlist')
            ->where('id', $wishlistId)
            ->update(['status' => 'Deleted']);

        return response()->json([
            'status' => 'success',
            'message' => 'Wishlist item removed successfully'
        ]);
    }

    public function moveToCart(Request $request)
    {
        $userId = Auth::id();
        $productId = $request->input('product_id');
    
        if (!$productId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product ID is required.',
            ]);
        }
    
        // Check if product already exists in cart
        $cartItem = DB::table('add_cart')
            ->where('user_id', $userId)
            ->where('pid', $productId)
            ->first();
    
        if ($cartItem) {
            // Update quantity by 1
            DB::table('add_cart')
                ->where('id', $cartItem->id)
                ->update([
                    'quantity' => $cartItem->quantity + 1,
                    'datetime' => date('Y-m-d H:i:s')
                ]);
        } else {
            // Insert new cart item
            DB::table('add_cart')->insert([
                'user_id' => $userId,
                'pid' => $productId,
                'quantity' => 1,
                'status' => 'Active',
                'datetime' => date('Y-m-d H:i:s')
            ]);
        }
    
        // Update wishlist status to Deleted
        DB::table('wishlist')
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->update([
                'status' => 'Deleted',
                'datetime' => date('Y-m-d H:i:s')
            ]);
    
        return response()->json([
            'status' => 'success',
            'message' => 'Product moved to cart successfully!',
        ]);
    }
    

}
