<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StationController extends Controller
{
    public function index()
    {
        $stations = DB::select("Select name, id from stations");
        return response()->json([
            'success' => true,
            'data' => [
                'stations' => $stations
            ]
        ]);
    }
}
