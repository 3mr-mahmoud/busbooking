<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\RouteController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\StationController;
use App\Http\Controllers\BusCategoryController;
use Illuminate\Support\Facades\Route;


Route::post('admin/login', [AdminController::class, 'login']);

Route::prefix('admin')->middleware('custom_auth:admin')->group(function () {

    Route::get('me', [AdminController::class, 'me']);
    Route::post('logout', [AdminController::class, 'logout']);

    Route::get('stations', [StationController::class, 'index']);
    Route::get('stations/{id}', [StationController::class, 'find']);
    Route::post('stations', [StationController::class, 'store']);
    Route::patch('stations/{id}', [StationController::class, 'update']);
    Route::delete('stations/{id}', [StationController::class, 'delete']);

    Route::get('routes', [RouteController::class, 'index']);
    Route::get('routes/{id}', [RouteController::class, 'find']);
    Route::post('routes', [RouteController::class, 'store']);
    Route::patch('routes/{id}', [RouteController::class, 'update']);
    Route::patch('routes/{id}/stations', [RouteController::class, 'updateStations']);
    Route::delete('routes/{id}', [RouteController::class, 'delete']);


    Route::get('services', [ServiceController::class, 'index']);
    Route::get('services/{id}', [ServiceController::class, 'find']);
    Route::post('services', [ServiceController::class, 'store']);
    Route::patch('services/{id}', [ServiceController::class, 'update']);
    Route::delete('services/{id}', [ServiceController::class, 'delete']);


    Route::get('bus-categories', [BusCategoryController::class, 'index']);
    Route::get('bus-categories/{id}', [BusCategoryController::class, 'find']);
    Route::post('bus-categories', [BusCategoryController::class, 'store']);
    Route::patch('bus-categories/{id}', [BusCategoryController::class, 'update']);
    Route::delete('bus-categories/{id}', [BusCategoryController::class, 'delete']);
});
