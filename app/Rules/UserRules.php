<?php

namespace App\Rules;

use Illuminate\Validation\Rules\Password;

class UserRules
{
    public function __construct()
    {
        //
    }

    public static function name(): array
    {
        return ['required', 'regex:/^[a-zA-Z0-9 \p{Hiragana}\p{Katakana}\p{Han}]+$/u', 'min:3', 'max:16'];
    }
    public static function email(): array
    {
        return ['required', 'email', 'max:255'];
    }
    public static function password(): array
    {
        return [
            'bail',
            'required',
            'regex:/^[a-zA-Z0-9!@#$%^&*()_+\-=]+$/',
            'min:8',
            'max:64',
            Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
        ];
    }
}
