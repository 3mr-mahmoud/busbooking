<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class StationController extends Controller
{
    public function index()
    {
        $stations = DB::select("Select * from stations");
        return response()->json([
            'success' => true,
            'data' => [
                'stations' => $stations
            ]
        ]);
    }

    public function find($stationId)
    {
        $station = DB::selectOne("Select * from stations where id = ?", [$stationId]);
        return response()->json([
            'success' => true,
            'data' => [
                'station' => $station
            ]
        ]);
    }
}
