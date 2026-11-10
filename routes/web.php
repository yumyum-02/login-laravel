<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Edit\EditUsernameController;
use App\Http\Controllers\Edit\EditEmailController;
use App\Http\Controllers\Edit\EditPasswordController;
use App\Http\Controllers\Edit\EditIconController;
use Illuminate\Support\Facades\Auth;

// ログイン画面表示
Route::get('/', [LoginController::class, 'create'])->name('login');
Route::post('/', [LoginController::class, 'store']);

// 会員登録画面表示
Route::get('/regist', [RegisterController::class, 'create'])->name('register');
Route::post('/regist', [RegisterController::class, 'store']);

// ログイン後 authミドルウェアを使用するものをグループ化
Route::middleware('auth')->group(function () {
    // ログアウト
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // ダッシュボード画面表示
    Route::get('/dashboard', function () {
        return view('dashboard');
    });

    // アカウント情報画面表示
    Route::get('/account', function () {
        $user = Auth::user();
        return view('account', ['user' => $user]);
    })->name('account');

    // ユーザー名変更画面表示
    Route::get('/edit-username', [EditUsernameController::class, 'edit'])->name('edit-username');
    // ユーザー名変更 update
    Route::post('/edit-username', [EditUsernameController::class, 'update']);

    // メールアドレス変更画面表示
    Route::get('/edit-email', [EditEmailController::class, 'edit'])->name('edit-email');
    // メールアドレス変更 update
    Route::post('/edit-email', [EditEmailController::class, 'update']);

    // パスワード変更画面表示
    Route::get('/edit-password', [EditPasswordController::class, 'edit'])->name('edit-password');
    // パスワード変更 update
    Route::post('/edit-password', [EditPasswordController::class, 'update']);

    // アイコン変更画面表示
    Route::get('/edit-icon', [EditIconController::class, 'edit'])->name('edit-icon');
    // アイコンアップロード
    Route::post('/edit-icon/upload', [EditIconController::class, 'upload'])->name('edit-icon.upload');
    // アイコンキャンセル
    Route::post('/edit-icon/cancel', [EditIconController::class, 'cancel'])->name('edit-icon.cancel');
    // アイコンリセット
    Route::post('/edit-icon/reset', [EditIconController::class, 'reset'])->name('edit-icon.reset');
    // アイコン更新
    Route::post('/edit-icon/update', [EditIconController::class, 'update'])->name('edit-icon.update');
});
