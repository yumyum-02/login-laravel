<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EditUsernameController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        // バリデーション
        $request->validate([
            'name' => ['required', 'regex:/^[a-zA-Z0-9 \p{Hiragana}\p{Katakana}\p{Han}]+$/u', 'min:3', 'max:16'],
        ]);

        // ユーザー名更新
        $request->user()->update([
            'name' => $request->name,
        ]);

        // リダイレクト
        return redirect('/account');
    }
}
