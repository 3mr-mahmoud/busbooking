<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;

class AdminController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);

        $admin = DB::selectOne("select * from admins where email = ?", [$request->email]);

        if (!$admin) {
            return $this->errorResponse(['email' => ['Provided Credentials are incorrect']], 422);
        }

        if (!Hash::check($request->password, $admin->password)) {
            return $this->errorResponse(['password' => ['Provided Credentials are incorrect']], 422);
        }

        $token =  Str::random(255);

        $inserted = DB::insert("call insert_admin_token(:admin_id,:token)", [
            ":admin_id" => $admin->id,
            ":token" => $token,
        ]);

        if (!$inserted) {
            abort(500);
        }


        unset($admin->password);

        return response()->json([
            "success" => true,
            "data" => [
                'admin' => $admin,
                'token' => $token
            ]
        ]);
    }
    public function me(Request $request)
    {
        $admin = DB::selectOne("call select_admin(?)", [$request->authenticated_id]);
        unset($admin->password);
        return response()->json([
            "success" => true,
            "data" => [
                'user' => $admin
            ]
        ]);
    }

    public function index(Request $request)
    {
        $authenticatedAdmin = DB::selectOne("call select_superadmin_flag(?)", [$request->authenticated_id]);
        if (!$authenticatedAdmin->superadmin) {
            return $this->errorResponse("Insufficient Permissions", 403);
        }

        $admins = DB::select("select id, name, phone, email, superadmin from admins");
        return response()->json([
            "success" => true,
            "data" => [
                'admins' => $admins
            ]
        ]);
    }

    public function find(Request $request, $adminId)
    {
        $authenticatedAdmin = DB::selectOne("call select_superadmin_flag(?)", [$request->authenticated_id]);
        if (!$authenticatedAdmin->superadmin) {
            return $this->errorResponse("Insufficient Permissions", 403);
        }

        $admin = DB::selectOne("call select_admin(?)", [$adminId]);
        unset($admin->password);
        return response()->json([
            "success" => true,
            "data" => [
                'admin' => $admin
            ]
        ]);
    }

    public function store(Request $request)
    {
        $admin = DB::selectOne("call select_superadmin_flag(?)", [$request->authenticated_id]);
        if (!$admin->superadmin) {
            return $this->errorResponse("Insufficient Permissions", 403);
        }

        $request->validate([
            'name' => 'required|string',
            'phone' => 'required|numeric',
            'email' => 'required|email',
            'password' => 'required|min:6',
            'superadmin' => 'nullable|boolean'
        ]);

        if ($resp = $this->handlePhoneErrors($request)) {
            return $resp;
        }

        if ($resp = $this->handleEmailErrors($request)) {
            return $resp;
        }

        $hashedPassword = Hash::make($request->password);

        $inserted = DB::insert("INSERT INTO admins (name, phone, email, password, superadmin) 
        VALUES (:name, :phone, :email, :password, :superadmin)", [
            ":name" => $request->name,
            ":phone" => $request->phone,
            ":email" => $request->email,
            ":password" => $hashedPassword,
            ":superadmin" => $request->superadmin ?? 0,
        ]);

        return response()->json([
            'success' => $inserted,
        ]);
    }


    public function update(Request $request, $adminId)
    {
        $authenticatedAdmin = DB::selectOne("call select_superadmin_flag(?)", [$request->authenticated_id]);
        // accessed only by superadmin or normal admin updating his data
        if (!$authenticatedAdmin->superadmin && $request->authenticated_id != $adminId) {
            return $this->errorResponse("Insufficient Permissions", 403);
        }

        $admin = DB::selectOne("call select_admin(?)", [$adminId]);

        if (!$admin) {
            return $this->errorResponse("Not Found", 404);
        }

        $request->validate([
            'name' => 'required|string',
            'phone' => 'required|integer',
            'email' => 'required|email',
            'password' => 'nullable|min:6',
            'superadmin' => 'nullable|boolean'
        ]);


        if ($resp = $this->handlePhoneErrors($request, $adminId)) {
            return $resp;
        }

        if ($resp = $this->handleEmailErrors($request, $adminId)) {
            return $resp;
        }



        if ($request->password) {
            $password = Hash::make($request->password);
        } else {
            $password = $admin->password;
        }
        $superadmin = $request->superadmin ?? $admin->superadmin;
        // the one doing the request is not super admin then 
        // he must be a normal admin updating his data to access this
        if (!$authenticatedAdmin->superadmin) {
            $superadmin = 0;
        }

        DB::update("call update_admin(:id, :name, :phone, :email, :password, :superadmin)", [
            ":id" => $adminId,
            ":name" => $request->name,
            ":phone" => $request->phone,
            ":email" => $request->email,
            ":password" => $password,
            ":superadmin" => $superadmin,
        ]);

        return response()->json([
            'success' => true,
        ]);
    }

    public function delete(Request $request, $adminId)
    {
        $authenticatedAdmin = DB::selectOne("call select_superadmin_flag(?)", [$request->authenticated_id]);
        // accessed only by superadmin or normal admin updating his data
        if (!$authenticatedAdmin->superadmin) {
            return $this->errorResponse("Insufficient Permissions", 403);
        }

        DB::delete("delete from admins where id = ?", [$adminId]);


        return response()->json([
            'success' => true,
        ]);
    }

    public function logout(Request $request)
    {
        $tokendeleted = DB::delete("delete from admin_tokens where admin_id = ? AND token = ?", [
            $request->authenticated_id,
            $request->bearerToken()
        ]);

        return response()->json([
            "success" => true,
            "message" => "logged out succesfully"
        ]);
    }

    private function handlePhoneErrors($request, $adminId = 0)
    {
        // check if phone is not used before
        // if I am updating ignore th current adminId
        $bindings = [$request->phone];
        $ignoreAdmin = "";
        if ($adminId) {
            $ignoreAdmin = " AND id != ?";
            $bindings[] = $adminId;
        }
        $result = DB::selectOne("select * from admins where phone = ? " . $ignoreAdmin, $bindings);
        if ($result) {
            return $this->errorResponse(["phone" => ["Phone is used before are you (" . $result->name . ")"]], 422); // validation error
        }
        return false;
    }

    private function handleEmailErrors($request, $adminId = 0)
    {
        // check if email is not used before
        // if I am updating ignore th current adminId
        $bindings = [$request->email];
        $ignoreAdmin = "";
        if ($adminId) {
            $ignoreAdmin = " AND id != ?";
            $bindings[] = $adminId;
        }
        $result = DB::selectOne("select * from admins where email = ? " . $ignoreAdmin, $bindings);
        if ($result) {
            return $this->errorResponse(["email" => ["Email is used before are you (" . $result->name . ")"]], 422); // validation error
        }
        return false;
    }
}
