<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Artisan;

URL::forceScheme('https');

Route::get('/clear', function () {
    Artisan::call('route:clear');
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    Artisan::call('optimize:clear');
    $laravelVersion = app()->version();
    $phpVersion = phpversion();
    return "✔️ Application cache and optimizations have been cleared successfully.<br><br>" .
        "Laravel Version: $laravelVersion, PHP Version: $phpVersion";
});

Route::get('/composer-update', function () {
    $startTime = microtime(true);
    $composerOutput = shell_exec('composer update');
    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;
    return "✔️ Composer update has been completed.<br><br>" .
        "Composer Update Output:<br>" . nl2br($composerOutput) . "<br><br>" .
        "⏱️ Total Execution Time: " . round($executionTime, 2) . " seconds.";
});

Route::get('/', function () {
    Artisan::call('route:clear');
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    Artisan::call('optimize:clear');
    return view('index');
});
