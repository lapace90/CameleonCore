<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class WelcomeController extends Controller
{
    public function index()
    {
        $users = User::all();
        // var_dump($users);
        return view('welcome', compact('users'));
    }
    
}

