<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GeoController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\CartController;
use App\Models\User;





Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

Route::prefix('geo')->group(function () {
    Route::get('/geocode', [GeoController::class, 'geocode']);     // ?address=
    Route::get('/reverse', [GeoController::class, 'reverse']);     // ?lat=&lng=
    Route::get('/directions', [GeoController::class, 'directions']); // ?start=lng,lat&end=lng,lat
});

Route::get('/menu', [MenuController::class, 'index']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user',   [UserController::class, 'user']);
    Route::post('/logout',[UserController::class, 'logout']);
    Route::post('/cart/items/{product}', [CartController::class, 'addItem']);
    Route::get('/cartAuth', [CartController::class, 'cartAuth']);
    Route::get('/fetchCart', [CartController::class, 'fetchCart']);
});
