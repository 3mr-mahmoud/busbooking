<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BusCategoryController extends Controller
{
    public function index()
    {
        $busCategories = DB::select("Select bus_categories.*, admins.name as creator_name from bus_categories 
        LEFT JOIN admins ON bus_categories.created_by = admins.id
        ");
        return response()->json([
            'success' => true,
            'data' => [
                'bus_categories' => $busCategories
            ]
        ]);
    }

    public function find($busCategoryId)
    {
        $busCategory = DB::selectOne("Select bus_categories.*, admins.name as creator_name from bus_categories LEFT JOIN admins ON bus_categories.created_by = admins.id where bus_categories.id = ?", [$busCategoryId]);
        if (!$busCategory) {
            return $this->errorResponse("Not Found", 404);
        }

        $busCategoryServices = DB::select("Select services.name, services.id  from bus_category_service LEFT JOIN services ON services.id = bus_category_service.service_id where bus_category_service.bus_category_id = ? ", [$busCategoryId]);
        $busCategory->services = $busCategoryServices;

        return response()->json([
            'success' => true,
            'data' => [
                'bus_category' => $busCategory
            ]
        ]);
    }

    private function generateServicesInsertQuery($busCategoryId, $services)
    {
        $query = "INSERT INTO bus_category_service ( bus_category_id, service_id) VALUES ";
        $i = 1;
        foreach ($services as $service) {
            $query .= "( " . $busCategoryId . ", " . $service . ")";;
            if (count($services) != $i) {
                $query .= ",";
            }
            $i++;
        }
        return  $query;
    }

    public function store(Request $request)
    {
        $request->validate([
            "name" => "required|string",
            "services" => "required|array",
            "services.*" => "required|integer",
        ]);

        try {
            DB::beginTransaction();

            $inserted = DB::insert(
                "Insert INTO bus_categories (name, created_by) Values (:name, :created_by)",
                [
                    ":name" => $request->name,
                    ":created_by" => $request->authenticated_id, // given by the custom auth middleware
                ]
            );

            if (!$inserted) {
                abort(500); // internal server error happened
            }

            $busCategoryId = DB::getPdo()->lastInsertId(); // get the last insertion id

            // insert the bus category services
            $query = $this->generateServicesInsertQuery($busCategoryId, $request->services);

            DB::insert($query);



            $busCategory = DB::selectOne("Select bus_categories.*, admins.name as creator_name from bus_categories LEFT JOIN admins ON bus_categories.created_by = admins.id where bus_categories.id = ?", [$busCategoryId]);
            $busCategoryServices = DB::select("Select services.name, services.id   from bus_category_service LEFT JOIN services ON services.id = bus_category_service.service_id where bus_category_service.bus_category_id = ?", [$busCategoryId]);
            $busCategory->services = $busCategoryServices;

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'bus_category' => $busCategory
                ]
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function update(Request $request, $busCategoryId)
    {
        $request->validate([
            "name" => "required|string",
            "services" => "required|array",
            "services.*" => "required|integer",
        ]);
        try {

            DB::beginTransaction();

            $result = DB::selectOne("select exists(select 1 from bus_categories where id = ?) as `exists`", [$busCategoryId]);
            if (!$result->exists) {
                return $this->errorResponse("Bus Category Not Found", 404);
            }

            DB::update("UPDATE bus_categories SET name = :name  WHERE id = :id", [
                ":id" => $busCategoryId,
                ":name" => $request->name,
            ]);

            // delete and reinsert them again
            DB::delete("DELETE FROM bus_category_service WHERE bus_category_id = ?", [$busCategoryId]);
            // insert the bus category services
            $query = $this->generateServicesInsertQuery($busCategoryId, $request->services);

            DB::insert($query);

            $busCategory = DB::selectOne("Select bus_categories.*, admins.name as creator_name from bus_categories LEFT JOIN admins ON bus_categories.created_by = admins.id where bus_categories.id = ?", [$busCategoryId]);

            if ($busCategory) {
                $busCategoryServices = DB::select("Select services.name, services.id   from bus_category_service LEFT JOIN services ON services.id = bus_category_service.service_id where bus_category_service.bus_category_id = ? ", [$busCategoryId]);
                $busCategory->services = $busCategoryServices;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'bus_category' => $busCategory
                ]
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function delete($busCategoryId)
    {
        if (!is_numeric($busCategoryId)) {
            abort(400); // bad request
        }
        $deleted = DB::delete("DELETE FROM bus_categories WHERE id = ?", [$busCategoryId]);
        if ($deleted == 0) {
            return $this->errorResponse("Already Deleted", 404);
        }
        return response()->json([
            'success' => true,
            'message' => "Deleted Succssfully"
        ]);
    }
}
