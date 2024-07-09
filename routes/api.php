<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Artisan;
use App\Http\Middleware\ApiAuth;
use App\Http\Controllers\AdminController;

URL::forceScheme('https');
Route::get('/clear', function () {
    Artisan::call('route:clear');
    Artisan::call('optimize:clear');
    return 'Application cache cleared';
});

Route::group(['prefix' => 'admin'], function () {
    Route::post('/login', [AdminController::class, 'login']);
    Route::post('/forgotPassword', [AdminController::class, 'forgotPassword']);
    Route::post('/resendOTP', [AdminController::class, 'resendOTP']);
    Route::post('/resetPassword', [AdminController::class, 'resetPassword']);

    Route::post('/updateProfile', [AdminController::class, 'updateProfile']);
    Route::post('/changePassword', [AdminController::class, 'changePassword']);
});

Route::group(['prefix' => 'user', 'middleware' => ApiAuth::class], function () {
});
