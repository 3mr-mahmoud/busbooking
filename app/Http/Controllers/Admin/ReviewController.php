<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function index($tripId)
    {
        $reviews = DB::select("select reviews.*,
        trips.departure_time,
        routes.name as route_name,
        customers.name as customer_name
        from reviews 
        LEFT JOIN trips ON trips.id = reviews.trip_id
        LEFT JOIN routes ON routes.id = trips.route_id
        LEFT JOIN customers ON customers.id = reviews.customer_id
        WHERE reviews.trip_id = ?
        ORDER BY ISNULL(reviews.seen_at) desc", [$tripId]);

        return response()->json([
            'success' => true,
            'data' => [
                'reviews' => $reviews
            ]
        ]);
    }

    public function find($tripId, $customerId)
    {
        $review = DB::selectOne("select reviews.*,
        trips.departure_time,
        routes.name as route_name,
        customers.name as customer_name
        from reviews 
        LEFT JOIN trips ON trips.id = reviews.trip_id
        LEFT JOIN routes ON routes.id = trips.route_id
        LEFT JOIN customers ON customers.id = reviews.customer_id
        WHERE reviews.trip_id = ? AND reviews.customer_id = ? ", [
            $tripId,
            $customerId
        ]);

        if (!$review) {
            return $this->errorResponse("Not Found", 404);
        }

        DB::update("UPDATE reviews SET seen_at = NOW() WHERE trip_id = ? AND customer_id = ? AND seen_at IS NULL", [
            $tripId,
            $customerId
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'review' => $review
            ]
        ]);
    }
}
