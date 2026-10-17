<?php

namespace App\Http\Controllers\Development;

use App\Http\Controllers\Controller;

class CommandController extends Controller
{
    public function index()
    {
        return view('development.command.index');
    }
}
