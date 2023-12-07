<?php

namespace App\Http\Controllers\Customer;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;

class CustomerController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);

        $customer = DB::selectOne("select * from customers where email = ?", [$request->email]);

        if (!$customer) {
            return $this->errorResponse(['email' => ['Provided Credentials are incorrect']]);
        }

        if (!Hash::check($request->password, $customer->password)) {
            return $this->errorResponse(['password' => ['Provided Credentials are incorrect']]);
        }

        $token =  Str::random(255);

        $inserted = DB::insert("INSERT INTO customer_tokens (customer_id,token) VALUES (:customer_id,:token)", [
            ":customer_id" => $customer->id,
            ":token" => $token,
        ]);

        if (!$inserted) {
            abort(500);
        }


        unset($customer->password); // remove the password from the response

        return response()->json([
            "success" => true,
            "data" => [
                'customer' => $customer,
                'token' => $token
            ]
        ]);
    }
    public function me(Request $request)
    {
        $customer = DB::selectOne("select * from customers where id = " . $request->authenticated_id);
        unset($customer->password);
        return response()->json([
            "success" => true,
            "data" => $customer
        ]);
    }

    public function logout(Request $request)
    {
        $tokendeleted = DB::delete("delete from customer_tokens where customer_id = ? AND token = ?", [
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
