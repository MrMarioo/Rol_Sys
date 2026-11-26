<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FieldController;
use App\Http\Controllers\FieldDataController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('fields', FieldController::class)->except(['create']);
    Route::get('fields/{field}/data', [FieldController::class, 'data'])->name('fields.data');
    Route::get('fields/{field}/analytics', [FieldController::class, 'analytics'])->name('fields.analytics');

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
        ->controller(AnalyticsController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/{field}', 'analyze')->name('analyze');
            Route::get('/{analytics}', 'show')->name('show');
            Route::get('/{analytics}/download', 'download')->name('download');
            Route::delete('/{analytics}', 'destroy')->name('destroy');
        });

    Route::name('reports.')
        ->prefix('reports')
        ->controller(ReportController::class)
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

    Route::name('notifications.')
        ->prefix('notifications')
        ->controller(NotificationController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/read-all', 'readAll')->name('read-all');
            Route::post('/{notification}/read', 'read')->name('read');
        });
});
