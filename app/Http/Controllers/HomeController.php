<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class HomeController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route(route: 'login');
        }

        return Inertia::render(
            component: 'Dashboard/Index',
            props: [
                'user' => Auth::user(),
            ]
        );
    }
}