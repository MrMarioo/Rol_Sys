<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\FieldDataController;

Route::middleware('guest')->group(function () {
    Route::get('login', function () {
        return Inertia::render('Auth/Login');
    })->name('login');

    Route::post('login', [
        \App\Http\Controllers\Auth\AuthenticatedSessionController::class,
        'store',
    ])->name('login.post');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/', [
        \App\Http\Controllers\DashboardController::class,
        'index',
    ])->name('dashboard');

    Route::resource('fields', \App\Http\Controllers\FieldController::class);
    Route::get('fields/{field}/data', [
        \App\Http\Controllers\FieldController::class,
        'data',
    ])->name('fields.data');
    Route::get('fields/{field}/analytics', [
        \App\Http\Controllers\FieldController::class,
        'analytics',
    ])->name('fields.analytics');

    Route::name('crops.')
        ->prefix('crops')
        ->controller(\App\Http\Controllers\CropController::class)
        ->group(function () {
            Route::get('find', 'find')->name('find');
        });

    Route::name('field-data.')
        ->prefix('field-data')
        ->controller(FieldDataController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{fieldData}', 'show')->name('show');
            Route::get('/{fieldData}/edit', 'edit')->name('edit');
            Route::put('/{fieldData}', 'update')->name('update');
            Route::delete('/{fieldData}', 'destroy')->name('destroy');
        });
    Route::name('analytics.')
        ->prefix('analytics')
        ->controller(\App\Http\Controllers\AnalitycsController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/{field}', 'analyze')->name('analyze');
            Route::get('/{analytics}', 'show')->name('show');
            Route::get('/{analytics}/download', 'download')->name('download');
            Route::delete('/{analytics}',  'destroy')->name('destroy');
        });

    Route::name('reports.')
        ->prefix('reports')
        ->controller(\App\Http\Controllers\ReportController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{report}', 'show')->name('show');
            Route::get('/{report}/edit', 'edit')->name('edit');
            Route::put('/{report}', 'update')->name('update');
            Route::post('/{report}/generate', 'generate')->name('generate');
            Route::get('/{report}/download', 'download')->name('download');
            Route::delete('/{report}', 'destroy')->name('destroy');
        });
    //
    //    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    //    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
});
