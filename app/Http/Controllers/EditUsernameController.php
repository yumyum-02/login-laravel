<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EditUsernameController extends Controller
{
    //create は新しいデータを作る画面、edit はすでにあるデータを直す画面
    public function edit(Request $request): View
    {
        return view('edit-username', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        // バリデーション
        $validated = $request->validate(
            [
                'name' => ['required', 'regex:/^[a-zA-Z0-9 \p{Hiragana}\p{Katakana}\p{Han}]+$/u', 'min:3', 'max:16'],
            ],
            [
                'name.required' => 'ユーザー名は必須です。',
                'name.regex' => 'ユーザー名は半角英数字、ひらがな、カタカナ、漢字のみ使用できます。',
                'name.min' => 'ユーザー名は3文字以上です。',
                'name.max' => 'ユーザー名は16文字以内です。',
            ]
        );

        // ユーザー名更新
        $request->user()->update([
            'name' => $validated['name'],
        ]);

        // リダイレクト
        return redirect()->route('account');
    }
}
