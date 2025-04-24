<?php 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class ProductDetailsController extends Controller
{
    public function getProductDetails(Request $request ,$id)
    {
        $product = DB::table('products as p')
            ->leftJoin('description as d', function ($join) {
                $join->on('p.id', '=', 'd.p_id')
                     ->where('d.status', 'Active');
            })
            ->leftJoin('category as c', 'p.cat_id', '=', 'c.id')
            ->select(
                'p.*',
                'd.description',
                'c.cat_name',
                'c.issubcategory'
            )
            ->where('p.id', $id)
            ->first();
    
        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found!',
                'data' => null
            ]);
        }

         // Check user via token manually (optional auth)
        $token = $request->bearerToken();
        $userId = null;

        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken) {
                $userId = $accessToken->tokenable_id;
            }
        }

        $isWishlisted = 0;
            if ($userId) {
                $wishlist = DB::table('wishlist')
                            ->where('product_id', $id)
                            ->where('user_id', $userId)
                            ->select('status')
                            ->first();

                if ($wishlist) {
                    $isWishlisted = ($wishlist->status == 'Active') ? 1 : 0;
                }
            }

        $isInCart = 0;

        if ($userId) {
            $inCart = DB::table('add_cart')
                ->where('pid', $id)
                ->where('user_id', $userId)
                ->where('status', 'Active')
                ->exists();
            $isInCart = $inCart ? 1 : 0;
        }

        $productData = (array) $product;

        // Calculate discount percent
        $price = (float) $product->price;         // Main price
        $actualPrice = (float) $product->discount; // Actual price after discount

        $discountPercent = 0;
        if ($price > 0 && $actualPrice > 0 && $actualPrice < $price) {
            $discountPercent = round((($price - $actualPrice) / $price) * 100);
        }


        // Add to product data
        $productData['discount_percent'] = $discountPercent;

        $images = DB::table('image')
            ->where('p_id', '=',$product->product_code)
            ->where('status', 'Active')
            ->get()
            ->map(function ($img) {
                return [
                    'id' => $img->id,
                    'image' =>$img->image
                ];
            });

        $productData['images'] = $images;
        $productData['wishlist'] = $isWishlisted;
        $productData['in_cart'] = $isInCart;

              // Related products
              $relatedProducts = DB::table('products as p')
              ->leftJoin('description as d', function ($join) {
                  $join->on('p.id', '=', 'd.p_id')
                       ->where('d.status', 'Active');
              })
              ->where('p.cat_id', $product->cat_id)
              ->where('p.id', '!=', $product->id)
              ->where('p.status', 'Active')
              ->select('p.id', 'p.product_code', 'p.product_name', 'p.price', 'p.discount', 'd.description')
              ->limit(10)
              ->get()
              ->map(function ($prod) use ($userId) {
                  // First image by seq
                $img = DB::table('image')
                    ->where('p_id', $prod->product_code)
                    ->where('status', 'Active')
                    ->orderBy('set_seq', 'asc')
                    ->value('image');

                $imageUrl = $img ? $img : null;
  
                    $price = (float) $prod->price;         // Main price
                    $actualPrice = (float) $prod->discount; // Actual price (after discount)
                    $discountPercent = 0;
                    
                    if ($price > 0 && $actualPrice > 0 && $actualPrice < $price) {
                        $discountPercent = round((($price - $actualPrice) / $price) * 100);
                    }
                    
  
                  // Wishlist check
                  $isWishlisted = 0;
                    if ($userId) {
                        $wishlist = DB::table('wishlist')
                                    ->where('product_id', $prod->id)
                                    ->where('user_id', $userId)
                                    ->select('status')
                                    ->first();

                        if ($wishlist) {
                            $isWishlisted = ($wishlist->status == 'Active') ? 1 : 0;
                        }
                    }
  
                  return [
                      'id' => $prod->id,
                      'title' => $prod->product_name,
                      'price' => $prod->price,
                      'discount' => $prod->discount,
                      'discount_percent' => $discountPercent,
                      'image' => $imageUrl,
                      'description' => implode(' ', array_slice(explode(' ', strip_tags($prod->description ?? '')), 0, 10)),
                      'wishlist' => $isWishlisted,
                  ];
              });

        return response()->json([
            'status' => 'success',
            'message' => 'Product details fetched successfully!',
            'data' => $productData,
            'related_products' => $relatedProducts
        ]);
    }


}
