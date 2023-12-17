<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RouteController extends Controller
{
    public function index()
    {
        $routes = DB::select("Select routes.*, admins.name as creator_name from routes 
        LEFT JOIN admins ON routes.created_by = admins.id
        ");
        return response()->json([
            'success' => true,
            'data' => [
                'routes' => $routes
            ]
        ]);
    }

    public function find($routeId)
    {
        $route = DB::selectOne("Select routes.*, admins.name as creator_name from routes LEFT JOIN admins ON routes.created_by = admins.id where routes.id = ?", [$routeId]);
        if (!$route) {
            return $this->errorResponse("Not Found", 404);
        }

        $routeStations = DB::select("Select stations.name, stations.id ,route_station.`order`  from route_station LEFT JOIN stations ON stations.id = route_station.station_id where route_station.route_id = ? ORDER BY route_station.`order` asc", [$routeId]);
        $route->stations = $routeStations;

        return response()->json([
            'success' => true,
            'data' => [
                'route' => $route
            ]
        ]);
    }

    private function generateStationsInsertQuery($routeId, $stations)
    {
        $query = "INSERT INTO route_station ( route_id, station_id, `order`) VALUES ";
        $i = 1;
        foreach ($stations as $station) {
            $query .= "( " . $routeId . ", " . $station['id'] . ", " . $station['order'] . ")";;
            if (count($stations) != $i) {
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
            "stations" => "required|array",
            "stations.*" => "required|array",
            "stations.*.*" => "required|integer",
        ]);

        try {
            DB::beginTransaction();

            $inserted = DB::insert(
                "Insert INTO routes (name, created_by) Values (:name, :created_by)",
                [
                    ":name" => $request->name,
                    ":created_by" => $request->authenticated_id, // given by the custom auth middleware
                ]
            );

            if (!$inserted) {
                abort(500); // internal server error happened
            }

            $routeId = DB::getPdo()->lastInsertId(); // get the last insertion id

            // insert the route stations
            $query = $this->generateStationsInsertQuery($routeId, $request->stations);

            DB::insert($query);

            DB::commit();

            $route = DB::selectOne("Select routes.*, admins.name as creator_name from routes LEFT JOIN admins ON routes.created_by = admins.id where routes.id = ?", [$routeId]);
            $routeStations = DB::select("Select stations.name, stations.id ,route_station.`order`  from route_station LEFT JOIN stations ON stations.id = route_station.station_id where route_station.route_id = ? ORDER BY route_station.`order` asc", [$routeId]);
            $route->stations = $routeStations;

            return response()->json([
                'success' => true,
                'data' => [
                    'route' => $route
                ]
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function update(Request $request, $routeId)
    {
        $request->validate([
            "name" => "required|string",
        ]);

        DB::update("UPDATE routes SET name = :name  WHERE id = :id", [
            ":id" => $routeId,
            ":name" => $request->name,
        ]);

        $route = DB::selectOne("Select routes.*, admins.name as creator_name from routes LEFT JOIN admins ON routes.created_by = admins.id where routes.id = ?", [$routeId]);
        if ($route) {
            $routeStations = DB::select("Select stations.name, stations.id ,route_station.`order`  from route_station LEFT JOIN stations ON stations.id = route_station.station_id where route_station.route_id = ? ORDER BY route_station.`order` asc", [$routeId]);
            $route->stations = $routeStations;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'route' => $route
            ]
        ]);
    }

    public function updateStations(Request $request, $routeId)
    {
        $request->validate([
            "stations" => "required|array",
            "stations.*" => "required|array",
            "stations.*.*" => "required|integer",
        ]);
        $result = DB::selectOne("select exists(select 1 from routes where id = ?) as `exists`", [$routeId]);
        if (!$result->exists) {
            return $this->errorResponse("Route Not Found", 404);
        }
        // delete them and reinsert them again

        DB::delete("DELETE FROM route_station WHERE route_id = ?", [$routeId]);
        // insert the route stations
        $query = $this->generateStationsInsertQuery($routeId, $request->stations);

        DB::insert($query);

        $route = DB::selectOne("Select routes.*, admins.name as creator_name from routes LEFT JOIN admins ON routes.created_by = admins.id where routes.id = ?", [$routeId]);
        $routeStations = DB::select("Select stations.name, stations.id ,route_station.`order`  from route_station LEFT JOIN stations ON stations.id = route_station.station_id where route_station.route_id = ? ORDER BY route_station.`order` asc", [$routeId]);
        $route->stations = $routeStations;

        return response()->json([
            'success' => true,
            'data' => [
                'route' => $route
            ]
        ]);
    }

    public function delete($routeId)
    {
        if (!is_numeric($routeId)) {
            abort(400); // bad request
        }
        $deleted = DB::delete("DELETE FROM routes WHERE id = ?", [$routeId]);
        if ($deleted == 0) {
            return $this->errorResponse("Not found", 404);
        }
        return response()->json([
            'success' => true,
            'message' => "Deleted Succssfully"
        ]);
    }
}
