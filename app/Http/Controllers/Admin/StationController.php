<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class StationController extends Controller
{
    public function index()
    {
        $stations = DB::select("Select stations.*, admins.name as creator_name from stations 
        LEFT JOIN admins ON stations.created_by = admins.id
        ");
        return response()->json([
            'success' => true,
            'data' => [
                'stations' => $stations
            ]
        ]);
    }

    public function find($stationId)
    {


        $station = DB::selectOne("Select stations.*, admins.name as creator_name from stations LEFT JOIN admins ON stations.created_by = admins.id where stations.id = ?", [$stationId]);

        if (!$station) {
            return $this->errorResponse("Not Found", 404);
        }
        return response()->json([
            'success' => true,
            'data' => [
                'station' => $station
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
            "Insert INTO stations (name, description, phone, created_by) Values (:name, :description, :phone, :created_by)",
            [
                ":name" => $request->name,
                ":description" => $request->description,
                ":phone" => $request->phone,
                ":created_by" => $request->authenticated_id, // given by the custom auth middleware
            ]
        );

        if (!$inserted) {
            abort(500); // internal server error happened
        }

        $insertedId = DB::getPdo()->lastInsertId(); // get the last insertion id

        $station = DB::selectOne("Select stations.*, admins.name as creator_name from stations LEFT JOIN admins ON stations.created_by = admins.id where stations.id = ?", [$insertedId]);

        return response()->json([
            'success' => true,
            'data' => [
                'station' => $station
            ]
        ]);
    }

    public function update(Request $request, $stationId)
    {

        $request->validate([
            "name" => "required|string",
            "phone" => "nullable|numeric",
        ]);

        DB::update("UPDATE stations SET name = :name , description = :description , phone = :phone WHERE id = :id", [
            ":id" => $stationId,
            ":name" => $request->name,
            ":description" => $request->description,
            ":phone" => $request->phone,
        ]);

        $station = DB::selectOne("Select stations.*, admins.name as creator_name from stations LEFT JOIN admins ON stations.created_by = admins.id where stations.id = ?", [$stationId]);

        return response()->json([
            'success' => true,
            'data' => [
                'station' => $station
            ]
        ]);
    }

    public function delete($stationId)
    {

        $deleted = DB::delete("DELETE FROM stations WHERE id = ?", [$stationId]);
        if ($deleted == 0) {
            return $this->errorResponse("Not found", 404);
        }
        return response()->json([
            'success' => true,
            'message' => "Deleted Succssfully"
        ]);
    }
}
