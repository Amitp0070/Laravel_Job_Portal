<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    // This method will show our home page 
    public function index(){
        return view('front.home');
    }

}
