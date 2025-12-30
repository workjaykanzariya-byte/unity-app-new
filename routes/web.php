<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/admin/{path?}', 'admin')
    ->where('path', '.*')
    ->name('admin.app');
