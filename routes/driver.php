<?php

use App\Http\Controllers\Driver\DriverController;
use App\Http\Controllers\Driver\TripController;
use Illuminate\Support\Facades\Route;


Route::post('driver/login', [DriverController::class, 'login']);


Route::prefix('driver')->middleware('custom_auth:driver')->group(function () {
    Route::get('me', [DriverController::class, 'me']);
    Route::post('logout', [DriverController::class, 'logout']);
    Route::patch('profile', [DriverController::class, 'updateProfile']);

    Route::get('trips/my-trips', [TripController::class, 'driverTrips']);
    Route::patch('trips/{id}/departure-time', [TripController::class, 'setDepartureTime']);
    Route::patch('trips/{id}/arrival-time', [TripController::class, 'setArrivalTime']);
});
