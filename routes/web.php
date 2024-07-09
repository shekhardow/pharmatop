<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Artisan;

URL::forceScheme('https');
Route::get('/clear', function () {
    Artisan::call('route:clear');
    Artisan::call('optimize:clear');
    return 'Application cache cleared';
});

Route::get('/', function () {
    return view('welcome');
});
