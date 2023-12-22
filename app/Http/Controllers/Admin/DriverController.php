<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DriverController extends Controller
{
    public function index()
    {

        $drivers = DB::select("select 
        drivers.id, 
        drivers.name, 
        drivers.phone, 
        drivers.email, 
        drivers.license_number, 
        drivers.city, 
        drivers.salary, 
        drivers.national_id, 
        drivers.    created_at, 
        admins.name as creator_name 
        from drivers
        LEFT JOIN admins ON admins.id = drivers.created_by");
        return response()->json([
            "success" => true,
            "data" => [
                'drivers' => $drivers
            ]
        ]);
    }

    public function find($driverId)
    {

        $driver = DB::selectOne("
        select drivers.*, admins.name as creator_name from drivers 
        LEFT JOIN admins ON admins.id = drivers.created_by
        where drivers.id = ? ", [$driverId]);
        unset($driver->password);
        return response()->json([
            "success" => true,
            "data" => [
                'driver' => $driver
            ]
        ]);
    }

    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required|string',
            'phone' => 'required|numeric',
            'email' => 'required|email',
            'password' => 'required|min:6',
            'salary' => 'required|numeric',
            'national_id' => 'required|integer',
            'license_number' => 'required|integer',
            'city' => 'required|string',
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

        $hashedPassword = Hash::make($request->password);

        $inserted = DB::insert("INSERT INTO drivers (name, phone, email, password, national_id, license_number, city, salary, created_by) 
        VALUES (:name, :phone, :email, :password, :national_id, :license_number, :city, :salary, :created_by)", [
            ":name" => $request->name,
            ":phone" => $request->phone,
            ":email" => $request->email,
            ":password" => $hashedPassword,
            ":national_id" => $request->national_id,
            ":license_number" => $request->license_number,
            ":city" => $request->city,
            ":salary" => $request->salary,
            ":created_by" => $request->authenticated_id,
        ]);

        return response()->json([
            'success' => $inserted,
        ]);
    }


    public function update(Request $request, $driverId)
    {
        $driver = DB::selectOne("select * from drivers where id = ?", [$driverId]);

        if (!$driver) {
            return $this->errorResponse("Not Found", 404);
        }

        $request->validate([
            'name' => 'required|string',
            'phone' => 'required|numeric',
            'email' => 'required|email',
            'password' => 'nullable|min:6',
            'salary' => 'required|numeric',
            'national_id' => 'required|integer',
            'license_number' => 'required|integer',
            'city' => 'required|string',
        ]);

        if ($resp = $this->handlePhoneErrors($request, $driverId)) {
            return $resp;
        }

        if ($resp = $this->handleEmailErrors($request, $driverId)) {
            return $resp;
        }

        if ($resp = $this->handleLicenseNumberErrors($request, $driverId)) {
            return $resp;
        }

        if ($resp = $this->handleNationalIdErrors($request, $driverId)) {
            return $resp;
        }



        if ($request->password && $request->password != "") {
            $password = Hash::make($request->password);
        } else {
            $password = $driver->password;
        }

        DB::update("UPDATE drivers SET name = :name, phone = :phone,
         email = :email, password = :password, national_id = :national_id, license_number = :license_number, city = :city, salary = :salary
        WHERE id = :id
        ", [
            ":id" => $driverId,
            ":name" => $request->name,
            ":phone" => $request->phone,
            ":email" => $request->email,
            ":national_id" => $request->national_id,
            ":license_number" => $request->license_number,
            ":city" => $request->city,
            ":salary" => $request->salary,
            ":password" => $password,
        ]);

        return response()->json([
            'success' => true,
        ]);
    }

    public function delete($driverId)
    {

        DB::delete("delete from drivers where id = ?", [$driverId]);


        return response()->json([
            'success' => true,
        ]);
    }


    private function handlePhoneErrors($request, $driverId = 0)
    {
        // check if phone is not used before
        // if I am updating ignore th current driverId
        $bindings = [$request->phone];
        $ignoreDriver = "";
        if ($driverId) {
            $ignoreDriver = " AND id != ?";
            $bindings[] = $driverId;
        }
        $result = DB::selectOne("select * from drivers where phone = ? " . $ignoreDriver, $bindings);
        if ($result) {
            return $this->errorResponse(["phone" => ["Phone is used before are you (" . $result->name . ")"]], 422); // validation error
        }
        return false;
    }

    private function handleEmailErrors($request, $driverId = 0)
    {
        // check if email is not used before
        // if I am updating ignore th current driverId
        $bindings = [$request->email];
        $ignoreDriver = "";
        if ($driverId) {
            $ignoreDriver = " AND id != ?";
            $bindings[] = $driverId;
        }
        $result = DB::selectOne("select * from drivers where email = ? " . $ignoreDriver, $bindings);
        if ($result) {
            return $this->errorResponse(["email" => ["Email is used before are you (" . $result->name . ")"]], 422); // validation error
        }
        return false;
    }


    private function handleNationalIdErrors($request, $driverId = 0)
    {
        // check if national_id is not used before
        // if I am updating ignore th current driverId
        $bindings = [$request->national_id];
        $ignoreDriver = "";
        if ($driverId) {
            $ignoreDriver = " AND id != ?";
            $bindings[] = $driverId;
        }
        $result = DB::selectOne("select * from drivers where national_id = ? " . $ignoreDriver, $bindings);
        if ($result) {
            return $this->errorResponse(["national_id" => ["National Id is used before are you (" . $result->name . ")"]], 422); // validation error
        }
        return false;
    }


    private function handleLicenseNumberErrors($request, $driverId = 0)
    {
        // check if license_number is not used before
        // if I am updating ignore th current driverId
        $bindings = [$request->license_number];
        $ignoreDriver = "";
        if ($driverId) {
            $ignoreDriver = " AND id != ?";
            $bindings[] = $driverId;
        }
        $result = DB::selectOne("select * from drivers where license_number = ? " . $ignoreDriver, $bindings);
        if ($result) {
            return $this->errorResponse(["license_number" => ["License Number is used before are you (" . $result->name . ")"]], 422); // validation error
        }
        return false;
    }
}
