<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
//アップロード用
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rule;

class EditIconController extends Controller
{
    public function edit(Request $request): View
    {
        return view('edit-icon', ['user' => $request->user()]);
    }

    public function upload(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            [
                'icon' => [
                    'required',
                    'mimes:jpeg,png',
                    'max:1024', // 1MB
                    'dimensions:max_width=400,max_height=400',
                ],
            ],
            [
                'icon.required' => '画像がアップロードされていません',
                'icon.mimetypes' => 'PNG または JPEG 形式の画像をアップロードしてください',
                'icon.max' => '容量は1MB以下の画像をアップロードしてください',
                'icon.dimensions' => '画像サイズは400px × 400px以下にしてください',
            ]
        );

        // アイコンを保存{id}_temp.拡張子
        $path = $request->file('icon')->storeAs(
            'icons', $request->user()->id.'_temp.'.$request->file('icon')->extension()
        );

        // 一時保存に失敗した場合
        if (!$path) {
            return redirect('edit-icon')->with('error', 'アイコンの保存に失敗しました');
        }

        //　セッションに保存
        $request->session()->put('temp_icon', $path);

        //　編集画面へリダイレクト
        return redirect('edit-icon');
    }

    public function reset(Request $request): RedirectResponse
    {
        // exec_icon_reset.php 相当
        // 画像を消して DB の icon を空に → アカウント画面へ
    }

    public function cancel(Request $request): RedirectResponse
    {
        // exec_icon_cancel.php 相当
        // 一時ファイルだけ消す（本番のアイコンは変えない）→ アカウント画面へ
    }

    public function update(Request $request): RedirectResponse
    {
        // exec_edit-icon.php 相当
        // 一時ファイルを本番にして DB を更新 → アカウント画面へ
    }
}