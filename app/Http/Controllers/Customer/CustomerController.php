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
                'user' => $customer,
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
            "data" => [
                'user' => $customer
            ]
        ]);
    }


    public function register(Request $request)
    {

        $request->validate([
            'name' => 'required|string',
            'phone' => 'required|regex:/[0-9]+/|digits_between:6,12',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        if ($resp = $this->handlePhoneErrors($request)) {
            return $resp;
        }

        if ($resp = $this->handleEmailErrors($request)) {
            return $resp;
        }

        $hashedPassword = Hash::make($request->password);

        $inserted = DB::insert("INSERT INTO customers (name, phone, email, password) 
        VALUES (:name, :phone, :email, :password)", [
            ":name" => $request->name,
            ":phone" => $request->phone,
            ":email" => $request->email,
            ":password" => $hashedPassword,
        ]);

        return response()->json([
            'success' => $inserted,
        ]);
    }

    public function updateProfile(Request $request)
    {

        $customer = DB::selectOne("select * from customers where id = ?", [$request->authenticated_id]);

        if (!$customer) {
            return $this->errorResponse("Not Found", 404);
        }

        $request->validate([
            'name' => 'required|string',
            'phone' => 'required|regex:/[0-9]+/|digits_between:6,12',
            'email' => 'required|email',
            'password' => 'nullable|min:6'
        ]);


        if ($resp = $this->handlePhoneErrors($request, $customer->id)) {
            return $resp;
        }

        if ($resp = $this->handleEmailErrors($request, $customer->id)) {
            return $resp;
        }



        if ($request->password) {
            $password = Hash::make($request->password);
        } else {
            $password = $customer->password;
        }

        DB::update("UPDATE customers SET name = :name, phone = :phone,
         email = :email, password = :password
        WHERE id = :id
        ", [
            ":id" => $customer->id,
            ":name" => $request->name,
            ":phone" => $request->phone,
            ":email" => $request->email,
            ":password" => $password
        ]);

        return response()->json([
            'success' => true,
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


    private function handlePhoneErrors($request, $customerId = 0)
    {
        // check if phone is not used before
        // if I am updating ignore th current customer$customerId
        $bindings = [$request->phone];
        $ignoreCustomer = "";
        if ($customerId) {
            $ignoreCustomer = " AND id != ?";
            $bindings[] = $customerId;
        }
        $result = DB::selectOne("select * from customers where phone = ? " . $ignoreCustomer, $bindings);
        if ($result) {
            return $this->errorResponse(["phone" => ["Phone is used before are you (" . $result->name . ")"]], 422); // validation error
        }
        return false;
    }

    private function handleEmailErrors($request, $customerId = 0)
    {
        // check if email is not used before
        // if I am updating ignore th current customer$customerId
        $bindings = [$request->email];
        $ignoreCustomer = "";
        if ($customerId) {
            $ignoreCustomer = " AND id != ?";
            $bindings[] = $customerId;
        }
        $result = DB::selectOne("select * from customers where email = ? " . $ignoreCustomer, $bindings);
        if ($result) {
            return $this->errorResponse(["email" => ["Email is used before are you (" . $result->name . ")"]], 422); // validation error
        }
        return false;
    }
}
