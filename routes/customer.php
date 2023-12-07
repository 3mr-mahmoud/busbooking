<?php

use App\Http\Controllers\Customer\CustomerController;
use Illuminate\Support\Facades\Route;


Route::post('customer/login', [CustomerController::class, 'login']);


Route::prefix('customer')->middleware('custom_auth:customer')->group(function () {
    Route::get('me', [CustomerController::class, 'me']);
    Route::post('logout', [CustomerController::class, 'logout']);
});
