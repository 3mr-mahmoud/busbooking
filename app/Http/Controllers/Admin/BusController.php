<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\DB;

class BusController extends Controller
{
    public function index()
    {
        $buses = DB::select("Select buses.*, 
        (select count(1) from bus_seats where bus_seats.bus_id = buses.id) as seats,
         admins.name as creator_name from buses 
         LEFT JOIN admins ON buses.created_by = admins.id");
        return response()->json([
            'success' => true,
            'data' => [
                'buses' => $buses
            ]
        ]);
    }

    public function find($busId)
    {
        if (!is_numeric($busId)) {
            abort(400); // bad request
        }

        $bus = DB::selectOne("Select buses.*,
         admins.name as creator_name from buses 
         LEFT JOIN admins ON buses.created_by = admins.id 
         where buses.id = ?", [$busId]);

        if (!$bus) {
            return $this->errorResponse("Not Found", 404);
        }

        $busSeats = DB::select("Select * from bus_seats where bus_id = ? ", [$busId]);
        $bus->seats = $busSeats;

        return response()->json([
            'success' => true,
            'data' => [
                'bus' => $bus
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            "plate_number" => "required|string|max:7",
            "capacity" => "nullable|numeric|min:0",
            "bus_category_id" => "required|integer|min:1",
            "seats" => "required|integer|min:1",
        ]);

        try {
            DB::beginTransaction();
            // validate bus_category_id exists
            $result = DB::selectOne("select exists(select 1 from bus_categories where id = ?) as `exists`", [$request->bus_category_id]);
            if (!$result->exists) {
                return $this->errorResponse(["bus_category_id" => ["Bus Category Not Found"]], 422); // validation error
            }

            // validate plate_number is not duplicated
            $result = DB::selectOne("select exists(select 1 from buses where plate_number = ?) as `exists`", [$request->plate_number]);
            if ($result->exists) {
                return $this->errorResponse(["plate_number" => ["Plate Number is used before"]], 422); // validation error
            }

            $inserted = DB::insert(
                "Insert INTO buses (plate_number, model, capacity, bus_category_id, created_by) 
                Values (:plate_number, :model, :capacity, :bus_category_id, :created_by)",
                [
                    ":plate_number" => $request->plate_number,
                    ":model" => $request->model,
                    ":capacity" => $request->capacity,
                    ":bus_category_id" => $request->bus_category_id,
                    ":created_by" => $request->authenticated_id, // given by the custom auth middleware
                ]
            );

            if (!$inserted) {
                return $this->errorResponse(["Internal Server Error"], 500); // internal server error happened
            }

            $busId = DB::getPdo()->lastInsertId(); // get the last insertion id

            $seatsQuery = $this->generateBusSeatsQuery($busId, $request->seats);

            $insertedSeats = DB::insert($seatsQuery);

            if (!$insertedSeats) {
                return $this->errorResponse(["Internal Server Error"], 500); // internal server error happened
            }

            $bus = DB::selectOne("Select buses.*,
         admins.name as creator_name from buses 
         LEFT JOIN admins ON buses.created_by = admins.id 
         where buses.id = ?", [$busId]);

            if (!$bus) {
                return $this->errorResponse("Not Found", 404);
            }

            $busSeats = DB::select("Select * from bus_seats where bus_id = ? ", [$busId]);
            $bus->seats = $busSeats;

            DB::commit();
            return response()->json([
                'success' => true,
                'data' => [
                    'bus' => $bus
                ]
            ]);
        } catch (Exception $e) {
            // if transaction failed discard database changes
            DB::rollBack();
            return $this->errorResponse($e->getMessage(), 500);
        }
    }


    private function generateBusSeatsQuery($busId, $seats, $oldSeatsNumber = 0)
    {
        $query = "INSERT INTO bus_seats ( bus_id, seat_number) VALUES ";
        $seat_number = 1;
        if ($oldSeatsNumber) {
            $seat_number = $oldSeatsNumber + 1;
        }
        for (; $seat_number <= $seats; $seat_number++) {

            $query .= "( " . $busId . ", " . $seat_number . ")";
            if ($seats != $seat_number) {
                $query .= ",";
            }
        }
        return  $query;
    }

    public function update(Request $request, $busId)
    {
        if (!is_numeric($busId)) {
            abort(400); // bad request
        }

        $bus = DB::selectOne("Select id, (select count(1) from bus_seats where bus_id = ?) as seats from buses where buses.id = ?", [$busId, $busId]);

        if (!$bus) {
            return $this->errorResponse("Not Found", 404);
        }

        $request->validate([
            "plate_number" => "required|string|max:7",
            "capacity" => "nullable|numeric|min:0",
            "bus_category_id" => "required|integer|min:1",
            "seats" => "required|integer|min:1",
        ]);

        // validate plate_number is not duplicated and ignore current plate number
        $result = DB::selectOne("select exists(select 1 from buses where plate_number = ? AND id != ?) as `exists`", [$request->plate_number, $busId]);
        if ($result->exists) {
            return $this->errorResponse(["plate_number" => ["Plate Number is used before"]], 422); // validation error
        }

        // validate bus_category_id exists
        $result = DB::selectOne("select exists(select 1 from bus_categories where id = ?) as `exists`", [$request->bus_category_id]);
        if (!$result->exists) {
            return $this->errorResponse(["bus_category_id" => ["Bus Category Not Found"]], 422); // validation error
        }

        try {
            DB::beginTransaction();
            // handle seats number change
            if ($request->seats < $bus->seats) {
                // delete if the seats number decrease
                DB::delete("DELETE FROM bus_seats WHERE bus_id = ? AND seat_number > ?", [$busId, $request->seats]);
            } elseif ($request->seats > $bus->seats) {
                $seatsQuery = $this->generateBusSeatsQuery($busId, $request->seats, $bus->seats);

                $insertedSeats = DB::insert($seatsQuery);

                if (!$insertedSeats) {
                    return $this->errorResponse(["Internal Server Error"], 500); // internal server error happened
                }
            }


            DB::update(
                "UPDATE buses  SET plate_number = :plate_number , model = :model , capacity = :capacity, bus_category_id = :bus_category_id WHERE id = :id",
                [
                    ":id" => $busId,
                    ":plate_number" => $request->plate_number,
                    ":model" => $request->model,
                    ":capacity" => $request->capacity,
                    ":bus_category_id" => $request->bus_category_id,
                ]
            );

            $bus = DB::selectOne("Select buses.*,
            admins.name as creator_name from buses 
            LEFT JOIN admins ON buses.created_by = admins.id 
            where buses.id = ?", [$busId]);

            if (!$bus) {
                return $this->errorResponse("Not Found", 404);
            }

            $busSeats = DB::select("Select * from bus_seats where bus_id = ? ", [$busId]);
            $bus->seats = $busSeats;

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'bus' => $bus
                ]
            ]);
        } catch (Exception $e) {
            // if transaction failed discard database changes
            DB::rollBack();
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function updateSeat(Request $request, $busId, $seatNumber)
    {
        if (!is_numeric($busId) || !is_numeric($seatNumber)) {
            abort(400); // bad request
        }

        $seat = DB::selectOne("Select * from bus_seats where bus_id = ? AND seat_number = ?", [$busId, $seatNumber]);

        if (!$seat) {
            return $this->errorResponse("Not Found", 404);
        }

        $request->validate([
            "note" => "nullable|string",
        ]);

        DB::update("UPDATE bus_seats SET note = :note  WHERE bus_id = :bus_id AND seat_number = :seat_number", [
            ":bus_id" => $busId,
            ":seat_number" => $seatNumber,
            ":note" => $request->note,
        ]);

        $seat = DB::selectOne("Select * from bus_seats where bus_id = ? AND seat_number = ?", [$busId, $seatNumber]);

        return response()->json([
            'success' => true,
            'data' => [
                'seat' => $seat
            ]
        ]);
    }

    public function delete($busId)
    {
        if (!is_numeric($busId)) {
            abort(400); // bad request
        }
        $deleted = DB::delete("DELETE FROM buses WHERE id = ?", [$busId]);
        if ($deleted == 0) {
            return $this->errorResponse("Not found", 404);
        }
        return response()->json([
            'success' => true,
            'message' => "Deleted Succssfully"
        ]);
    }
}
