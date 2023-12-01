<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


// admin routes
Route::middleware('custom_auth:admin')->get('admin/me', function (Request $request) {
    $admin = DB::select("select * from admins where id = " . $request->authenticated_id)[0];
    return response()->json([
        "success" => true,
        "data" => $admin
    ]);
});
