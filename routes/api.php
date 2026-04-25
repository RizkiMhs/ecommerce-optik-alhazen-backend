<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
// routes/api.php

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;

Route::post('register', [RegisterController::class, 'register']);
Route::post('login', [LoginController::class, 'login']);

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::middleware('auth:sanctum')->group(function () {

    // PROFILE
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'getProfile']);
    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'updateProfile']);

    // ADDRESS
    Route::get('/addresses', [\App\Http\Controllers\AddressController::class, 'index']);
    Route::post('/addresses', [\App\Http\Controllers\AddressController::class, 'store']);
    Route::put('/addresses/{id}', [\App\Http\Controllers\AddressController::class, 'update']);
    Route::delete('/addresses/{id}', [\App\Http\Controllers\AddressController::class, 'destroy']);


    // prduk
    Route::get('/products', [\App\Http\Controllers\ProductController::class, 'index']);
    Route::get('/lens-types', [\App\Http\Controllers\LensTypeController::class, 'index']);

    // CART
    Route::post('/cart', [\App\Http\Controllers\CartController::class, 'addToCart']);
    Route::get('/cart', [\App\Http\Controllers\CartController::class, 'index']);
    Route::put('/cart/{id}', [\App\Http\Controllers\CartController::class, 'update']);
    Route::delete('/cart/{id}', [\App\Http\Controllers\CartController::class, 'destroy']);
    // Route::post('/cart', [\App\Http\Controllers\Api\CartController::class, 'addToCart']);


    // ORDER
    Route::post('/checkout', [\App\Http\Controllers\OrderController::class, 'checkout']);
    Route::post('/checkout', [\App\Http\Controllers\OrderController::class, 'store']);
    // 💡 ROUTE BARU: Mengambil daftar pesanan
    Route::get('/orders', [App\Http\Controllers\OrderController::class, 'index']);
    Route::get('/orders/{id}/tracking', [App\Http\Controllers\OrderController::class, 'trackPackage']);

    // cek ongkir
    Route::post('/ongkir', [\App\Http\Controllers\OrderController::class, 'checkOngkir']);

    // get cities
    Route::get('/cities', [\App\Http\Controllers\AddressController::class, 'getCities']);

    // orders
    

});
Route::get('/products', [\App\Http\Controllers\ProductController::class, 'index']);
Route::get('/lens-types', [\App\Http\Controllers\LensTypeController::class, 'index']);

// Route ini terbuka untuk Midtrans
Route::post('/midtrans/callback', [App\Http\Controllers\OrderController::class, 'callback']);


// Route untuk menerima webhook dari Biteship
Route::post('/webhook/biteship', [App\Http\Controllers\BiteshipWebhookController::class, 'handle']);
