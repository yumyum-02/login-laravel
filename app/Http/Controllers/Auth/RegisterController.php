<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\UserRules;

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
            // app/Rules/UserRules.php で定義したバリデーションを使用する
            'name' => UserRules::name(),
            'email' => UserRules::email(),
            'password' => UserRules::password(),
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
            'password' => $validated['password'],
        ]);

        return redirect('/')->with('message','会員登録が完了しました。ログインしてください。');
    }
}
