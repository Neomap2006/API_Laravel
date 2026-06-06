<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PelangganController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\PembelianController;


Route::apiResource('users', UserApiController::class);
Route::apiResource('books', BookController::class);
Route::apiResource('products', ProductController::class);
Route::post('products/bulk-upload', [ProductController::class, 'bulkUpload']);
Route::post('products/{id}/upload-images', [ProductController::class, 'uploadImages']);
Route::post('products/images/{id}', [ProductController::class, 'updateImage']);
Route::delete('products/images/{id}', [ProductController::class, 'destroyImage']);
Route::apiResource('orders', OrderController::class);
Route::put('orders/{id}/status', [OrderController::class, 'updateStatus']);

// Pelanggan
Route::get('pelanggan', [PelangganController::class, 'index']);
Route::post('pelanggan', [PelangganController::class, 'store']);
Route::get('pelanggan/phone/{no_hp}', [PelangganController::class, 'findByPhone']);
Route::put('pelanggan/{id}', [PelangganController::class, 'update']);
Route::delete('pelanggan/{id}', [PelangganController::class, 'destroy']);

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', function (Request $request) {
        return $request->user();
    });
    Route::post('logout', [AuthController::class, 'logout']);

    // Supplier
    Route::get('/suppliers', [SupplierController::class, 'index']);
    Route::post('/suppliers', [SupplierController::class, 'store']);
    Route::get('/suppliers/{supplier}', [SupplierController::class, 'show']);
    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update']);
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy']);

    // Pembelian
    Route::get('/pembelians', [PembelianController::class, 'index']);
    Route::post('/pembelians', [PembelianController::class, 'store']);
    Route::get('/pembelians/{pembelian}', [PembelianController::class, 'show']);
    Route::patch('/pembelians/{pembelian}/status', [PembelianController::class, 'updateStatus']);
    Route::delete('/pembelians/{pembelian}', [PembelianController::class, 'destroy']);
});

