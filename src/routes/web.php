<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Web\ProductController as WebProductController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminCategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\AdminProductController as AdminProductController;

Route::redirect('/', '/catalog');

Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [WebProductController::class, 'show'])->name('web.products.show');

Route::middleware('guest')->controller(AuthController::class)->group(function () {
    Route::get('/login', 'showLoginForm')->name('login');
    Route::post('/login', 'login')->name('login.post');
    Route::get('/register', 'showRegisterForm')->name('register');
    Route::post('/register', 'register')->name('register.post');
    Route::get('/verify/{token}', 'verifyEmail')->name('verification.verify.custom');
});

Route::prefix('cart')->name('cart.')->group(function () {
    Route::post('/add', [CartController::class, 'add'])->name('add');
    Route::patch('/{cartItem}', [CartController::class, 'update'])->name('update');
    Route::delete('/{cartItem}', [CartController::class, 'remove'])->name('remove');
});

Route::middleware('auth')->controller(AuthController::class)->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::prefix('email')->group(function () {
        Route::get('/verify', 'showVerificationNotice')->name('verification.notice');

        Route::get('verify/{id}/{hash}', 'verifyEmail')
            ->name('verification.verify');

        Route::post('verification-notification', 'resendVerificationEmail')
            ->middleware('throttle:6,1')
            ->name('verification.send');
    });

    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');

    Route::prefix('admin')->middleware('can:access-panel')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');

        Route::resource('categories', AdminCategoryController::class)
            ->middleware('can:manage-categories')
            ->names('admin.categories')->except(['show']);

        Route::resource('products', AdminProductController::class)
            ->names('admin.products')->except(['show']);

        Route::post('/products/{product}/images', [AdminProductController::class, 'uploadImage'])
            ->name('admin.products.images.upload');
    });
});
