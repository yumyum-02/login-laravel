<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class EditPasswordController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            [
                'current_password' => ['required', 'current_password'],
                'new_password' => [
                    'bail',
                    'required',
                    'regex:/^[a-zA-Z0-9!@#$%^&*()_+\-=]+$/',
                    'min:8',
                    'max:64',
                    Password::min(8)
                        ->letters()
                        ->mixedCase()
                        ->numbers()
                        ->symbols(),
                ],
                'new_password_confirmation' => ['required', 'same:new_password'],
            ],
            [
                'current_password.required' => '現在のパスワードを入力してください。',
                'current_password.current_password' => '現在のパスワードが正しくありません。',
                'new_password.required' => 'パスワードを入力してください。',
                'new_password.regex' => 'パスワードは半角英数字と記号で入力してください。',
                'new_password.min' => 'パスワードは8文字以上64文字以内で入力してください。',
                'new_password.max' => 'パスワードは8文字以上64文字以内で入力してください。',
                'new_password_confirmation.required' => 'パスワード（確認用）を入力してください。',
                'new_password_confirmation.same' => 'パスワードが一致していません。',
            ]
        );

        $request->user()->update([
            'password' => $validated['new_password'],
        ]);

        $request->session()->regenerate();

        return redirect()->route('account');
    }
}
