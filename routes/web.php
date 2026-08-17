<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\LoginController;

// ログイン画面表示
Route::get('/', [LoginController::class, 'create'])->name('login');
Route::post('/', [LoginController::class, 'store']);

// ログアウト
Route::post('/logout', [LoginController::class, 'destroy'])
    ->name('logout')
    ->middleware('auth');

// 会員登録画面表示
Route::get('/regist', [RegisterController::class, 'create']);
Route::post('/regist', [RegisterController::class, 'store']);

Route::get('/dashboard', function() {
    return view('dashboard');
})->middleware('auth');