<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function get_all_customers()
    {
        $customers = Customer::all();

        return response()->json(['customers' => $customers], 200);
    }


}