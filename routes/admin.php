<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\StationController;
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
});

// admin/stations/5
