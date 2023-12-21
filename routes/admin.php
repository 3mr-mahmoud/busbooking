<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\BusController;
use App\Http\Controllers\Admin\RouteController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\StationController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\TripController;
use App\Http\Controllers\BusCategoryController;
use Illuminate\Support\Facades\Route;


Route::post('admin/login', [AdminController::class, 'login']);

Route::prefix('admin')->middleware('custom_auth:admin')->group(function () {

    Route::get('me', [AdminController::class, 'me']);
    Route::post('logout', [AdminController::class, 'logout']);


    Route::get('admins', [AdminController::class, 'index']);
    Route::get('admins/{id}', [AdminController::class, 'find']);
    Route::post('admins', [AdminController::class, 'store']);
    Route::patch('admins/{id}', [AdminController::class, 'update']);
    Route::delete('admins/{id}', [AdminController::class, 'delete']);

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


    Route::get('buses', [BusController::class, 'index']);
    Route::get('buses/{id}', [BusController::class, 'find']);
    Route::post('buses', [BusController::class, 'store']);
    Route::patch('buses/{id}', [BusController::class, 'update']);
    Route::patch('buses/{id}/seats/{seat_number}', [BusController::class, 'updateSeat']);
    Route::delete('buses/{id}', [BusController::class, 'delete']);


    Route::get('trips', [TripController::class, 'index']);
    Route::get('trips/{id}', [TripController::class, 'find']);
    Route::get('trips/{id}/available-seats', [TripController::class, 'availableSeats']);
    Route::post('trips', [TripController::class, 'store']);
    Route::patch('trips/{id}', [TripController::class, 'update']);
    Route::delete('trips/{id}', [TripController::class, 'delete']);

    Route::get('tickets', [TicketController::class, 'index']);
    Route::post('tickets', [TicketController::class, 'store']);

    Route::prefix("trips/{tid}/")->group(function () {
        Route::get('tickets', [TicketController::class, 'tripTickets']);
        Route::get('tickets/{tnumber}', [TicketController::class, 'find']);
        Route::patch('tickets/{tnumber}', [TicketController::class, 'update']);
        Route::delete('tickets/{tnumber}', [TicketController::class, 'delete']);
    });
});
