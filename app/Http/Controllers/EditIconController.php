<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class EditIconController extends Controller
{
    public function edit(Request $request): View
    {
        return view('edit-icon', ['user' => $request->user()]);
    }

    public function upload(Request $request): RedirectResponse
    {
        // バリデーション
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
                'icon.mimes' => 'PNG または JPEG 形式の画像をアップロードしてください',
                'icon.max' => '容量は1MB以下の画像をアップロードしてください',
                'icon.dimensions' => '画像サイズは400px × 400px以下にしてください',
            ]
        );

        // 前の仮アイコンがあれば消す
        if ($request->session()->has('temp_icon')) {
            Storage::disk('public')->delete($request->session()->get('temp_icon'));
        }

        // アイコンの一時保存{id}_temp.拡張子
        $path = $request->file('icon')->storeAs(
            'icons',
            $request->user()->id.'_temp.'.$request->file('icon')->extension(),
            'public'
        );

        //　セッションに一時保存したアイコンのパスを保存
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
        // 一時保存したアイコンを消す（本番のアイコンは変えない）→ アカウント画面へ
        if ($request->session()->has('temp_icon')) {
            // 一時保存したアイコンを消す
            Storage::disk('public')->delete($request->session()->get('temp_icon'));

            // セッションから一時保存したアイコンのパスを消す
            $request->session()->forget('temp_icon');
        }
        return redirect('account');
    }

    public function update(Request $request): RedirectResponse
    {
        // exec_edit-icon.php 相当
        // 一時ファイルを本番にして DB を更新 → アカウント画面へ
    }
}