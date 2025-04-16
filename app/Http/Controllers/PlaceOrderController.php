<?php 
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\AddCart;

class PlaceOrderController extends Controller
{
    public function placeOrder(Request $request)
    {
        $userId = Auth::id();
        $addressType = $request->address;
    
        DB::beginTransaction();
        try {
            if ($addressType === 'shipping') {
                $data = $request->input('address_data');
                DB::table('user_shipping_addresses')->insert([
                    'user_id'    => $userId,
                    'first_name' => $data['first_name'],
                    'last_name'  => $data['last_name'],
                    'phone'      => $data['phone'],
                    'email'      => $data['email'],
                    'flat'       => $data['flat'],
                    'street'     => $data['street'],
                    'locality'   => $data['locality'],
                    'country'    => $data['country'],
                    'state'      => $data['state'],
                    'city'       => $data['city'],
                    'zip_code'   => $data['zip_code'],
                    'addr_type'  => $data['addr_type'],
                    'datetime'   => now(),
                ]);
            }
    
            $paymentMethod = $request->paymentmethod;
            $paymentType = ($paymentMethod === 'cod') ? 'Cash On Delivery' : 'Online';
            $paymentMode = ($paymentMethod === 'cod') ? 'COD' : 'Online';
            $paymentStatus = 'Pending';
    
            $orderId = 'BTJ' . rand(10000000000, 999999999999);
            $date = now()->format('Y-m-d');
            $time = now()->format('H:i:s');
            $timestamp = now();
    
            $cartProducts = Product::with(['cart', 'image', 'sizeClass'])
                ->whereHas('cart', function ($q) use ($userId) {
                    $q->where('user_id', $userId)
                      ->where('status', 'Active')
                      ->where('checked', 1);
                })
                ->where('status', 'Active')
                ->get();
    
            if ($cartProducts->isEmpty()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'No products found in cart.'
                ]);
            }
    
            $orderPrice = 0;
    
            foreach ($cartProducts as $product) {
                $quantity = $product->cart->quantity;
                $checked = $product->cart->checked;
    
                if ($product->stock !== 'Yes' || $product->in_stock < $product->minimum) {
                    return response()->json([
                        'status' => 'failed',
                        'message' => $product->product_name . ' is out of stock or below minimum quantity.'
                    ]);
                }
    
                $trackingId = 'TDI' . rand(1000000, 9999999);
                $price = $product->price;
                $discount = $product->discount;
                $productTotal = $discount ? $discount * $quantity : $price * $quantity;
                $gstRate = $product->tax ?? 0;
                $gstAmount = ($productTotal * $gstRate) / 100;
    
                // Insert into order_details
                DB::table('order_details')->insert([
                    'user_id'       => $userId,
                    'order_id'      => $orderId,
                    'tracking_id'   => $trackingId,
                    'productid'     => $product->id,
                    'productname'   => $product->product_name,
                    'productimage'  => optional($product->image)->image ?? null,
                    'class0'        => $product->class0,
                    'class1'        => $product->class1,
                    'class2'        => $product->class2,
                    'class3'        => $product->class3,
                    'gst'           => $gstAmount,
                    'price'         => $productTotal,
                    'quantity'      => $quantity,
                    'payment_status'=> $paymentStatus,
                    'shipment_mode' => null,
                    'payment_type'  => $paymentType,
                    'date'          => $date,
                    'time'          => $time,
                    'timeStamp'     => $timestamp,
                ]);
    
                // Order status insert
                DB::table('order_status')->insert([
                    ['user_id' => $userId, 'order_id' => $orderId, 'tracking_id' => $trackingId, 'tracking_status' => 'Ordered and Approved', 'date' => $date, 'time' => $time],
                    ['user_id' => $userId, 'order_id' => $orderId, 'tracking_id' => $trackingId, 'tracking_status' => 'Your Order has been placed', 'date' => $date, 'time' => $time]
                ]);
    
                // Update product stock
                $remainingStock = $product->in_stock - $quantity;
                $isStock = $remainingStock == 0 ? 'No' : 'Yes';
    
                DB::table('products')->where('id', $product->id)->update([
                    'stock' => $isStock,
                    'in_stock' => $remainingStock
                ]);
    
                DB::table('stock')->insert([
                    'p_id' => $product->id,
                    'stock' => $remainingStock,
                    'type' => 'Debit',
                    'created_date' => $date,
                    'created_time' => $time
                ]);
    
                $orderPrice += $productTotal;
            }
    
            // Insert into order_tbl
            DB::table('order_tbl')->insert([
                'userid'        => $userId,
                'orderprice'    => $orderPrice,
                'promo_code_id' => $request->coupanCode ?? null,
                'payment_type'  => $paymentType,
                'payment_mode'  => $paymentMode,
                'shipping'      => $request->newshicherge ?? 0,
                'order_id'      => $orderId,
                'gst'           => '', // optional
                'exp_time'      => '',
                'exp_date'      => '',
                'coupan_code'   => $request->coupanCode ?? null,
                'date'          => $date,
                'time'          => $time
            ]);

            $userData = DB::table('user')->where('id', $userId)->first();

            if (
                empty($userData->flat) &&
                empty($userData->street) &&
                empty($userData->locality) &&
                empty($userData->city) &&
                empty($userData->zipcode) &&
                empty($userData->state) &&
                empty($userData->country)
            ) {
                DB::table('user')->where('id', $userId)->update([
                    'first_name' => $data['first_name'] ?? '',
                    'last_name'  => $data['last_name'] ?? '',
                    'phone'      => $data['phone'] ?? '',
                    'email'      => $data['email'] ?? '',
                    'flat'       => $data['flat'] ?? '',
                    'street'     => $data['street'] ?? '',
                    'locality'   => $data['locality'] ?? '',
                    'city'       => $data['city'] ?? '',
                    'zipcode'    => $data['zip_code'] ?? '',
                    'state'      => $data['state'] ?? '',
                    'country'    => $data['country'] ?? '',
                    'addr_type'  => $data['addr_type'] ?? '',
                ]);

                // Refresh user data after update
                $userData = DB::table('user')->where('id', $userId)->first();
            }

            // Billing address insert
            $billingArr = [
                'order_id'    => $orderId,
                'user_id'     => $userId,
                'first_name'  => $userData->firstname ?? '',
                'last_name'   => $userData->lastname ?? '',
                'flat'        => $userData->flat ?? '',
                'street'      => $userData->street ?? '',
                'locality'    => $userData->locality ?? '',
                'city'        => $userData->city ?? '',
                'zip_code'    => $userData->zipcode ?? '',
                'state'       => $userData->state ?? '',
                'country'     => $userData->country ?? '',
                'email'       => $userData->email ?? '',
                'phone'       => $userData->mobile ?? '',
                'addr_type'   => $userData->addr_type ?? '',
                'o_date'      => $date,
                'o_time'      => $time
            ];

            DB::table('billing_address')->insert($billingArr);

            if ($addressType === 'id') {
            
                $data = $request->input('address_data');


                $shipping_user = DB::table('user_shipping_addresses')
                                    ->where('user_id', $userId)
                                    ->where('id', $data['address_id'])
                                    ->first();

                if ($shipping_user) {
                    $shippingArr = [
                        'order_id'    => $orderId,
                        'user_id'     => $userId,
                        'first_name'  => $shipping_user->first_name,
                        'last_name'   => $shipping_user->last_name,
                        'flat'        => $shipping_user->flat,
                        'street'      => $shipping_user->street,
                        'locality'    => $shipping_user->locality,
                        'city'        => $shipping_user->city,
                        'zip_code'    => $shipping_user->zip_code,
                        'state'       => $shipping_user->state,
                        'country'     => $shipping_user->country,
                        'email'       => $shipping_user->email,
                        'phone'       => $shipping_user->phone,
                        'addr_type'   => $shipping_user->addr_type,
                        'o_date'      => $date,
                        'o_time'      => $time
                    ];
            
                    DB::table('shiping_address')->insert($shippingArr);
                }
            }
             else {
            // Shipping address fallback from billing if not set
                $shippingArr = [
                    'order_id'    => $orderId,
                    'user_id'     => $userId,
                    'first_name'  => $data['first_name'] ?? $userData->first_name,
                    'last_name'   => $data['last_name'] ?? $userData->last_name,
                    'flat'        => $data['flat'] ?? $userData->flat,
                    'street'      => $data['street'] ?? $userData->street,
                    'locality'    => $data['locality'] ?? $userData->locality,
                    'city'        => $data['city'] ?? $userData->city,
                    'zip_code'    => $data['zip_code'] ?? $userData->zipcode,
                    'state'       => $data['state'] ?? $userData->state,
                    'country'     => $data['country'] ?? $userData->country,
                    'email'       => $data['email'] ?? $userData->email,
                    'phone'       => $data['phone'] ?? $userData->phone,
                    'addr_type'   => $data['addr_type'] ?? $userData->addr_type,
                    'o_date'      => $date,
                    'o_time'      => $time
                ];

                DB::table('shiping_address')->insert($shippingArr);

        }

        // Cart status inactive कर दो
        DB::table('add_cart')
            ->where('user_id', $userId)
            ->where('status', 'Active')
            ->where('checked', 1)
            ->update(['status' => 'Deleted']);

        DB::commit();
        return response()->json([
            'status' => 'success',
            'message' => 'Order placed successfully.',
            'order_id' => $orderId
        ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong. Please try again.',
                'error' => $e->getMessage()
            ]);
        }
    }
}

