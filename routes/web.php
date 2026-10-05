<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\EditUsernameController;
use App\Http\Controllers\EditEmailController;
use App\Http\Controllers\EditPasswordController;
use App\Http\Controllers\EditIconController;
use Illuminate\Support\Facades\Auth;

// ログイン画面表示
Route::get('/', [LoginController::class, 'create'])->name('login');
Route::post('/', [LoginController::class, 'store']);

// ログアウト
Route::post('/logout', [LoginController::class, 'destroy'])
    ->name('logout')
    ->middleware('auth');

// 会員登録画面表示
Route::get('/regist', [RegisterController::class, 'create'])->name('register.create');
Route::post('/regist', [RegisterController::class, 'store'])->name('register.store');

// ダッシュボード画面表示
Route::get('/dashboard', function() {
    return view('dashboard');
})->middleware('auth');

// アカウント情報画面表示
Route::get('/account' , function() {
    $user = Auth::user();
    return view('account',['user' => $user]);
})->name('account')->middleware('auth');

// ユーザー情報変更画面表示
Route::get('edit-username', [EditUsernameController::class, 'edit'])->name('edit-username')->middleware('auth');
// ユーザー名変更 update
Route::post('edit-username' , [EditUsernameController::class, 'update'])->name('update-username')->middleware('auth');

// メールアドレス変更画面表示
Route::get('edit-email', [EditEmailController::class, 'edit'])->name('edit-email')->middleware('auth');
// メールアドレス変更 update
Route::post('edit-email' , [EditEmailController::class, 'update'])->name('update-email')->middleware('auth');

//　パスワード変更画面表示
Route::get('edit-password', [EditPasswordController::class, 'edit'])->name('edit-password')->middleware('auth');
// パスワード変更 update
Route::post('edit-password' , [EditPasswordController::class, 'update'])->name('update-password')->middleware('auth');

// アイコン変更画面表示
Route::get('edit-icon', [EditIconController::class, 'edit'])->name('edit-icon')->middleware('auth');
// アイコンアップロード
Route::post('edit-icon/upload' , [EditIconController::class, 'upload'])->name('edit-icon.upload')->middleware('auth');
// アイコンキャンセル
Route::post('edit-icon/cancel' , [EditIconController::class, 'cancel'])->name('edit-icon.cancel')->middleware('auth');
// アイコンリセット
Route::post('edit-icon/reset' , [EditIconController::class, 'reset'])->name('edit-icon.reset')->middleware('auth');
// アイコン更新
Route::post('edit-icon/update' , [EditIconController::class, 'update'])->name('edit-icon.update')->middleware('auth');