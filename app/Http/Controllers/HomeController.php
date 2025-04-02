<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class HomeController extends Controller
{
    //

    public function index()
    {
        if(Auth::check()){
            return Inertia::render('Dashboard/Index', [
                'user' => Auth::user(),
            ]);
        }
        return redirect()->route('login');
    }
}
