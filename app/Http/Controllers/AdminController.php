<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

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
            return $this->errorResponse(['email' => ['Provided Credentials are incorrect']]);
        }

        if (!Hash::check($request->password, $admin->password)) {
            return $this->errorResponse(['password' => ['Provided Credentials are incorrect']]);
        }

        $token =  Str::random(255);

        $inserted = DB::insert("INSERT INTO admin_tokens (admin_id,token) VALUES (:admin_id,:token)", [
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
        $admin = DB::selectOne("select * from admins where id = " . $request->authenticated_id);
        unset($admin->password);
        return response()->json([
            "success" => true,
            "data" => $admin
        ]);
    }

    public function logout(Request $request)
    {
        $tokendeleted = DB::delete("delete from admin_tokens where admin_id = ? AND token = ?", [
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
