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
    // フォームの表示 create:表示する
    public function create(): View
    {
        return view('auth.regist');
    }

    // フォームの送信処理 store:保存する
    public function store(Request $request): RedirectResponse
    {
        // バリデーション
        $validated = $request->validate([
            // 名前のバリデーション 必須、正規表現で半角英数字と全角ひらがな、カタカナ、漢字を使用できるようにする
            'name' => ['required', 'regex:/^[a-zA-Z0-9 \p{Hiragana}\p{Katakana}\p{Han}]+$/u', 'min:3', 'max:16'],
            // メールアドレスのバリデーション　必須 メールアドレスの形式、最大255文字、文字列か
            'email' => ['required', 'email', 'max:255', 'string'],
            // パスワードのバリデーション 必須、正規表現で半角英数字と記号を使用できるようにする、最小8文字、最大64文字
            'password' => [
                'bail', // エラーが発生したら、残りのバリデーションをスキップする 必須で落ちたらその先のバリデーションをスキップする（ルールが多いためbailを採用）
                'required',
                'regex:/^[a-zA-Z0-9!@#$%^&*()_+\-=]+$/',
                'min:8', 'max:64',
                Password::min(8)
                    ->letters()      // 英字を1文字以上
                    ->mixedCase()    // 大文字・小文字を両方
                    ->numbers()      // 数字を1文字以上
                    ->symbols(),     // 記号を1文字以上
            ],
        ]);

        // メールアドレスの重複チェック
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
