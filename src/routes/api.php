<?php

declare(strict_types=1);

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ApiController;

Route::prefix('v1')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post('/register', 'register');
        Route::post('/login', 'login');
        Route::get('/verify/{token}', 'verifyEmail');
    });

    Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
    Route::apiResource('products', ProductController::class)->only(['index', 'show']);

    Route::middleware('auth:api')->group(function () {
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::controller(ProductController::class)->prefix('products')->group(function () {
            Route::post('/', 'store')->can('create', Product::class);
            Route::put('/{product}', 'update')->can('update', 'product');
            Route::delete('/{product}', 'destroy')->can('delete', 'product');
        });

        Route::controller(CategoryController::class)->prefix('categories')->group(function () {
            Route::post('/', 'store')->can('create', Category::class);
            Route::put('/{category}', 'update')->can('update', 'category');
            Route::delete('/{category}', 'destroy')->can('delete', 'category');
        });
    });
});
