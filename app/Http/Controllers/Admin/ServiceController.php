<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    public function index()
    {
        $services = DB::select("Select services.*, admins.name as creator_name from services 
        LEFT JOIN admins ON services.created_by = admins.id
        ");
        return response()->json([
            'success' => true,
            'data' => [
                'services' => $services
            ]
        ]);
    }

    public function find($serviceId)
    {


        $service = DB::selectOne("Select services.*, admins.name as creator_name from services LEFT JOIN admins ON services.created_by = admins.id where services.id = ?", [$serviceId]);

        if (!$service) {
            return $this->errorResponse("Not Found", 404);
        }
        return response()->json([
            'success' => true,
            'data' => [
                'service' => $service
            ]
        ]);
    }

    public function store(Request $request)
    {

        $request->validate([
            "name" => "required|string",
        ]);
        $inserted = DB::insert(
            "Insert INTO services (name, created_by) Values (:name , :created_by)",
            [
                ":name" => $request->name,
                ":created_by" => $request->authenticated_id, // given by the custom auth middleware
            ]
        );

        if (!$inserted) {
            abort(500); // internal server error happened
        }

        $insertedId = DB::getPdo()->lastInsertId(); // get the last insertion id

        $service = DB::selectOne("Select services.*, admins.name as creator_name from services LEFT JOIN admins ON services.created_by = admins.id where services.id = ?", [$insertedId]);

        return response()->json([
            'success' => true,
            'data' => [
                'service' => $service
            ]
        ]);
    }

    public function update(Request $request, $serviceId)
    {

        $request->validate([
            "name" => "required|string",
        ]);

        DB::update("UPDATE services SET name = :name  WHERE id = :id", [
            ":id" => $serviceId,
            ":name" => $request->name,
        ]);

        $service = DB::selectOne("Select services.*, admins.name as creator_name from services LEFT JOIN admins ON services.created_by = admins.id where services.id = ?", [$serviceId]);

        return response()->json([
            'success' => true,
            'data' => [
                'service' => $service
            ]
        ]);
    }

    public function delete($serviceId)
    {

        $deleted = DB::delete("DELETE FROM services WHERE id = ?", [$serviceId]);
        if ($deleted == 0) {
            return $this->errorResponse("Not found", 404);
        }
        return response()->json([
            'success' => true,
            'message' => "Deleted Succssfully"
        ]);
    }
}
