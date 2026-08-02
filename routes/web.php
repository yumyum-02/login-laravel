<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\EditUsernameController;
use Illuminate\Support\Facades\Auth;

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

// ダッシュボード画面表示
Route::get('/dashboard', function() {
    return view('dashboard');
})->middleware('auth');

// アカウント情報画面表示
Route::get('account' , function() {
    $user = Auth::user();
    return view('account',['user' => $user]);
})->name('account')->middleware('auth');

// ユーザー情報変更画面表示
Route::get('edit-username' , function(){
    $user = Auth::user();
    return view('edit-username',['user' => $user]);
})->name('edit-username')->middleware('auth');

// ユーザー情報変更処理
// ユーザー名変更 update
Route::post('edit-username' , [EditUsernameController::class, 'update'])->name('update-username')->middleware('auth');