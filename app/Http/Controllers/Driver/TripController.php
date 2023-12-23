<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripController extends Controller
{
    public function driverTrips(Request $request)
    {
        $trips = DB::select("Select 
        trips.id,
        trips.bus_id,
        trips.driver_id,
        trips.route_id,
        trips.departure_time,
        trips.arrival_time,
        trips.actual_departure_time,
        trips.expected_duration,
        (select count(*) from tickets where tickets.trip_id = trips.id) as passengers,
        buses.plate_number,
        buses.model,
        bus_categories.name as bus_category_name,
        routes.name as route_name
        from trips 
        LEFT JOIN routes ON trips.route_id = routes.id
        LEFT JOIN buses ON trips.bus_id = buses.id
        LEFT JOIN bus_categories ON bus_categories.id = buses.bus_category_id
        WHERE trips.driver_id = ? ORDER BY departure_time asc", [$request->authenticated_id]);


        return response()->json([
            'success' => true,
            'data' => [
                'trips' => $trips
            ]
        ]);
    }

    public function setArrivalTime(Request $request, $tripId)
    {

        $trip = DB::selectOne("Select * from trips WHERE driver_id = ? AND id = ?", [
            $request->authenticated_id,
            $tripId
        ]);
        if (!$trip) {
            return $this->errorResponse("Not Found", 404);
        }

        if (!$trip->actual_departure_time) {
            return $this->errorResponse("You need to start trip first", 404);
        }

        DB::update("UPDATE trips set arrival_time = NOW() where id = ? AND arrival_time IS NULL", [$tripId]);

        return response()->json([
            'success' => true,
        ]);
    }

    public function setDepartureTime(Request $request, $tripId)
    {

        $trip = DB::selectOne("Select * from trips WHERE driver_id = ? AND id = ?", [
            $request->authenticated_id,
            $tripId
        ]);
        if (!$trip) {
            return $this->errorResponse("Not Found", 404);
        }

        DB::update("UPDATE trips set actual_departure_time = NOW() where id = ? AND actual_departure_time IS NULL", [$tripId]);

        return response()->json([
            'success' => true,
        ]);
    }
}
