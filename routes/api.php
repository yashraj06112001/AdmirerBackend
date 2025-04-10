<?php

use App\Http\Controllers\logoutHandlerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\signUp;
use App\Http\Controllers\LoginHandler;
use App\Http\Controllers\VerifyPageAcessController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\productListingController;

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
Route::post("/signUp",[signUp::class,"signUpHandler"])->name("signUp");
//API to logged in the user
Route::post("/Login",[LoginHandler::class,'loginHandling'])->name("Login");
//API to verify user logged in or not
Route::middleware('auth:sanctum')->post("/Verify",[VerifyPageAcessController::class,"verify"])->name("Verify");

Route::post('/cart-products', [CartController::class, 'getCartProducts']);
Route::post('/cart-remove', [CartController::class, 'removeFromCart']);
Route::post('/cart/update-check', [CartController::class, 'updateCartCheckStatus']);
//API to get all product-category and sub-category
Route::get("/product-category",[ProductCategoryController::class,"categorySubcategary"])->name("category");
//API to get price-category range
Route::get("/price-category",[ProductCategoryController::class,"PriceCategory"])->name("price-category");
// logout api's to log out from system
Route::middleware('auth:sanctum')->post("/logout",[logoutHandlerController::class,"logout"])->name("logout");
Route::middleware('auth:sanctum')->post("/logoutAll",[VerifyPageAcessController::class,"logoutAll"])->name("logoutAll");
//API to get all the products List
Route::get("/productListing",[productListingController::class,"ShowProducts"])->name("productShow");
// api to give subcategories based on category -> send {category} in request to get all types of subcategories related to that category}
Route::post("/catSubCat",[ProductCategoryController::class,"categoryBasedSubcategory"])->name("subCategory");
// Product after applying filters of {category, subcategory, maxPrice, minPrice} so you need to send these only subCategory can be sended or remained null
Route::post('/productFilteredList',[productListingController::class,'productAfterFilterListing'])->name("filteredProduct");