<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripController extends Controller
{
    public function customerTrips(Request $request)
    {
        $trips = DB::select("Select 
        trips.id,
        trips.bus_id,
        trips.driver_id,
        trips.route_id,
        trips.departure_time,
        trips.actual_departure_time,
        trips.arrival_time,
        trips.price,
        trips.expected_duration,
        tickets.seat_number,
        buses.plate_number,
        buses.model,
        bus_categories.name as bus_category_name,
        drivers.name as driver_name,
        routes.name as route_name,
        IF(ISNULL(trips.actual_departure_time), NULL, trips.golden_seat_number) as golden_seat
        from tickets 
        LEFT JOIN trips ON trips.id = tickets.trip_id 
        LEFT JOIN routes ON trips.route_id = routes.id
        LEFT JOIN drivers ON trips.driver_id = drivers.id
        LEFT JOIN buses ON trips.bus_id = buses.id
        LEFT JOIN bus_categories ON bus_categories.id = buses.bus_category_id 
        where tickets.customer_id = ?", [$request->authenticated_id]);


        return response()->json([
            'success' => true,
            'data' => [
                'trips' => $trips
            ]
        ]);
    }


    public function find($tripId)
    {
        $trip = DB::selectOne("Select 
        trips.id,
        trips.bus_id,
        trips.driver_id,
        trips.route_id,
        trips.actual_departure_time,
        trips.departure_time,
        trips.price,
        trips.expected_duration,
        buses.plate_number,
        buses.model,
        bus_categories.id as bus_category_id,
        bus_categories.name as bus_category_name,
        drivers.name as driver_name,
        routes.name as route_name
        from trips 
        LEFT JOIN routes ON trips.route_id = routes.id
        LEFT JOIN drivers ON trips.driver_id = drivers.id
        LEFT JOIN buses ON trips.bus_id = buses.id
        LEFT JOIN bus_categories ON bus_categories.id = buses.bus_category_id
        WHERE trips.id = ?", [$tripId]);
        if (!$trip) {
            return $this->errorResponse("Not Found", 404);
        }
        unset($trip->golden_seat_number);

        $trip->route_stations = DB::select("call select_route_stations(?)", [$trip->route_id]);

        $trip->category_services = DB::select("Select services.name, services.id  from bus_category_service LEFT JOIN services ON services.id = bus_category_service.service_id where bus_category_service.bus_category_id = ? ", [$trip->bus_category_id]);

        $trip->seats = DB::select("select *, not exists(select seat_number from tickets where trip_id = ? AND tickets.seat_number = bus_seats.seat_number) as available from bus_seats where bus_id = ?", [$trip->id, $trip->bus_id]);
        return response()->json([
            'success' => true,
            'data' => [
                'trip' => $trip
            ]
        ]);
    }


    public function available(Request $request)
    {
        $request->validate([
            'from' => "nullable|date_format:Y-m-d",
            'to' => "nullable|date_format:Y-m-d",
            'route_id' => "nullable|integer|min:1",
        ]);

        if ($resp = $this->handleRouteIdErrors($request)) {
            return $resp;
        }

        $results = $this->getAvailableTrips($request);

        return response()->json([
            'success' => true,
            'data' => [
                'trips' => $results
            ],
        ]);
    }

    public function buyTicket(Request $request, $tripId)
    {

        $trip = DB::selectOne("Select * from trips where id = ?", [$tripId]);
        if (!$trip) {
            return $this->errorResponse("Not Found", 404);
        }

        $customer = DB::selectOne("Select * from customers where id = ?", [$request->authenticated_id]);

        $request->validate([
            "seat_number" => "required|integer|min:1",
            "payment_method" => "required|in:cash,visa",
        ]);

        if ($resp = $this->handleSeatNumberErrors($tripId, $request->seat_number)) {
            return $resp;
        }

        if ($customer->wallet_balance < $trip->price) {
            return $this->errorResponse('Insufficient Wallet Balance', 422);
        }

        try {
            DB::beginTransaction();
            $inserted = DB::insert(
                "Insert INTO tickets 
            (ticket_number, seat_number, trip_id, customer_id, payment_method ) 
            select COALESCE(max(ticket_number) + 1,1), :seat_number, :trip_id, :customer_id, :payment_method from tickets where trip_id = :tic_trip_id limit 1",
                [
                    ":seat_number" => $request->seat_number,
                    ":trip_id" => $tripId,
                    ":tic_trip_id" => $tripId,
                    ":customer_id" => $customer->id,
                    ":payment_method" => $request->payment_method,
                ]
            );
            if (!$inserted) {
                abort(500); // internal server error happened
            }
            DB::update("update customers set wallet_balance = wallet_balance - ? where id = ?", [$trip->price, $customer->id]);
            DB::commit();
            return response()->json([
                'success' => true,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function getReview(Request $request, $tripId)
    {
        // search for ended trips in the trips belonging to that user with the given id
        $trip = DB::selectOne("select * from trips WHERE id = ? AND arrival_time IS NOT NULL AND id in (select trip_id from tickets where customer_id = ?)
        ", [$tripId, $request->authenticated_id]);
        if (!$trip) {
            return $this->errorResponse("Not Found or trip is still ongoing", 404);
        }

        $review = DB::selectOne("select * from reviews where trip_id = ? AND customer_id = ?", [$trip->id, $request->authenticated_id]);
        if ($review) {
            unset($review->seen_at);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'review' => $review
            ]
        ]);
    }

    public function review(Request $request, $tripId)
    {
        // search for ended trips in the trips belonging to that user with the given id
        $trip = DB::selectOne("select * from trips WHERE id = ? AND arrival_time IS NOT NULL AND id in (select trip_id from tickets where customer_id = ?)
        ", [$tripId, $request->authenticated_id]);
        if (!$trip) {
            return $this->errorResponse("Not Found or trip is still ongoing", 404);
        }

        $request->validate([
            'stars' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:600',
        ]);

        $review = DB::selectOne("select * from reviews where trip_id = ? AND customer_id = ?", [$trip->id, $request->authenticated_id]);

        if ($review) {
            DB::update(
                "UPDATE reviews SET  stars = :stars, comment = :comment
           WHERE trip_id = :trip_id AND customer_id = :customer_id",
                [
                    ":stars" => $request->stars,
                    ":trip_id" => $tripId,
                    ":customer_id" => $request->authenticated_id,
                    ":comment" => $request->comment,
                ]
            );
        } else {
            DB::insert(
                "Insert INTO reviews 
            (customer_id, trip_id, stars, comment ) 
            VALUES (:customer_id, :trip_id, :stars, :comment)",
                [
                    ":stars" => $request->stars,
                    ":trip_id" => $tripId,
                    ":customer_id" => $request->authenticated_id,
                    ":comment" => $request->comment,
                ]
            );
        }


        return response()->json([
            'success' => true,
        ]);
    }

    private function getAvailableTrips($request)
    {
        $query = "Select 
        trips.id,
        trips.bus_id,
        trips.driver_id,
        trips.route_id,
        trips.departure_time,
        trips.price,
        trips.expected_duration,
        buses.plate_number,
        buses.model,
        bus_categories.name as bus_category_name,
        routes.name as route_name,
        (SELECT COUNT(*) FROM bus_seats WHERE trips.bus_id = bus_seats.bus_id) - (SELECT COUNT(*) FROM tickets WHERE tickets.trip_id = trips.id) as available_seats 
        from trips 
        LEFT JOIN routes ON trips.route_id = routes.id
        LEFT JOIN drivers ON trips.driver_id = drivers.id
        LEFT JOIN buses ON trips.bus_id = buses.id
        LEFT JOIN bus_categories ON bus_categories.id = buses.bus_category_id
        WHERE arrival_time IS NULL AND (SELECT COUNT(*) FROM tickets WHERE tickets.trip_id = trips.id) < (SELECT COUNT(*) FROM bus_seats WHERE trips.bus_id = bus_seats.bus_id) ";
        $conditionsStr = "";
        $bindings = [];
        if ($request->from) {
            $conditionsStr .= " departure_time >= ?";
            $bindings[] = $request->from;
        }

        if ($request->to) {
            if ($conditionsStr) {
                $conditionsStr .= " AND ";
            }
            $conditionsStr .= " departure_time <= ?";
            $bindings[] = $request->to;
        }

        if ($request->route_id) {
            if ($conditionsStr) {
                $conditionsStr .= " AND ";
            }
            $conditionsStr .= " route_id = ?";
            $bindings[] = $request->route_id;
        }

        $query .= $conditionsStr ? " AND " . $conditionsStr : "";
        return  DB::select($query, $bindings);
    }

    private function handleRouteIdErrors($request)
    {
        if (!$request->route_id) {
            return false;
        }
        // validate route_id exists
        $result = DB::selectOne("select exists(select 1 from routes where id = ?) as `exists`", [$request->route_id]);
        if (!$result->exists) {
            return $this->errorResponse(["route_id" => ["Route Not Found"]], 422); // validation error
        }
        return false;
    }


    private function handleSeatNumberErrors($tripId, $seatNumber)
    {
        // return available seats for this trip

        $found = DB::selectOne("Select bus_seats.seat_number
        from bus_seats
        inner join trips ON trips.bus_id = bus_seats.bus_id AND trips.id = ?
        where bus_seats.seat_number = ? AND bus_seats.seat_number not IN (
            Select seat_number from tickets where trip_id = ?  
        )", [
            $tripId,
            $seatNumber,
            $tripId,
        ]);
        if (!$found) {
            return $this->errorResponse(["seat_number" => ["This seat number is not available any more"]], 422); // validation error
        }
        return false;
    }
}
