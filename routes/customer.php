<?php

use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Customer\TripController;
use Illuminate\Support\Facades\Route;


Route::post('customer/login', [CustomerController::class, 'login']);

Route::post('customer/register', [CustomerController::class, 'register']);


Route::prefix('customer')->middleware('custom_auth:customer')->group(function () {
    Route::get('me', [CustomerController::class, 'me']);
    Route::post('logout', [CustomerController::class, 'logout']);
    Route::patch('profile', [CustomerController::class, 'updateProfile']);

    Route::get('trips/my-trips', [TripController::class, 'customerTrips']);
    Route::get('trips/available', [TripController::class, 'available']);
    Route::get('trips/{id}', [TripController::class, 'find']);
    Route::post('trips/{id}/review', [TripController::class, 'review']);
    Route::post('trips/{id}/ticket', [TripController::class, 'buyTicket']);
});
