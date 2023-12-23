<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getStats()
    {
        // 5 aggregate
        $averageTripsPrice = DB::selectOne("select avg(price) as avg_price from trips")->avg_price;
        $ticketsCount = DB::selectOne("select count(*) as tickets_total from tickets")->tickets_total;
        $customersCount = DB::selectOne("select count(*) as customers_count from customers")->customers_count;
        $sales = DB::selectOne("select sum(trips.price) as sales from tickets left join trips on trips.id = tickets.trip_id")->sales;
        $driversSalary = DB::selectOne("select sum(salary) as salary_total from drivers")->salary_total;
        // 2 detailed
        $routeTrips = DB::select("select 
        routes.name as route_name,
        count(*) as trips_count,
        (select count(*) from route_station where route_station.route_id = routes.id) as stations_count
        from trips
        LEFT JOIN routes ON trips.route_id = routes.id
        group by trips.route_id
        ORDER BY trips_count desc");

        $tripTickets = DB::select("select 
        tickets.trip_id,
        trips.departure_time,
        routes.name as route_name,
        count(*) as tickets_count
        from tickets
        LEFT JOIN trips ON trips.id = tickets.trip_id
        LEFT JOIN routes ON trips.route_id = routes.id
        group by tickets.trip_id
        ORDER BY tickets_count desc");
        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'average_trips_price' => number_format($averageTripsPrice, 2),
                    'tickets_count' => $ticketsCount,
                    'customers_count' => $customersCount,
                    'sales' => number_format($sales, 2),
                    'drivers_salary' => number_format($driversSalary, 2),
                ],
                'route_trips' => $routeTrips,
                'trip_tickets' => $tripTickets,
            ]
        ]);
    }
}
