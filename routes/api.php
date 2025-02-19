<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Artisan;
use App\Http\Middleware\ApiAuth;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EncryptDecryptMiddleware;

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

Route::group(['middleware' => EncryptDecryptMiddleware::class], function () {
    Route::middleware('throttle:60,1')->group(function () {
        Route::group(['prefix' => 'common'], function () {
            Route::post('/login', [CommonController::class, 'login']);
            Route::post('/forgotPassword', [CommonController::class, 'forgotPassword']);
            Route::post('/resendOTP', [CommonController::class, 'resendOTP']);
            Route::post('/verifyResetPasswordOTP', [CommonController::class, 'verifyResetPasswordOTP']);
            Route::post('/resetPassword', [CommonController::class, 'resetPassword']);
        });
    });

    Route::middleware('throttle:100,1')->group(function () {
        Route::group(['prefix' => 'admin'], function () {
            Route::get('/getAdminById/{id?}', [AdminController::class, 'getAdminById']);
            Route::post('/updateProfile', [AdminController::class, 'updateProfile']);
            Route::post('/changePassword', [AdminController::class, 'changePassword']);

            Route::get('/getStaticContent', [AdminController::class, 'getStaticContent']);
            Route::post('/updateStaticContent', [AdminController::class, 'updateStaticContent']);

            Route::get('/getDashboardData', [AdminController::class, 'getDashboardData']);

            Route::get('/getAllUsers', [AdminController::class, 'getAllUsers']);
            Route::get('/getUserById/{id?}', [AdminController::class, 'getUserById']);

            Route::get('/getAllCategories', [AdminController::class, 'getAllCategories']);
            Route::get('/getCategoryById/{id?}', [AdminController::class, 'getCategoryById']);
            Route::post('/addCategory', [AdminController::class, 'addCategory']);
            Route::post('/updateCategory', [AdminController::class, 'updateCategory']);
            Route::post('/deleteCategory', [AdminController::class, 'deleteCategory']);
            Route::post('/upvoteCategory', [AdminController::class, 'upvoteCategory']);

            Route::get('/getAllCourses', [AdminController::class, 'getAllCourses']);
            Route::get('/getCourseById/{id?}', [AdminController::class, 'getCourseById']);
            Route::post('/addCourse', [AdminController::class, 'addCourse']);
            Route::post('/updateCourse', [AdminController::class, 'updateCourse']);
            Route::post('/deleteCourse', [AdminController::class, 'deleteCourse']);
            Route::post('/upvoteCourse', [AdminController::class, 'upvoteCourse']);
            Route::post('/addModuleVideo', [AdminController::class, 'addModuleVideo']);
            Route::post('/deleteModuleVideo', [AdminController::class, 'deleteModuleVideo']);
            Route::post('/addModuleDocument', [AdminController::class, 'addModuleDocument']);
            Route::post('/deleteModuleDocument', [AdminController::class, 'deleteModuleDocument']);

            Route::get('/getAllBillingInfo', [AdminController::class, 'getAllBillingInfo']);
        });
    });

    Route::middleware('throttle:60,1')->group(function () {
        Route::post('user/register', [UserController::class, 'register']);
        // Route::get('user/insert', [UserController::class, 'insertDummyUsers']);
        Route::get('user/getStaticContent', [UserController::class, 'getStaticContent']);
        Route::get('user/getAllCategories/{guest}', [UserController::class, 'getAllCategories'])->where('guest', 'guest');
        Route::get('user/getAllCourses/{guest}', [UserController::class, 'getAllCourses'])->where('guest', 'guest');
        Route::get('user/getCourseByCategoryId/{id?}/{guest}', [UserController::class, 'getCourseByCategoryId'])->where('guest', 'guest');
        Route::get('user/getCourseDetailsById/{id?}/{guest}', [UserController::class, 'getCourseDetailsById'])->where('guest', 'guest');
        Route::get('user/getAllCountries', [UserController::class, 'getAllCountries']);
        Route::get('user/getAllStatesByCountry/{id?}', [UserController::class, 'getAllStatesByCountry']);
        Route::get('user/getAllCitiesByState/{id?}', [UserController::class, 'getAllCitiesByState']);
        Route::get('user/downloadCertificate/{user_id?}/{course_id?}', [UserController::class, 'downloadCertificate']);

        Route::post('/update-playback', [UserController::class, 'updatePlayback']);

        Route::group(['prefix' => 'user', 'middleware' => ApiAuth::class], function () {
            Route::get('/getUserDetails', [UserController::class, 'getUserDetails']);
            Route::post('/updateProfile', [UserController::class, 'updateProfile']);
            Route::post('/changePassword', [UserController::class, 'changePassword']);

            Route::get('/getAllCategories', [UserController::class, 'getAllCategories']);
            Route::get('/getAllCourses', [UserController::class, 'getAllCourses']);
            Route::get('/getCourseByCategoryId/{id?}', [UserController::class, 'getCourseByCategoryId']);
            Route::get('/getCourseDetailsById/{id?}', [UserController::class, 'getCourseDetailsById']);

            Route::post('/toggleWishlist', [UserController::class, 'toggleWishlist']);
            Route::get('/getAllWishlistItems', [UserController::class, 'getAllWishlistItems']);

            Route::post('/toggleCart', [UserController::class, 'toggleCart']);
            Route::get('/getAllCartItems', [UserController::class, 'getAllCartItems']);

            Route::post('/createPaymentIntent', [UserController::class, 'createPaymentIntent']);
            Route::post('/initiateCheckout', [UserController::class, 'initiateCheckout']);
            Route::post('/checkout', [UserController::class, 'checkout']);
            Route::get('/getAllUserPurchasedCourses', [UserController::class, 'getAllUserPurchasedCourses']);

            Route::post('/completeVideo', [UserController::class, 'completeVideo']);
            Route::post('/generateCertificate', [UserController::class, 'generateCertificate']);

            Route::post('/logout', [UserController::class, 'logout']);
        });
    });
});
