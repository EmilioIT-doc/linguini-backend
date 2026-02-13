<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;
use App\Http\Controllers\GeoController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\StripeController;

Route::post('/register', [UserController::class, 'register']);
Route::post('/login',    [UserController::class, 'login']);

Route::prefix('geo')->group(function () {
    Route::get('/geocode',     [GeoController::class, 'geocode']);
    Route::get('/reverse',     [GeoController::class, 'reverse']);
    Route::get('/directions',  [GeoController::class, 'directions']);
});

Route::get('/menu', [MenuController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {

    // Perfil user (con addresses)
    Route::get('/user',   [UserController::class, 'user']);          // GET perfil + addresses
    Route::patch('/user', [UserController::class, 'updateUser']);    // PATCH name + phone

    // Direcciones del user
    Route::get('/user/addresses',  [UserController::class, 'addressesIndex']); // GET
    Route::post('/user/addresses', [UserController::class, 'store']);          // POST
    Route::patch('/user/addresses/{id}', [UserController::class, 'update']);   // PATCH
    Route::delete('/user/addresses/{id}', [UserController::class, 'destroy']); // DELETE

    // Logout
    Route::post('/logout', [UserController::class, 'logout']);

    // Carrito
    Route::post('/cart/items/{product}', [CartController::class, 'addItem']);
    Route::get('/cartAuth',  [CartController::class, 'cartAuth']);
    Route::get('/fetchCart', [CartController::class, 'fetchCart']);
    Route::patch('/cart/items/{cartItem}', [CartController::class, 'updateQty']); 
    Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy']);


    // Stripe
    Route::post('/makePaymentCartAuth', [StripeController::class, 'makePaymentCartAuth']);
    Route::get('/stripe/session/{id}',  [StripeController::class, 'getSessionStatus']);
});
