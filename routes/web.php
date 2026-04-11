<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/sysadmin', function () {
    return view('layouts.green_layout');
});