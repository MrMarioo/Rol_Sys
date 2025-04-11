<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

    //    Route::get('/field-data', [FieldDataController::class, 'index'])->name('field-data.index');
    //    Route::get('/field-data/{field}', [FieldDataController::class, 'show'])->name('field-data.show');
    //    Route::post('/field-data/import', [FieldDataController::class, 'import'])->name('field-data.import');
    //    Route::get('/field-data/{field}/export', [FieldDataController::class, 'export'])->name('field-data.export');
    //
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
