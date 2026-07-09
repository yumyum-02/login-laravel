<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegisterController;

Route::get('/', function () {
    return view('auth/login');
});

// 会員登録画面表示
Route::get('/regist', [RegisterController::class, 'create']);
Route::post('/regist', [RegisterController::class, 'store']);

Route::get('/dashboard', function() {
    return view('dashboard');
});