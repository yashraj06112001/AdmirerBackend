<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\AddCart;
use Illuminate\Support\Facades\Redis;

class CartController extends Controller
{

    public function getAllUserAddresses()
    {
        $userId = Auth::id();
        // 1. Get User Primary Address Info
        $user = DB::table('user as u')
            ->leftJoin('countries as c', 'c.id', '=', 'u.country')
            ->leftJoin('state_list as s', 's.id', '=', 'u.state')
            ->select(
                'u.id',
                'u.firstname',
                'u.lastname',
                'u.mobile',
                'u.mobileverified',
                'u.email',
                'u.emailverified',
                'u.flat',
                'u.street',
                'u.locality',
                'u.city',
                'u.zipcode',
                'u.state as state_id',
                's.state as state_name',
                'u.country as country_id',
                'c.country_name',
                'u.addr_type'
            )
            ->where('u.id', $userId)
            ->first();
    
        // 2. Get Shipping Addresses
        $shippingAddresses = DB::table('user_shipping_addresses as sp')
            ->join('countries as c', 'c.id', '=', 'sp.country')
            ->join('state_list as sl', 'sl.id', '=', 'sp.state')
            ->select(
                'sp.*',
                'c.country_name',
                'sl.state as state_name'
            )
            ->where('sp.user_id','=', $userId)
            ->where('sp.status','=', 'Active')
            ->orderByDesc('sp.id')
            ->get();
    
        // 3. Final Response
        return response()->json([
            'status'  => 'success',
            'message' => 'User & Shipping addresses fetched successfully!',
            'data'    => [
                'user_address'     => $user,
                'shipping_address' => $shippingAddresses
            ]
        ]);
    }
    
    
    public function getCartProducts(Request $request)
    {
        $userId = Auth::id();
    
        $products = Product::with(['cart', 'image', 'sizeClass'])
            ->where('status', 'Active')
            ->whereHas('cart', function($q) use ($userId) {
                $q->where('status', 'Active')
                  ->where('user_id', $userId);
            })
            ->get()
            ->map(function($product) {
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
                    'checked'            => $product->cart->checked ?? 0,
                    'image'              => $product->image ? 'https://admirer.in/asset/image/product/' . $product->image->image : null,
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
    
        // Totals
        $totals = [
            'total_price'    => 0,
            'total_discount' => 0,
            'total_amount'   => 0,
        ];
    
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

        $userAddresses = $this->getAllUserAddresses()->getData()->data;

        return response()->json([
            'status'  => 'success',
            'message' => 'Cart Fetched Successfully!',
            'data'    => [
                'products' => $products,
                'order_summary'   => $totals,
                'user_addresses'  => $userAddresses
            ]
        ], 200);
    }
    
    public function removeFromCart(Request $request)
    {
        $request->validate([
            'pid' => 'required|integer',
        ]);
    
        $userId = Auth::id(); 
    
        $cartItem = AddCart::where('user_id', $userId)
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
            'pid' => 'required|integer',
            'checked' => 'required|in:0,1',
        ]);
    
        $userId = Auth::id(); 
    
        $cartItem = AddCart::where('user_id', $userId)
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


    public function getCartAddress(Request $request)
    {
       $user=Auth::user();
       // user will have certain shipping Addresses send it to the final frontend
       $id=$user->id;
       $shippingAddresses=DB::table('user_shipping_addresses as ship')
       ->leftJoin('state_list','ship.state','=','state_list.id')
       ->select('ship.first_name','ship.last_name','ship.flat','ship.street'
       ,'ship.locality','ship.city','ship.zip_code','ship.email'
       ,'state_list.state','ship.addr_type')
       ->where('ship.user_id','=',$id)
       ->get();
       // with this we got the shipping Addresses Hoooreey!!!!!!!!!!
       //Now lets find the billing Address of the User

       $billingAddress=DB::table('user as u')
       ->leftJoin('state_list as s','s.id','=','u.state')
       ->select('u.firstname as first_name','u.lastname as last_name','u.flat','u.street','u.locality','u.city','u.zipcode as zip_code','u.addr_type',
       's.state',)
       ->where('u.id','=',$id)
       ->get();
       // Through this we already have the billing address as well, Hooreeyyy !!!

       // Now lets pass all the data as a JSON
       return response()->json([
        "shipping_address"=>$shippingAddresses,
        "billing_address"=>$billingAddress
       ]);

    }
   
    public function updateCartProductQuantity(Request $request)
    {   

        $userId = Auth::user()->id;
    $product_id = $request->productId;
    $currentQuantity = $request->quantity;

    $updated = DB::table('add_cart as ac')
        ->where('ac.user_id', '=', $userId)
        ->where('ac.pid', '=', $product_id)
        ->update([
            'quantity' => $currentQuantity
        ]);

    if ($updated) {
        return response()->json([
            'message' => "Product ID $product_id quantity updated to $currentQuantity in cart."
        ]);
    } else {
        return response()->json([
            'message' => "No cart entry found for product ID $product_id.",
        ], 404);
    }
    }

}
