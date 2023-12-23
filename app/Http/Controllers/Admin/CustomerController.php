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
            'wallet_balance' => 'required|numeric'
        ]);

        DB::update("UPDATE customers SET wallet_balance = :wallet_balance
        WHERE id = :id
        ", [
            ":id" => $customerId,
            ":wallet_balance" => $request->wallet_balance
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
}
