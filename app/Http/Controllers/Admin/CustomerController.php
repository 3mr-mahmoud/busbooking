<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = DB::select("select id, name, phone, email, wallet_balance from customers");
        return response()->json([
            "success" => true,
            "data" => [
                'customers' => $customers
            ]
        ]);
    }

    public function find($customerId)
    {
        $customer = DB::selectOne("select * from customers where id = ? ", [$customerId]);
        unset($customer->password);
        return response()->json([
            "success" => true,
            "data" => [
                'customer' => $customer
            ]
        ]);
    }

    public function update(Request $request, $customerId)
    {

        $customer = DB::selectOne("select * from customers where id = ?", [$customerId]);

        if (!$customer) {
            return $this->errorResponse("Not Found", 404);
        }

        $request->validate([
            'name' => 'required|string',
            'phone' => 'required|integer|digits_between:6,11',
            'email' => 'required|email',
            'password' => 'nullable|min:6',
            'wallet_balance' => 'required|numeric'
        ]);


        if ($resp = $this->handlePhoneErrors($request, $customerId)) {
            return $resp;
        }

        if ($resp = $this->handleEmailErrors($request, $customerId)) {
            return $resp;
        }



        if ($request->password) {
            $password = Hash::make($request->password);
        } else {
            $password = $customer->password;
        }

        DB::update("UPDATE customers SET name = :name, phone = :phone,
         email = :email, password = :password,
         wallet_balance = :wallet_balance
        WHERE id = :id
        ", [
            ":id" => $customerId,
            ":name" => $request->name,
            ":phone" => $request->phone,
            ":email" => $request->email,
            ":password" => $password,
            ":wallet_balance" => $request->wallet_balance,
        ]);

        return response()->json([
            'success' => true,
        ]);
    }

    public function delete($customerId)
    {

        DB::delete("delete from customers where id = ?", [$customerId]);


        return response()->json([
            'success' => true,
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
