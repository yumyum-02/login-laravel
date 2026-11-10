<?php

namespace App\Http\Controllers\Edit;

use App\Http\Controllers\Controller;
use App\Rules\UserRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class EditPasswordController extends Controller
{
    public function edit(Request $request): View
    {
        return view('edit.edit-password', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            [
                'current_password' => ['required', 'current_password'],
                'password' => UserRules::password(),
                'password_confirmation' => ['required', 'same:password'],
            ],
            [
                'current_password.required' => '現在のパスワードを入力してください。',
                'current_password.current_password' => '現在のパスワードが正しくありません。',
                'password_confirmation.required' => 'パスワード（確認用）を入力してください。',
                'password_confirmation.same' => 'パスワードが一致していません。',
            ]
        );

        $request->user()->update([
            'password' => $validated['password'],
        ]);

        $request->session()->regenerate();

        return redirect()->route('account');
    }
}
