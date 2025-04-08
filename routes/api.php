<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\signUp;
use App\Http\Controllers\LoginHandler;
use App\Http\Controllers\VerifyPageAcessController;
use App\Http\Controllers\CartController;

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

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/cart-products', [CartController::class, 'getCartProducts']); // ✅ GET API
    Route::post('/cart-remove', [CartController::class, 'removeFromCart']);
    Route::post('/cart/update-check', [CartController::class, 'updateCartCheckStatus']);
});
