<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CustomAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $guard): Response
    {
        if (!in_array($guard, ['customer', 'admin', 'driver'])) {
            abort(500);
        }
        $token = $request->bearerToken(); // gets the bearer token sent in authorization header
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => "Unauthorized"
            ], 403);
        }
        $results = DB::select("select " . $guard . "_id from " . $guard . "_tokens WHERE token='" . $token . "'");

        if (!$results) {
            return response()->json([
                'success' => false,
                'message' => "Unauthorized"
            ], 403);
        }

        $request->merge(['authenticated_id' => $results[0]->{$guard . "_id"}]);


        return $next($request);
    }
}
