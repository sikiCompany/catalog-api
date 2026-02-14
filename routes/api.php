<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SearchController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Route::prefix('v1')->group(function () {});
    
    Route::apiResource('products', ProductController::class);
    
    Route::get('search/products', [SearchController::class, 'search'])->name('products.search');
    
    Route::post('products/{id}/image', [ProductController::class, 'uploadImage'])->name('products.upload-image');
    
    Route::post('products/{id}/restore', [ProductController::class, 'restore'])->name('products.restore');


// Route::apiResource('products', ProductController::class);
// Route::get('search/products', [SearchController::class, 'search']);
