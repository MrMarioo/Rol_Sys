<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FieldDataController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/field-data', [FieldDataController::class, 'apiStore']);
    Route::get('/field-data/{fieldData}', [FieldDataController::class, 'apiShow']);
    Route::get('/fields/{field}/data', [FieldDataController::class, 'apiFieldData']);

    Route::get('/health', function () {
        return response()->json(['status' => 'ok', 'timestamp' => now()]);
    });
});

Route::post('/drone/field-data', [FieldDataController::class, 'droneStore']);
