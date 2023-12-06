<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;


Route::post('admin/login', [AdminController::class, 'login']);


Route::prefix('admin')->middleware('custom_auth:admin')->group(function () {
    Route::get('me', [AdminController::class, 'me']);
    Route::post('logout', [AdminController::class, 'logout']);
});
