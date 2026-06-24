<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\CommentModerationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\UserReviewController;
use App\Http\Controllers\Web\CartController;
use App\Http\Controllers\Web\CatalogController;
use App\Http\Controllers\Web\CommentController;
use App\Http\Controllers\Web\ProductController as WebProductController;
use App\Http\Controllers\Web\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/catalog');

Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [WebProductController::class, 'show'])->name('web.products.show');

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/result', [CheckoutController::class, 'result'])->name('checkout.result');

Route::middleware('guest')->controller(AuthController::class)->group(function () {
    Route::get('/login', 'showLoginForm')->name('login');
    Route::post('/login', 'login')->name('login.post');
    Route::get('/register', 'showRegisterForm')->name('register');
    Route::post('/register', 'register')->name('register.post');
    Route::get('/verify/{token}', 'verifyEmail')->name('verification.verify.custom');

    Route::get('/login/2fa', [TwoFactorController::class, 'showVerifyForm'])->name('login.2fa');
    Route::post('/login/2fa', [TwoFactorController::class, 'verifyLogin'])
        ->middleware('throttle:5,1')
        ->name('login.2fa.post');
});

Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add', [CartController::class, 'add'])->name('add');
    Route::patch('/{cartItem}', [CartController::class, 'update'])->name('update');
    Route::delete('/{cartItem}', [CartController::class, 'remove'])->name('remove');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/checkout/cancel/{order}', [CheckoutController::class, 'cancel'])->name('checkout.cancel');
    Route::post('/checkout/retry/{order}', [CheckoutController::class, 'retry'])->name('checkout.retry');
    Route::delete('/checkout/decline/{order}', [CheckoutController::class, 'decline'])->name('checkout.decline');

    Route::prefix('email')->controller(AuthController::class)->group(function () {
        Route::get('/verify', 'showVerificationNotice')->name('verification.notice');

        Route::get('verify/{id}/{hash}', 'verifyEmail')
            ->name('verification.verify');

        Route::post('verification-notification', 'resendVerificationEmail')
            ->middleware('throttle:6,1')
            ->name('verification.send');
    });

    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::delete('/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');

    Route::get('/profile/reviews', [UserReviewController::class, 'index'])->name('profile.reviews.index');
    Route::patch('/profile/reviews/{comment}', [UserReviewController::class, 'update'])
        ->middleware('not.banned')
        ->name('profile.reviews.update');

    Route::post('/2fa/generate', [TwoFactorController::class, 'generate'])->name('2fa.generate');
    Route::post('/2fa/enable', [TwoFactorController::class, 'enable'])->name('2fa.enable');
    Route::delete('/2fa/disable', [TwoFactorController::class, 'disable'])->name('2fa.disable');

    Route::post('/products/{product}/comments', [CommentController::class, 'store'])
        ->middleware(['auth', 'not.banned'])
        ->name('comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])
        ->middleware(['auth', 'not.banned'])
        ->name('comments.destroy');

    Route::prefix('admin')->middleware('role:admin|manager|seller')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])
            ->middleware('role:admin|manager')
            ->name('admin.dashboard');

        Route::resource('categories', AdminCategoryController::class)
            ->middleware('can:manage,App\Models\Category')
            ->names('admin.categories')->except(['show']);

        Route::resource('products', AdminProductController::class)
            ->names('admin.products')->except(['show']);

        Route::post('/products/{product}/images', [AdminProductController::class, 'uploadImage'])
            ->name('admin.products.images.upload');
        Route::delete('/products/{product}/images', [AdminProductController::class, 'destroyAllImages'])
            ->name('admin.products.images.destroy-all');

        Route::middleware('can:manage-users')->group(function () {
            Route::prefix('users')->name('admin.users.')->group(function () {
                Route::get('/', [AdminUserController::class, 'index'])->name('index');
                Route::patch('/{user}/ban', [AdminUserController::class, 'ban'])->name('ban');
                Route::patch('/{user}/unban', [AdminUserController::class, 'unban'])->name('unban');
                Route::patch('/{user}/assign-role', [AdminUserController::class, 'assignRole'])->name('assign-role');
            });

            Route::get('/comments', [CommentModerationController::class, 'index'])->name('admin.comments.index');
            Route::patch('comments/{comment}/approve', [CommentModerationController::class, 'approve'])
                ->name('admin.comments.approve');
            Route::patch('/comments/{comment}/reject', [CommentModerationController::class, 'reject'])
                ->name('admin.comments.reject');
            Route::delete('/comments/{comment}', [CommentModerationController::class, 'destroy'])->name('admin.comments.destroy');

            Route::prefix('reports')->name('admin.reports.')->group(function () {
                Route::get('/', [ReportController::class, 'index'])->name('index');
                Route::post('/', [ReportController::class, 'store'])->name('store');
                Route::get('/{report}/download', [ReportController::class, 'download'])->name('download');
            });
        });
    });
});
