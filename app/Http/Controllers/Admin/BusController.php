<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class StationController extends Controller
{
    public function index()
    {
        $buses = DB::select("Select buses.*, admins.name as creator_name from buses 
        LEFT JOIN admins ON buses.created_by = admins.id
        ");
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

        $bus = DB::selectOne("Select buses.*, admins.name as creator_name from buses LEFT JOIN admins ON buses.created_by = admins.id where buses.id = ?", [$busId]);

        if (!$station) {
            return $this->errorResponse("Not Found", 404);
        }
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
            "name" => "required|string",
            "phone" => "nullable|numeric",
        ]);
        $inserted = DB::insert(
            "Insert INTO buses (platenum, model, capacity, created_by) Values (:platenum, :model, :capacity, :created_by)",
            [
                ":platenum" => $request->platenum,
                ":model" => $request->model,
                ":capacity" => $request->capacity,
                ":created_by" => $request->authenticated_id, // given by the custom auth middleware
            ]
        );

        if (!$inserted) {
            abort(500); // internal server error happened
        }

        $insertedId = DB::getPdo()->lastInsertId(); // get the last insertion id

        $bus = DB::selectOne("Select buses.*, admins.name as creator_name from buses LEFT JOIN admins ON buses.created_by = admins.id where buses.id = ?", [$insertedId]);

        return response()->json([
            'success' => true,
            'data' => [
                'bus' => $bus
            ]
        ]);
    }

    public function update(Request $request, $busId)
    {
        if (!is_numeric($busId)) {
            abort(400); // bad request
        }
        $request->validate([
            "name" => "required|string",
            "phone" => "nullable|numeric",
        ]);

        DB::update("UPDATE buses SET platenum = :platenum , model = :model , capacity = :capacity WHERE id = :id", [
            ":id" => $busId,
            ":platenum" => $request->platenum,
            ":model" => $request->model,
            ":capacity" => $request->capacity,
        ]);

        $bus = DB::selectOne("Select buses.*, admins.name as creator_name from buses LEFT JOIN admins ON buses.created_by = admins.id where buses.id = ?", [$busId]);

        return response()->json([
            'success' => true,
            'data' => [
                'bus' => $bus
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
            return $this->errorResponse("Already Deleted", 404);
        }
        return response()->json([
            'success' => true,
            'message' => "Deleted Succssfully"
        ]);
    }
}
