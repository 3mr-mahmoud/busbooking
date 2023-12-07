<?php

namespace App\Http\Controllers\Driver;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;

class DriverController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);

        $driver = DB::selectOne("select * from drivers where email = ?", [$request->email]);

        if (!$driver) {
            return $this->errorResponse(['email' => ['Provided Credentials are incorrect']]);
        }

        if (!Hash::check($request->password, $driver->password)) {
            return $this->errorResponse(['password' => ['Provided Credentials are incorrect']]);
        }

        $token =  Str::random(255);

        $inserted = DB::insert("INSERT INTO driver_tokens (driver_id,token) VALUES (:driver_id,:token)", [
            ":driver_id" => $driver->id,
            ":token" => $token,
        ]);

        if (!$inserted) {
            abort(500);
        }


        unset($driver->password);

        return response()->json([
            "success" => true,
            "data" => [
                'driver' => $driver,
                'token' => $token
            ]
        ]);
    }
    public function me(Request $request)
    {
        $driver = DB::selectOne("select * from drivers where id = " . $request->authenticated_id);
        unset($driver->password);
        return response()->json([
            "success" => true,
            "data" => $driver
        ]);
    }

    public function logout(Request $request)
    {
        $tokendeleted = DB::delete("delete from driver_tokens where driver_id = ? AND token = ?", [
            $request->authenticated_id,
            $request->bearerToken()
        ]);

        if (!$tokendeleted)
            abort(500);

        return response()->json([
            "success" => true,
            "message" => "logged out succesfully"
        ]);
    }
}
