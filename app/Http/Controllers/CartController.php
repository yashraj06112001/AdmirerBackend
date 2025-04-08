<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\AddCart;

class CartController extends Controller
{
    public function getCartProducts(Request $request)
    {
        $userId = $request->user_id;

        // Fetch products along with their relationships
        $products = Product::with(['cart', 'image', 'sizeClass'])
            ->where('status', 'Active')
            ->whereHas('cart', function($q) use ($userId) {
                $q->where('status', 'Active')
                  ->where('user_id', $userId);
            })
            ->get()
            ->map(function($product) {
                // map each product to required structure including cart checked field
                return [
                    'id'                 => $product->id,
                    'cat_id'             => $product->cat_id,
                    'subcat_id'          => $product->subcat_id,
                    'product_name'       => $product->product_name,
                    'minimum'            => $product->minimum,
                    'maximum'            => $product->maximum,
                    'class0'             => $product->class0,
                    'class1'             => $product->class1,
                    'class2'             => $product->class2,
                    'class3'             => $product->class3,
                    'price'              => $product->price,
                    'discount'           => $product->discount,
                    'cod'                => $product->cod,
                    'product_code'       => $product->product_code,
                    'vendor_product_id'  => $product->vendor_product_id,
                    'avg_rating'         => $product->avg_rating,
                    'quantity'           => $product->cart->quantity ?? 0,
                    'checked'            => $product->cart->checked ?? 0,  // Added checked value from add_cart
                    'image' => $product->image ? url('asset/image/product/' . $product->image->image) : null,
                    'stock'              => $product->stock,
                    'in_stock'           => $product->in_stock,
                    'symbol'             => $product->sizeClass->symbol ?? null,
                    'tax'                => $product->tax,
                    'length'             => $product->length,
                    'width'              => $product->width,
                    'weight'             => $product->weight,
                    'height'             => $product->height,
                ];
            }); 

        // Initialize totals
        $totals = [
            'total_price'    => 0,
            'total_discount' => 0,
            'total_amount'   => 0,
        ];
        
        // Calculate totals only for those items where checked == 1
        foreach ($products as $item) {
            if ($item['checked'] == 1) {
                $quantity = $item['quantity'];
                $price = $item['price'];
                $discount = $item['discount'];

                $totals['total_price']    += $price * $quantity;
                $totals['total_discount'] += $discount * $quantity;
                $totals['total_amount']   += $discount * $quantity;
            }
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Cart Fetched Successfully!',
            'data'    => [
                'products' => $products,
                'totals'   => $totals
            ]
        ], 200);
    }

    public function removeFromCart(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'pid'     => 'required|integer',
        ]);

        $cartItem = AddCart::where('user_id', $request->user_id)
                            ->where('pid', $request->pid)
                            ->where('status', 'Active')
                            ->first();

        if ($cartItem) {
            $cartItem->status = 'Deleted';
            $cartItem->save();

            return response()->json([
                'status'  => 'success',
                'message' => 'Product removed from cart successfully!'
            ], 200);
        } else {
            return response()->json([
                'status'  => 'failed',
                'message' => 'Product not found in cart!'
            ], 404);
        }
    }

    public function updateCartCheckStatus(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'pid' => 'required|integer',
            'checked' => 'required|in:0,1',
        ]);
    
        $cartItem = AddCart::where('user_id', $request->user_id)
                            ->where('pid', $request->pid)
                            ->where('status', 'Active')
                            ->first();
    
        if (!$cartItem) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Cart item not found!',
            ], 404);
        }
    
        $cartItem->checked = $request->checked;
        $cartItem->save();
    
        return response()->json([
            'status' => 'success',
            'message' => 'Cart item updated successfully!',
            'checked' => $cartItem->checked
        ], 200);
    }
    

}
