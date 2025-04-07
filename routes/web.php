<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;



Route::prefix('login')->group(function () {
    Route::get('', function () {
        return Inertia::render('Auth/Login');
    })->name("login");
    Route::post('', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store'])->middleware(['guest'])->name('login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/', [\App\Http\Controllers\HomeController::class, 'index'])->name('home');

    Route::resource('fields', \App\Http\Controllers\FieldController::class);
});
