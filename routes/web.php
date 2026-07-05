<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth/login');
});

Route::get('/regist', function(){
    return view('auth/regist');
});

Route::get('/dashboard', function() {
    return view('dashboard');
});