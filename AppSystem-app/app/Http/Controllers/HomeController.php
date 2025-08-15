<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{

    public function index(Request $request)
    {
        return response()->json(['message' => 'HomeController index placeholder'], 200);
    }
}
