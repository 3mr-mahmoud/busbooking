<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function tripTickets($tripId)
    {
        return $this->index($tripId);
    }

    public function index($tripId = 0)
    {
        $bindings = [];
        $ticketsQuery = "Select tickets.*, 
        customers.name as customer_name,
        buses.capacity,
        buses.plate_number,
        trips.bus_id,
        trips.departure_time,
        trips.expected_duration,
        IF(tickets.seat_number = trips.golden_seat_number, 1, 0) as is_golden
        from tickets 
        LEFT JOIN trips ON trips.id = tickets.trip_id
        LEFT JOIN customers ON tickets.customer_id = customers.id
        LEFT JOIN buses ON trips.bus_id = buses.id
        LEFT JOIN bus_seats ON trips.bus_id = bus_seats.bus_id AND tickets.seat_number = bus_seats.seat_number ";
        if ($tripId) {
            $ticketsQuery .= " WHERE tickets.trip_id = ?";
            $bindings[] = $tripId;
        }

        $tickets = DB::select($ticketsQuery, $bindings);

        return response()->json([
            'success' => true,
            'data' => [
                'tickets' => $tickets
            ]
        ]);
    }

    public function find($tripId, $ticketNumber)
    {


        $ticket = DB::selectOne("Select tickets.*, 
        customers.name as customer_name,
        buses.capacity,
        buses.plate_number,
        trips.bus_id,
        trips.departure_time,
        trips.expected_duration,
        IF(tickets.seat_number = trips.golden_seat_number, 1, 0) as is_golden
        from tickets 
        LEFT JOIN trips ON trips.id = tickets.trip_id
        LEFT JOIN customers ON tickets.customer_id = customers.id
        LEFT JOIN buses ON trips.bus_id = buses.id
        LEFT JOIN bus_seats ON trips.bus_id = bus_seats.bus_id AND tickets.seat_number = bus_seats.seat_number
        where tickets.trip_id = ? AND tickets.ticket_number = ?", [$tripId, $ticketNumber]);
        if (!$ticket) {
            return $this->errorResponse("Not Found", 404);
        }
        return response()->json([
            'success' => true,
            'data' => [
                'ticket' => $ticket
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            "seat_number" => "required|integer|min:1",
            "trip_id" => "required|integer|min:1",
            "customer_id" => "required|integer|min:1",
            "payment_method" => "required|in:cash,visa",
        ]);

        if ($resp = $this->handleTripIdErrors($request)) {
            return $resp;
        }

        if ($resp = $this->handleCustomerIdErrors($request)) {
            return $resp;
        }

        if ($resp = $this->handleSeatNumberErrors($request)) {
            return $resp;
        }

        $inserted = DB::insert(
            "Insert INTO tickets 
            (ticket_number, seat_number, trip_id, customer_id, payment_method ) 
            select COALESCE(max(ticket_number) + 1,1), :seat_number, :trip_id, :customer_id, :payment_method from tickets where trip_id = :tic_trip_id limit 1",
            [
                ":seat_number" => $request->seat_number,
                ":trip_id" => $request->trip_id,
                ":tic_trip_id" => $request->trip_id,
                ":customer_id" => $request->customer_id,
                ":payment_method" => $request->payment_method,
            ]
        );

        if (!$inserted) {
            abort(500); // internal server error happened
        }


        return $this->find($request->trip_id, $request->seat_number);
    }


    public function update(Request $request, $tripId, $ticketNumber)
    {


        $ticket = DB::selectOne("Select * from tickets where trip_id = ? AND ticket_number = ? ", [$tripId, $ticketNumber]);

        if (!$ticket) {
            return $this->errorResponse("Not Found", 404);
        }

        $request->validate([
            "seat_number" => "required|integer|min:1",
            "trip_id" => "required|integer|min:1",
            "customer_id" => "required|integer|min:1",
            "payment_method" => "required|in:cash,visa",
        ]);

        if ($resp = $this->handleTripIdErrors($request)) {
            return $resp;
        }

        if ($resp = $this->handleCustomerIdErrors($request)) {
            return $resp;
        }

        if ($resp = $this->handleSeatNumberErrors($request, $ticket->seat_number)) {
            return $resp;
        }

        DB::update("UPDATE tickets SET seat_number = :seat_number, trip_id = :trip_id, customer_id = :customer_id,
         payment_method = :payment_method
         WHERE trip_id = :old_trip_id AND ticket_number = :old_ticket_number ", [
            ":old_trip_id" => $tripId,
            ":old_ticket_number" => $ticketNumber,
            ":seat_number" => $request->seat_number,
            ":trip_id" => $request->trip_id,
            ":customer_id" => $request->customer_id,
            ":payment_method" => $request->payment_method
        ]);

        return $this->find($request->trip_id, $ticketNumber);
    }

    public function delete($tripId, $ticketNumber)
    {

        $deleted = DB::delete("DELETE FROM tickets WHERE trip_id = ? AND ticket_number = ?", [$tripId, $ticketNumber]);

        if ($deleted == 0) {
            return $this->errorResponse("Not found", 404);
        }

        return response()->json([
            'success' => true,
            'message' => "Deleted Succssfully"
        ]);
    }




    private function handleSeatNumberErrors($request, $seatNumber = 0)
    {
        // return available seats for this trip

        $bindings = [
            $request->trip_id,
            $request->seat_number,
            $request->trip_id,
        ];
        $ignoreClause = "";
        // if I have seatNumber then I am updating I should return the current ticket seat number as availabe
        if ($seatNumber) {
            $ignoreClause = " AND seat_number != ?";
            $bindings[] = $seatNumber;
        }

        $availableSeatQuery = "Select bus_seats.seat_number
        from bus_seats
        inner join trips ON trips.bus_id = bus_seats.bus_id AND trips.id = ?
        where bus_seats.seat_number = ? AND bus_seats.seat_number not IN (
            Select seat_number from tickets where trip_id = ? " . $ignoreClause . "  
        )";

        $found = DB::selectOne($availableSeatQuery, $bindings);
        if (!$found) {
            return $this->errorResponse(["seat_number" => ["This seat number is not available any more"]], 422); // validation error
        }
        return false;
    }

    private function handleCustomerIdErrors($request)
    {
        // validate route_id exists
        $result = DB::selectOne("select exists(select 1 from customers where id = ?) as `exists`", [$request->customer_id]);
        if (!$result->exists) {
            return $this->errorResponse(["customer_id" => ["Customer Not found"]], 422); // validation error
        }
        return false;
    }


    private function handleTripIdErrors($request)
    {
        // validate trip_id exists
        $result = DB::selectOne("select exists(select 1 from trips where id = ?) as `exists`", [$request->trip_id]);
        if (!$result->exists) {
            return $this->errorResponse(["trip_id" => ["Trip Not Found"]], 422); // validation error
        }
        return false;
    }
}
