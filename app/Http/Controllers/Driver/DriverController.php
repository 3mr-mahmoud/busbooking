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

    private function generatePreferencesInsertQuery($driverId, $stations)
    {
        $query = "INSERT INTO driver_preferences ( driver_id, station_id) VALUES ";
        $i = 1;
        foreach ($stations as $station) {
            $query .= "( " . $driverId . ", " . $station . ")";;
            if (count($stations) != $i) {
                $query .= ",";
            }
            $i++;
        }
        return  $query;
    }

    public function updateProfile(Request $request)
    {
        $driver = DB::selectOne("select * from drivers where id = ?", [$request->authenticated_id]);

        if (!$driver) {
            return $this->errorResponse("Not Found", 404);
        }

        $request->validate([
            'name' => 'required|string',
            'phone' => 'required|numeric',
            'email' => 'required|email',
            'password' => 'nullable|min:6',
            'national_id' => 'required|integer',
            'license_number' => 'required|integer',
            'city' => 'required|string',
            'stations' => 'nullable|array',
            'stations.*' => 'required|integer|min:1',
        ]);

        if ($resp = $this->handlePhoneErrors($request)) {
            return $resp;
        }

        if ($resp = $this->handleEmailErrors($request)) {
            return $resp;
        }

        if ($resp = $this->handleLicenseNumberErrors($request)) {
            return $resp;
        }

        if ($resp = $this->handleNationalIdErrors($request)) {
            return $resp;
        }

        if ($request->password && $request->password != "") {
            $password = Hash::make($request->password);
        } else {
            $password = $driver->password;
        }



        DB::update("UPDATE drivers SET name = :name, phone = :phone,
         email = :email, password = :password, national_id = :national_id, license_number = :license_number, city = :city
        WHERE id = :id
        ", [
            ":id" => $request->authenticated_id,
            ":name" => $request->name,
            ":phone" => $request->phone,
            ":email" => $request->email,
            ":national_id" => $request->national_id,
            ":license_number" => $request->license_number,
            ":city" => $request->city,
            ":password" => $password,
        ]);

        // delete and reinsert them again
        DB::delete("DELETE FROM driver_preferences WHERE driver_id = ?", [$request->authenticated_id]);
        // insert the driver preferences
        if ($request->stations) {
            $query = $this->generatePreferencesInsertQuery($request->authenticated_id, $request->stations);

            DB::insert($query);
        }


        return response()->json([
            'success' => true,
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


    private function handlePhoneErrors($request)
    {
        // check if phone is not used before
        // if I am updating ignore th current driverId
        $result = DB::selectOne("select * from drivers where phone = ? AND id != ? ", [$request->phone, $request->authenticated_id]);
        if ($result) {
            return $this->errorResponse(["phone" => ["Phone is used before are you (" . $result->name . ")"]], 422); // validation error
        }
        return false;
    }

    private function handleEmailErrors($request)
    {
        // check if email is not used before
        // if I am updating ignore th current driverId
        $result = DB::selectOne("select * from drivers where email = ? AND id != ?", [$request->email, $request->authenticated_id]);
        if ($result) {
            return $this->errorResponse(["email" => ["Email is used before are you (" . $result->name . ")"]], 422); // validation error
        }
        return false;
    }


    private function handleNationalIdErrors($request)
    {
        // check if national_id is not used before
        // if I am updating ignore th current driverId
        $result = DB::selectOne("select * from drivers where national_id = ? AND id != ?", [$request->national_id, $request->authenticated_id]);
        if ($result) {
            return $this->errorResponse(["national_id" => ["National Id is used before are you (" . $result->name . ")"]], 422); // validation error
        }
        return false;
    }


    private function handleLicenseNumberErrors($request)
    {
        // check if license_number is not used before
        // if I am updating ignore th current driverId
        $result = DB::selectOne("select * from drivers where license_number = ? AND id != ?", [$request->license_number, $request->authenticated_id]);
        if ($result) {
            return $this->errorResponse(["license_number" => ["License Number is used before are you (" . $result->name . ")"]], 422); // validation error
        }
        return false;
    }
}
