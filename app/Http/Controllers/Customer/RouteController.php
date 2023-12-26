<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class RouteController extends Controller
{
    public function index()
    {
        $routes = DB::select("Select name, id  from routes");
        return response()->json([
            'success' => true,
            'data' => [
                'routes' => $routes
            ]
        ]);
    }


    public function find($routeId)
    {
        $route = DB::selectOne("Select name, id  from routes where id = ?", [$routeId]);
        if (!$route) {
            return $this->errorResponse("Not Found", 404);
        }

        $routeStations = DB::select("call select_route_stations(?)", [$routeId]);
        $route->stations = $routeStations;

        return response()->json([
            'success' => true,
            'data' => [
                'route' => $route
            ]
        ]);
    }
}
