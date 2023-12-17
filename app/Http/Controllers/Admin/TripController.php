<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripController extends Controller
{
    public function index()
    {
        $trips = DB::select("Select trips.*, 
        admins.name as creator_name,
        drivers.name as driver_name,
        routes.name as route_name
        from trips 
        LEFT JOIN routes ON trips.route_id = routes.id
        LEFT JOIN admins ON trips.created_by = admins.id
        LEFT JOIN drivers ON trips.driver_id = drivers.id
        ");
        return response()->json([
            'success' => true,
            'data' => [
                'trips' => $trips
            ]
        ]);
    }

    public function find($tripId)
    {
        if (!is_numeric($tripId)) {
            abort(400); // bad request
        }

        $trip = DB::selectOne("Select trips.*, 
        admins.name as creator_name,
        drivers.name as driver_name,
        routes.name as route_name
        from trips 
        LEFT JOIN routes ON trips.route_id = routes.id
        LEFT JOIN admins ON trips.created_by = admins.id
        LEFT JOIN drivers ON trips.driver_id = drivers.id
        where trips.id = ?", [$tripId]);
        if (!$trip) {
            return $this->errorResponse("Not Found", 404);
        }
        return response()->json([
            'success' => true,
            'data' => [
                'trip' => $trip
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            "route_id" => "required|integer|min:1",
            "driver_id" => "required|integer|min:1",
            "bus_id" => "required|integer|min:1",
            "departure_time" => "required|date_format:Y-m-d H:i:s",
            "price" => "required|numeric",
            "expected_duration" => "nullable|numeric",
            "golden_seat_number" => "nullable|integer|min:1",
        ]);

        if ($resp = $this->handleRouteIdErrors($request)) {
            return $resp;
        }

        if ($resp = $this->handleBusIdErrors($request)) {
            return $resp;
        }

        if ($resp = $this->handleDriverIdErrors($request)) {
            return $resp;
        }

        if ($resp = $this->handleSeatErrors($request)) {
            return $resp;
        }


        $inserted = DB::insert(
            "Insert INTO trips 
            (route_id, driver_id, bus_id, departure_time, price, expected_duration, golden_seat_number, created_by) 
            Values (:route_id, :driver_id, :bus_id, :departure_time, :price, :expected_duration, :golden_seat_number, :created_by)",
            [
                ":route_id" => $request->route_id,
                ":driver_id" => $request->driver_id,
                ":bus_id" => $request->bus_id,
                ":departure_time" => $request->departure_time,
                ":price" => $request->price,
                ":expected_duration" => $request->expected_duration,
                ":golden_seat_number" => $request->golden_seat_number,
                ":created_by" => $request->authenticated_id, // given by the custom auth middleware
            ]
        );

        if (!$inserted) {
            abort(500); // internal server error happened
        }

        $tripId = DB::getPdo()->lastInsertId(); // get the last insertion id

        $trip = DB::selectOne("Select trips.*, 
        admins.name as creator_name,
        drivers.name as driver_name,
        routes.name as route_name
        from trips 
        LEFT JOIN routes ON trips.route_id = routes.id
        LEFT JOIN admins ON trips.created_by = admins.id
        LEFT JOIN drivers ON trips.driver_id = drivers.id
        where trips.id = ?", [$tripId]);
        return response()->json([
            'success' => true,
            'data' => [
                'trip' => $trip
            ]
        ]);
    }


    public function update(Request $request, $tripId)
    {
        if (!is_numeric($tripId)) {
            abort(400); // bad request
        }

        $trip = DB::selectOne("Select id from trips where id = ?", [$tripId]);

        if (!$trip) {
            return $this->errorResponse("Not Found", 404);
        }

        $request->validate([
            "route_id" => "required|integer|min:1",
            "driver_id" => "required|integer|min:1",
            "bus_id" => "required|integer|min:1",
            "departure_time" => "required|date_format:Y-m-d H:i:s",
            "price" => "required|numeric",
            "expected_duration" => "nullable|numeric",
            "golden_seat_number" => "nullable|integer|min:1",
        ]);

        if ($resp = $this->handleRouteIdErrors($request, $tripId)) {
            return $resp;
        }

        if ($resp = $this->handleBusIdErrors($request, $tripId)) {
            return $resp;
        }

        if ($resp = $this->handleDriverIdErrors($request, $tripId)) {
            return $resp;
        }

        if ($resp = $this->handleSeatErrors($request, $tripId)) {
            return $resp;
        }

        DB::update("UPDATE trips SET route_id = :route_id, driver_id = :driver_id, bus_id = :bus_id,
         departure_time = :departure_time, price = :price,
          expected_duration = :expected_duration, golden_seat_number = :golden_seat_number
           WHERE id = :id", [
            ":id" => $tripId,
            ":route_id" => $request->route_id,
            ":driver_id" => $request->driver_id,
            ":bus_id" => $request->bus_id,
            ":departure_time" => $request->departure_time,
            ":price" => $request->price,
            ":expected_duration" => $request->expected_duration,
            ":golden_seat_number" => $request->golden_seat_number
        ]);

        $trip = DB::selectOne("Select trips.*, 
        admins.name as creator_name,
        drivers.name as driver_name,
        routes.name as route_name
        from trips 
        LEFT JOIN routes ON trips.route_id = routes.id
        LEFT JOIN admins ON trips.created_by = admins.id
        LEFT JOIN drivers ON trips.driver_id = drivers.id
        where trips.id = ?", [$tripId]);

        return response()->json([
            'success' => true,
            'data' => [
                'trip' => $trip
            ]
        ]);
    }

    public function delete($tripId)
    {
        if (!is_numeric($tripId)) {
            abort(400); // bad request
        }
        $deleted = DB::delete("DELETE FROM trips WHERE id = ?", [$tripId]);

        if ($deleted == 0) {
            return $this->errorResponse("Not found", 404);
        }

        return response()->json([
            'success' => true,
            'message' => "Deleted Succssfully"
        ]);
    }




    private function handleSeatErrors($request)
    {
        if (!$request->golden_seat_number) {
            return false;
        }
        // validate seat_number exists
        $result = DB::selectOne("select exists(select 1 from bus_seats where bus_id = ? AND seat_number = ?) as `exists`", [$request->bus_id, $request->golden_seat_number]);
        if (!$result->exists) {
            return $this->errorResponse(["golden_seat_number" => ["Seat number not found"]], 422); // validation error
        }
        return false;
    }

    private function handleRouteIdErrors($request)
    {
        // validate route_id exists
        $result = DB::selectOne("select exists(select 1 from routes where id = ?) as `exists`", [$request->route_id]);
        if (!$result->exists) {
            return $this->errorResponse(["route_id" => ["Route Not Found"]], 422); // validation error
        }
        return false;
    }


    private function handleDriverIdErrors($request, $tripId = 0)
    {
        // validate driver_id exists
        $result = DB::selectOne("select exists(select 1 from drivers where id = ?) as `exists`", [$request->driver_id]);
        if (!$result->exists) {
            return $this->errorResponse(["driver_id" => ["Driver Not Found"]], 422); // validation error
        }

        // check if the driver is available
        // check if he is assigned to another trip with departure time less than or equal the one I am processing
        // and have null arrival time i.e (ongoing)
        $bindings = [
            $request->driver_id,
            $request->departure_time,
        ];
        $availableQuery = "select * from trips where driver_id = ? AND arrival_time IS NULL AND departure_time <= ? ";
        if ($tripId) {
            $availableQuery .= "AND id != ?";
            $bindings[] = $tripId;
        }
        $result = DB::selectOne($availableQuery, $bindings);
        if ($result) {
            return $this->errorResponse(["driver_id" => ["Driver will be busy on another trip ( " . $result->id . " ) @ " . $result->departure_time]], 422); // validation error
        }
        // no errors
        return false;
    }

    private function handleBusIdErrors($request, $tripId = 0)
    {
        // validate driver_id exists
        $result = DB::selectOne("select exists(select 1 from buses where id = ?) as `exists`", [$request->bus_id]);
        if (!$result->exists) {
            return $this->errorResponse(["bus_id" => ["Bus Not Found"]], 422); // validation error
        }

        // check if the driver is available
        // check if he is assigned to another trip with departure time less than or equal the one I am processing
        // and have null arrival time i.e (ongoing)
        $bindings = [
            $request->bus_id,
            $request->departure_time,
        ];
        $availableQuery = "select * from trips where bus_id = ? AND arrival_time IS NULL AND departure_time <= ? ";
        if ($tripId) {
            $availableQuery .= "AND id != ?";
            $bindings[] = $tripId;
        }
        $result = DB::selectOne($availableQuery, $bindings);
        if ($result) {
            return $this->errorResponse(["bus_id" => ["Bus will be busy on another trip ( " . $result->id . " ) @ " . $result->departure_time]], 422); // validation error
        }
        // no errors
        return false;
    }
}
