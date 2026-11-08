<?php

namespace App\Http\Controllers\Edit;

use App\Http\Controllers\Controller;
use App\Rules\UserRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EditUsernameController extends Controller
{
    //create は新しいデータを作る画面、edit はすでにあるデータを直す画面
    public function edit(Request $request): View
    {
        return view('edit.edit-username', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        // バリデーション
        $validated = $request->validate([
            'name' => UserRules::name(),
        ]);

        // ユーザー名更新
        $request->user()->update([
            'name' => $validated['name'],
        ]);

        // リダイレクト
        return redirect()->route('account');
    }
}
