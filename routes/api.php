<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostalServerController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\ExportController; 

// Public routes
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
});

// Protected routes
Route::group(['middleware' => 'auth:api'], function () {
    Route::group(['prefix' => 'auth'], function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Postal Server Management Routes
    Route::group(['prefix' => 'servers'], function () {
        Route::get('/', [PostalServerController::class, 'index']);
        Route::get('{postalServer}', [PostalServerController::class, 'show']);
        Route::post('{postalServer}/test-connection', [PostalServerController::class, 'testConnection']);
    });

    // Statistics Routes
    Route::group(['prefix' => 'stats'], function () {
        Route::group(['prefix' => 'server/{postalServer}'], function () {
            Route::get('/', [StatsController::class, 'server']);
            Route::get('suppressions', [StatsController::class, 'suppressions']);
            Route::delete('suppressions', [StatsController::class, 'deleteSuppressions']);
            Route::group(['prefix' => 'bounces'], function () {
                Route::get('/', [StatsController::class, 'bounces']);
                Route::get('domain', [StatsController::class, 'bouncesByDomain']);
                Route::get('email', [StatsController::class, 'bouncesByAddress']);
                Route::get('error-type', [StatsController::class, 'bouncesByErrorType']);
                Route::post('error-type/suppressions', [StatsController::class, 'suppressBouncesByErrorType']);
            });
        });
    });

    // Export Routes
    Route::group(['prefix' => 'export'], function () {
        Route::get('server/{postalServer}/bounces', [ExportController::class, 'bounces']);
        Route::get('server/{postalServer}/bounces/error-type', [ExportController::class, 'bouncesByErrorType']);
    });
});
