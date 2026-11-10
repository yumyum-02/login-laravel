<?php

namespace App\Http\Controllers\Edit;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\UserRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EditEmailController extends Controller
{
    public function edit(Request $request): View
    {
        return view('edit.edit-email', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        // バリデーション
        $validated = $request->validate([
            'email' => UserRules::email(),
        ]);

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
