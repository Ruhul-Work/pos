<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CoreController extends Controller
{
     public function home()
     {
            // Handle the request and return a view
         return view('backend.modules.dashboard.home');
     }
     public function home2()
     {
            // Handle the request and return a view
         return view('backend.modules.dashboard.home2');
     }
}

