<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WebController extends Controller
{
    public function show()
    {
        $title = "Welcome to the Main Page";
        $message = "example";
        return view('main', compact('title', 'message'));
    }
}
