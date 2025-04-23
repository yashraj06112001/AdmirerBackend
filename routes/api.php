<?php

use App\Http\Controllers\logoutHandlerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\signUp;
use App\Http\Controllers\LoginHandler;
use App\Http\Controllers\VerifyPageAcessController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\productListingController;
use App\Http\Controllers\ProductDetailsController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\AddToCartController;
use App\Http\Controllers\changePhoneNumberThroughDashboardController;
use App\Http\Controllers\HomepageController;
use App\Http\Controllers\userApiController;
use App\Http\Controllers\LoginSmsController;
use Laravel\Sanctum\Sanctum;
use App\Http\Controllers\PlaceOrderController;
use App\Http\Controllers\placeOrderFlowController;
use App\Http\Controllers\razorPayController;
use App\Http\Controllers\searchApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
// API to signUp the user
Route::middleware('auth:sanctum')->post("/signUp",[signUp::class,"signUpHandler"])->name("signUp");
//API to logged in the user
Route::post("/Login",[LoginHandler::class,'loginHandling'])->name("Login");
//API to verify user logged in or not
Route::middleware('auth:sanctum')->post("/Verify",[VerifyPageAcessController::class,"verify"])->name("Verify");

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/cart-products', [CartController::class, 'getCartProducts']); 
    Route::post('/cart-remove', [CartController::class, 'removeFromCart']);
    Route::post('/cart/update-check', [CartController::class, 'updateCartCheckStatus']);
});

//API to update cart Quantity
Route::middleware('auth:sanctum')->post('/update-cart-quantity',[CartController::class,'updateCartProductQuantity'])->name('update-cart-quantity');
//API to get all product-category and sub-category
Route::get("/product-category",[ProductCategoryController::class,"categorySubcategary"])->name("category");
//API to get price-category range
Route::get("/price-category",[ProductCategoryController::class,"PriceCategory"])->name("price-category");
// logout api's to log out from system
Route::middleware('auth:sanctum')->post("/logout",[logoutHandlerController::class,"logout"])->name("logout");
Route::middleware('auth:sanctum')->post("/logoutAll",[VerifyPageAcessController::class,"logoutAll"])->name("logoutAll");
//API to get all the products List
Route::get("/productListing/{cat?}/{subcat?}",[productListingController::class,"ShowProducts"])->name("productShow");
// api to give subcategories based on category -> send {category} in request to get all types of subcategories related to that category}
Route::post("/catSubCat",[ProductCategoryController::class,"categoryBasedSubcategory"])->name("subCategory");
// Product after applying filters of {category, subcategory, maxPrice, minPrice} so you need to send these only subCategory can be sended or remained null
Route::post('/productFilteredList',[productListingController::class,'productAfterFilterListing'])->name("filteredProduct");
Route::post('/getSubCatName',[productListingController::class,'getSubcatName'])->name('getSubCatName');
//.................................................Dashboard API............................................................................


//Profile API get and Update API 's
Route::middleware('auth:sanctum')->get("/user-profile",[DashboardController::class,"profileInfo"])->name("user-profile");
Route::middleware('auth:sanctum')->post('/updateProfile',[DashboardController::class,'profileUpdate'])->name('update_profile');
//Order API
Route::middleware('auth:sanctum')->get('/user-order-detail',[DashboardController::class,'AllOrderStatus'])->name("order-details");
Route::middleware('auth:sanctum')->get('/recent-orders',[DashboardController::class,'recentOrder'])->name("recent-orders");
Route::middleware('auth:sanctum')->get('/status-history',[DashboardController::class,'orderStatus'])->name('order-status');
Route::post("/order-detail",[DashboardController::class,'orderDetail']);
// get all product details and related product details 
Route::get('/product-details/{id}', [ProductDetailsController::class, 'getProductDetails']);
// add wishlist and remove wishlist
Route::post('/wishlist/toggle', [WishlistController::class, 'toggleWishlist']);
// add to cart and remove cart
Route::post('/add-to-cart', [AddToCartController::class, 'addToCart']);



// this API is used to get addresses from the user at cart-checkout-page
Route::middleware('auth:sanctum')->get('/get-address-cart',action:[CartController::class,'getCartAddress'])->name('getcartAddress');
// buy now 
Route::post('/buy-now', [AddToCartController::class, 'buyNow']);
// get user all wishlist
Route::middleware('auth:sanctum')->get('/user/wishlist', [WishlistController::class, 'getUserWishlist']);
// remove user wishlist
Route::middleware('auth:sanctum')->post('/user/wishlist/remove', [WishlistController::class, 'removeFromWishlist']);
// move to cart wishlist
Route::middleware('auth:sanctum')->post('/user/wishlist/movecart', [WishlistController::class, 'moveToCart']);

// Homepage banner img url
Route::get('/homepage-data', [HomepageController::class, 'getHomepageData']);

//USER DETAIL
Route::post('/getAddress',[userApiController::class,'getAddress'])->name('getAddress');
//place order details 
Route::middleware('auth:sanctum')->post('/place-order', [PlaceOrderController::class, 'placeOrder']);
// SMS API'S FOR LOGIN AND VERIFICATION
Route::post('/send-otp', [LoginSmsController::class, 'sendOtp']);
Route::post('/verify-otp', [LoginSmsController::class, 'verifyOtp']);



// PhoneNumber Update API's
Route::middleware('auth:sanctum')->post('/changeNumberSendOtp',[changePhoneNumberThroughDashboardController::class,'sendOtp'])->name('chnageNumberOtpSend');
Route::middleware('auth:sanctum')->post('/changeNumberOtpVerify',[changePhoneNumberThroughDashboardController::class,'verifyOtp'])->name('changeNumberOtpVerification');

//...........................................   Nimbus Deleivery Api.................................................................//
//Nimbus post to initiate delievery

Route::middleware('auth:sanctum')->post('/NimbusShippingStart',[placeOrderFlowController::class,'createOrder'])->name('nimbus-shipping-start');          

//.....................................................RazorPay API integration......................................................//


Route::middleware('auth:sanctum')->post('/razorPayStoreApi',[razorPayController::class,'store'])->name('razorpayStoreAPI');
Route::middleware('auth:sanctum')->post('/razorPayCreateOrderApi',[razorPayController::class,'createOrder'])->name('razorpayStoreAPI');


//..................................................Search API..............................................................//
Route::get('/search', [searchApiController::class, 'search']);