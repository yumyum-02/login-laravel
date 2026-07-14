<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    //フォームの表示
    public function create(): View
    {
        return view('auth.login');
    }

    //　フォームの送信処理
    public function store(Request $request): RedirectResponse
    {
        // バリデーション
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', 'string'],
            'password' => ['required', 'regex:/^[a-zA-Z0-9!@#$%^&*()_+\-=]+$/', 'min:8', 'max:64'],
        ]);

        // ログイン情報の作成
        $credentials = [
            'email' => mb_strtolower($validated['email'], 'UTF-8'),
            'password' => $validated['password'],
        ];

        // ログイン情報の認証
        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => 'ログイン情報が正しくありません。',
            ]);
        }

        // ログイン成功時の処理
        return redirect('/dashboard');
    }

    // ログアウト処理
    public function destroy(): RedirectResponse
    {
        Auth::logout();
        return redirect('/');
    }
}
