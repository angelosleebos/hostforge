<?php

use Illuminate\Support\Facades\Route;

// Public SPA routes
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!admin|api).*$');

// Admin SPA routes
Route::get('/admin/{any?}', function () {
    return view('admin');
})->where('any', '.*');
