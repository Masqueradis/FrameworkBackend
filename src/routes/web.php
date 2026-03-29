<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('login', function() {
    return view('auth.login');
})->name('login')->middleware('guest');

Route::get('register', function() {
    return view('auth.register');
})->name('register')->middleware('guest');

Route::get('dashboard', function() {
    return view('dashboard');
})->middleware('auth');

Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth');
Route::post('register', [AuthController::class, 'register']);
