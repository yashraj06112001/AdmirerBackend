<?php

use App\Http\Controllers\logoutHandlerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\signUp;
use App\Http\Controllers\LoginHandler;
use App\Http\Controllers\VerifyPageAcessController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ProductCategoryController;

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
Route::post("/signUp",[signUp::class,"signUpHandler"])->name("signUp");
Route::post("/Login",[LoginHandler::class,'loginHandling'])->name("Login");
Route::middleware('auth:sanctum')->post("/Verify",[VerifyPageAcessController::class,"verify"])->name("Verify");

Route::post('/cart-products', [CartController::class, 'getCartProducts']);
Route::post('/cart-remove', [CartController::class, 'removeFromCart']);
Route::post('/cart/update-check', [CartController::class, 'updateCartCheckStatus']);
Route::get("/product-category",[ProductCategoryController::class,"categorySubcategary"])->name("category");
Route::get("/price-category",[ProductCategoryController::class,"PriceCategory"])->name("price-category");
Route::middleware('auth:sanctum')->post("/logout",[logoutHandlerController::class,"logout"])->name("logout");
Route::middleware('auth:sanctum')->post("/logoutAll",[VerifyPageAcessController::class,"logoutAll"])->name("logoutAll");
