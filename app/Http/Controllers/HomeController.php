<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    //

    public function index(): Response | RedirectResponse
    {
        if(Auth::check()){
            return Inertia::render('Dashboard/Index', [
                'user' => Auth::user(),
            ]);
        }
        return redirect()->route('login');
    }
}
