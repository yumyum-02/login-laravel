<?php

namespace App\Http\Controllers;

//型宣言に使っている。型宣言自体はなくても動く
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;


class RegisterController extends Controller
{
    // フォームの表示
    public function create(): View
    {
        return view('auth.regist');
    }

    // フォームの送信処理
    public function store(Request $request): RedirectResponse
    {
        // バリデーション
        $validated = $request->validate([
            'name' => ['required', 'regex:/^[a-zA-Z0-9 \p{Hiragana}\p{Katakana}\p{Han}]+$/u', 'min:3', 'max:16'],
            'email' => ['required', 'email', 'max:255', 'string'],
            'password' => [
                'bail',
                'required', 'regex:/^[a-zA-Z0-9!@#$%^&*()_+\-=]+$/', 'min:8', 'max:64',
                Password::min(8)
                    ->letters()      // 英字を1文字以上
                    ->mixedCase()    // 大文字・小文字を両方
                    ->numbers()      // 数字を1文字以上
                    ->symbols(),     // 記号を1文字以上
            ],
        ]);

        $email = mb_strtolower($validated['email'], 'UTF-8');
        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'そのメールアドレスはすでに使用されています。',
            ]);
        }

        // ユーザー登録
        User::create([
            'name' => $validated['name'],
            'email' => $email,
            'password' => $validated['password'],  //ハッシュ化
        ]);

        return redirect('/')->with('message','会員登録が完了しました。ログインしてください。');
    }
}
