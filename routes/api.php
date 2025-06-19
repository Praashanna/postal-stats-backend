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
        Route::post('/', [PostalServerController::class, 'store']);
        Route::get('{postalServer}', [PostalServerController::class, 'show']);
        Route::put('{postalServer}', [PostalServerController::class, 'update']);
        Route::delete('{postalServer}', [PostalServerController::class, 'destroy']);
        Route::post('{postalServer}/test-connection', [PostalServerController::class, 'testConnection']);
        Route::patch('{postalServer}/toggle-status', [PostalServerController::class, 'toggleStatus']);
    });

    // Statistics Routes
    Route::group(['prefix' => 'stats'], function () {
        Route::group(['prefix' => 'server/{postalServer}'], function () {
            Route::get('/', [StatsController::class, 'server']);
            Route::group(['prefix' => 'bounces'], function () {
                Route::get('/', [StatsController::class, 'bounces']);
                Route::get('domain', [StatsController::class, 'bouncesByDomain']);
                Route::get('email', [StatsController::class, 'bouncesByAddress']);
            });
        });
    });

    // Export Routes
    Route::group(['prefix' => 'export'], function () {
        Route::get('server/{postalServer}/bounces', [ExportController::class, 'bounces']);
    });
});
