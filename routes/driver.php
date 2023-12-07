<?php

use App\Http\Controllers\Driver\DriverController;
use Illuminate\Support\Facades\Route;


Route::post('driver/login', [DriverController::class, 'login']);


Route::prefix('driver')->middleware('custom_auth:driver')->group(function () {
    Route::get('me', [DriverController::class, 'me']);
    Route::post('logout', [DriverController::class, 'logout']);
});
