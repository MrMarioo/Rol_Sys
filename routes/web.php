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

    //    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    //    Route::get('/analytics/{field}', [AnalyticsController::class, 'fieldAnalysis'])->name('analytics.field');
    //    Route::post('/analytics/predict', [AnalyticsController::class, 'predict'])->name('analytics.predict');
    //
    //    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    //    Route::get('/reports/create', [ReportController::class, 'create'])->name('reports.create');
    //    Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
    //    Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    //    Route::get('/reports/{report}/download', [ReportController::class, 'download'])->name('reports.download');
    //
    //    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    //    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
});
