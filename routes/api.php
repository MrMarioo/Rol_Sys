<?php

use App\Http\Controllers\Api\FieldDataController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/field-data', [FieldDataController::class, 'store']);
    Route::get('/field-data/{fieldData}', [FieldDataController::class, 'show']);
    Route::get('/fields/{field}/data', [FieldDataController::class, 'forField']);

    Route::get('/health', function () {
        return response()->json(['status' => 'ok', 'timestamp' => now()]);
    });
});

Route::post('/drone/field-data', [FieldDataController::class, 'drone']);