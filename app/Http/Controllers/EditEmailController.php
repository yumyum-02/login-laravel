<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EditEmailController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        // バリデーション
        $validated = $request->validate(
            [
                'email' => ['required','email', 'max:255', 'unique:users,email,' . $request->user()->id],
            ],
            [
                'email.required' => 'メールアドレスは必須です。',
                'email.email' => 'メールアドレスの形式が不正です。',
                'email.max' => 'メールアドレスは255文字以内です。',
                'email.unique' => 'そのメールアドレスはすでに使用されています。',
            ]
        );

        $request->user()->update([
            'email' => $validated['email'],
        ]);

        return redirect()->route('account');
    }
}
