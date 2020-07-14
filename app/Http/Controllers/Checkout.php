<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Checkout extends Controller
{
    public function getData($orderId)
    {
        dd($orderId);
    }
}
