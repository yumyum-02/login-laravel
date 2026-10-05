<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EditEmailController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        // バリデーション
        $validated = $request->validate(
            [
                'email' => ['required', 'email', 'max:255'],
            ],
            [
                'email.required' => 'メールアドレスは必須です。',
                'email.email' => 'メールアドレスの形式が不正です。',
                'email.max' => 'メールアドレスは255文字以内です。',
            ]
        );

        // 会員登録と同じく小文字にしてから、自分以外の重複を見る
        $email = mb_strtolower($validated['email'], 'UTF-8');
        if (User::where('email', $email)->where('id', '!=', $request->user()->id)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'そのメールアドレスはすでに使用されています。',
            ]);
        }

        $request->user()->update([
            'email' => $email,
        ]);

        return redirect()->route('account');
    }
}
